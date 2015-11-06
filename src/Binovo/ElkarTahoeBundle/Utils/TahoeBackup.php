<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarTahoeBundle\Utils;

use Binovo\ElkarBackupBundle\Entity\Job;
use Binovo\ElkarTahoeBundle\Utils\Pair;
use Symfony\Bridge\Monolog\Logger;
//translator
use \SplQueue;


class TahoeBackup
{

    const TAHOE_ALIAS = 'tahoe'; //tahoe bin file path

    protected $_logger;
    //protected $_trans;
    protected $_context;
    protected $_queue;


    public function __construct(Logger $log)
    {
    //it is a service so it gets called only once

        $this->_logger = $log;
        //TODO: inject translator
        $this->_context = array('source' => 'TahoeBackup');
        $this->_queue = new SplQueue();
    }


    protected function _fullRetain($path, $retention)
    {

        $command = self::TAHOE_ALIAS . ' ls ' . $path . ' 2>&1';
        $commandOutput  = array();
        $status         = 0;
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            $this->_logger->err('Cannot access to tahoe storage [fullRetain_ls]: ' . implode("\n",$commandOutput), $this->_context);
            return $status;
        }
        $i = count($commandOutput);
        if ($i > $retention) {
            $command = self::TAHOE_ALIAS . ' unlink ' . $path . $commandOutput[0] . ' 2>&1';
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0 != $status) {
                $this->_logger->err('Cannot access to tahoe storage [no_rot_unlink]: ' . implode("\n",$commandOutput), $this->_context);
                return $status;
            }

            return 0;
        }

        return null;

    }

    protected function _getJobPath(Job $job) {

        $paramsFilename = dirname(__FILE__) . '/../../../../app/config/parameters.yml';
        $paramsFile = file_get_contents(realpath($paramsFilename) );
        if (false == $paramsFile) {
            return false;
        }

        $backupDirParam = 'backup_dir:';
        $i=strpos($paramsFile, $backupDirParam);
        $i+=strlen($backupDirParam);
        $value = '';

        for (;$i<strlen($paramsFile);$i++) {
            if ("\n"==$paramsFile[$i]) {
                break;
            }
            if (' '!=$paramsFile[$i]) {
                $value.=$paramsFile[$i];
            }
        }

        $idClient = $job->getClient()->getId();
        $idJob    = $job->getId();

        return $value . '/' . sprintf('%04d', $idClient) . '/' . sprintf('%04d', $idJob) . '/';
    }

    protected function _getJobTahoePath(Job $job) {

        $idClient = $job->getClient()->getId();
        $idJob    = $job->getId();
        
        return 'elkarbackup:Backups/' . sprintf('%04d', $idClient) . '/' . sprintf('%04d', $idJob) . '/';
    }


    protected function _runJob(Pair $pair) {



        if (!file_exists('.tahoe/imReady.txt')) {
            $this->_logger->err('Cannot perform backup on tahoe storage: tahoe configuration not properly set', $this->_context);
            return -1;
        }

        $job = $pair->getJob();
        $retain = $pair->getRetain();

        $retains = $job->getPolicy()->getRetains();
        foreach ($retains as $r) {
            if ($r[0]===$retain) {
                $retention = $r[1];
                break;
            }
        }
        // $retention should always be greater than 0

        if (!$job->getPolicy()->isRotation($retain)) { //no rotation

            $backupDir = $this->_getJobPath($job);
            if(false == $backupDir) {
                $this->_logger->err('Cannot perform backup on tahoe storage [no_rot_getDir]: Cannot obtain source directory', $this->_context);
                return 1;
            }
            $backupDir .= $retain . '.0';

            $command = self::TAHOE_ALIAS . ' backup ' . $backupDir . ' ' . $this->_getJobTahoePath($job) . ' 2>&1';
            exec($command, $commandOutput, $status);
            if (0 != $status) {
                $this->_logger->err('Cannot perform backup on tahoe storage [no_rot_backup]: ' . implode("\n",$commandOutput), $this->_context);
                return $status;
            }

            $command = self::TAHOE_ALIAS . ' ls ' . $this->_getJobTahoePath($job) . 'Archives 2>&1';
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0 != $status) {
                $this->_logger->err('Cannot access to tahoe storage [no_rot_ls1]: ' . implode("\n",$commandOutput), $this->_context);
                return $status;
            }

            $i = count($commandOutput);
            //there should be only one but, just in case, we take the last one
            $command = self::TAHOE_ALIAS . ' mv ' . $this->_getJobTahoePath($job) . 'Archives/' . $commandOutput[$i-1];
            $command .=                       ' ' . $this->_getJobTahoePath($job) . $retain . '/' . $commandOutput[$i-1] . ' 2>&1';
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0 != $status) {
                $this->_logger->err('Cannot access to tahoe storage [no_rot_mv]: ' . implode("\n",$commandOutput), $this->_context);
                return $status;
            }

            $path = $this->_getJobTahoePath($job) . $retain . '/';
            $result = $this->_fullRetain($path, $retention);
            if (0 === $result) {
                $this->_logger->info($retain . ' was full: oldest item *deleted', $this->_context);
            } else {
                if (null != $resutl) {
                    return $result;
                }
            }
            $this->_logger->info('Tahoe backup done - ' . $retain, $this->_context);

        } else { //rotation

            $previousRetain = null;
            foreach ($retains as $r) {
                if ($r[0]===$retain) {
                    break;
                }
                $previousRetain = $r[0];
            }
            // $previous should never be null if it's a rotation

            $command = self::TAHOE_ALIAS . ' ls ' . $this->_getJobTahoePath($job) . $previousRetain . '/ 2>&1';
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0 == $status && count($commandOutput) > 0) {
                $command = self::TAHOE_ALIAS . ' cp -r ' . $this->_getJobTahoePath($job) . $previousRetain  . '/' . $commandOutput[0];
                $command .=                          ' ' . $this->_getJobTahoePath($job) . $retain          . '/' . $commandOutput[0] . ' 2>&1';
                $commandOutput  = array();
                $status         = 0;
                exec($command, $commandOutput, $status);
                if (0 != $status) {
                    $this->_logger->err('Cannot access to tahoe storage [rot_cp]: ' . implode("\n",$commandOutput), $this->_context);
                    return $status;
                }

                $path = $this->_getJobTahoePath($job) . $retain . '/';
                $result = $this->_fullRetain($path, $retention);
                if (0 === $result) {
                    $this->_logger->info($retain . ' was full: oldest item *deleted', $this->_context);
                } else {
                    if (null != $resutl) {
                        return $result;
                    }
                }
                $this->_logger->info('Tahoe backup done - ' . $retain . ' rotation', $this->_context);
            } else {
                $this->_logger->warn('Backup rotation was tried but no items were found in the previous retain level', $this->_context);
                return $status;
            }

        }
        return true;
    }

    
    public function enqueueJob(Job $job, $retain)
    {

        $this->_queue->enqueue(new Pair($job, $retain));
    }

    public function isInstalled()
    {   //doesent work after uninstalling

        $command = 'dpkg-query -W tahoe-lafs';
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            return $status;
        }
        return true;
    }

    public function runAllQueuedJobs() {

        foreach ($this->_queue as $pair) {
            $this->_runJob($pair);
        }

    }

}
