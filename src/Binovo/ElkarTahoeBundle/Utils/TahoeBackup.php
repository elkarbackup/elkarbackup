<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarTahoeBundle\Utils;

use Binovo\ElkarBackupBundle\Entity\Job;
use Binovo\ElkarTahoeBundle\Utils\Pair;
use Symfony\Bridge\Monolog\Logger;
use \SplQueue;

class TahoeBackup
{
    const NODE_DIR_NAME = '.tahoe/';    //default when 'tahoe create-node'
    const POINTER_DIR = 'tahoe-client/';

    const TAHOE_ALIAS = 'tahoe';    //tahoe bin file path
    const READY_FILE = 'elkarbackupNodeReady';

    protected $_nodeLocation;
    protected $_logger;
    protected $_context;
    protected $_queue;

    public function __construct(Logger $log, $home='/var/lib/elkarbackup')
    {
        $this->_nodeLocation = $home . '/';
        $this->_logger = $log;
        $this->_context = array('source' => 'TahoeBackup');
        $this->_queue = new SplQueue();
    }

    public function enqueueJob(Job $job, $retain)
    {

        $this->_queue->enqueue(new Pair($job, $retain));
    }

    protected function _fullRetain($path, $retention)
    {
        $command = self::TAHOE_ALIAS . ' ls ' . $path . ' 2>&1';
        $commandOutput  = array();
        $status         = 0;
        exec($command, $commandOutput, $status);
        if (0!=$status) {
            $this->_logger->err('Cannot access to Tahoe storage [fullRetain_ls]: ' . implode("\n", $commandOutput), $this->_context);
            return $status;
        }
        $i = count($commandOutput);
        if ($i > $retention) {
            $command = self::TAHOE_ALIAS . ' unlink ' . $path . $commandOutput[0] . ' 2>&1';
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0!=$status) {
                $this->_logger->err('Cannot access to Tahoe storage [no_rot_unlink]: ' . implode("\n", $commandOutput), $this->_context);
                return $status;
            }
            return 0;
        }
        return null;
    }

    public function getBin()
    {
        return self::TAHOE_ALIAS;
    }


    protected function _getJobPath(Job $job)
    {
        $paramsFilename = dirname(__FILE__) . '/../../../../app/config/parameters.yml';
        $paramsFile = file_get_contents(realpath($paramsFilename) );
        if (false==$paramsFile) {
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

    protected function _getJobTahoePath(Job $job)
    {
        $idClient = $job->getClient()->getId();
        $idJob    = $job->getId();

        return 'elkarbackup:Backups/' . sprintf('%04d', $idClient) . '/' . sprintf('%04d', $idJob) . '/';
    }

    public function getNodePath()
    {
        return $this->_nodeLocation . self::NODE_DIR_NAME;
    }

    public function getPointerToNode()
    {
        return $this->_nodeLocation . self::POINTER_DIR;
    }

    public function getReadyCode()
    {
        $filePath = $this->_nodeLocation . self::NODE_DIR_NAME . self::READY_FILE;
        if (!file_exists($filePath)) {
            return '000';
        } else {
            return file_get_contents($filePath);
        }
    }

    public function getReadyFile()
    {
        return self::NODE_DIR_NAME . self::READY_FILE;
    }

    public function getRelativeNodePath()
    {
        //relative so 'elkarbackup' does not traverse directories without permission
        return self::NODE_DIR_NAME;
    }

    public function getRelativePointerToNode()
    {
        return self::POINTER_DIR;
    }

    public function isInstalled()
    {
        $command = "which tahoe";
        exec($command, $commandOutput, $status);
        if (0 != $status){
            return false;
        } else {
            return true;
        }
    }

    public function isReady()
    {
        $filePath = $this->_nodeLocation . self::NODE_DIR_NAME . self::READY_FILE;
        if (!file_exists($filePath)) {
            return false;
        }
        $content = file_get_contents($filePath);
        $key = $content[0] . $content[1] . $content[2];
        switch ($key) {
            case '100':
                //keep going
            case '200':
                //keep going
            case 'URI':
                return true;
                break;
            case '000':
                //keep going
            case '101':
                //keep going
            case '500':
                return false;
                break;
            default: //should never happen
                return false;
                break;
        }
        return false;
    }

    public function runAllQueuedJobs()
    {
        foreach ($this->_queue as $pair) {
            $this->_runJob($pair);
        }
    }

    protected function _runJob(Pair $pair)
    {
        if (!file_exists(self::NODE_DIR_NAME . self::READY_FILE)) {
            $this->_logger->err('Cannot perform backup on Tahoe storage: Tahoe configuration not properly set', $this->_context);
            return false;
        }

        $job = $pair->getJob();
        $retain = $pair->getRetain();

        $retains = $job->getPolicy()->getRetains();
        foreach ($retains as $r) {
            if ($r[0]===$retain) {
                $retention = $r[1];
                break;
            }
        } // $retention should always be greater than 0

        if (!$job->getPolicy()->isRotation($retain)) { //no rotation
            $backupDir = $this->_getJobPath($job);
            if (false==$backupDir) {
                $this->_logger->err('Cannot perform backup on Tahoe storage [no_rot_getDir]: Cannot obtain source directory', $this->_context);
                return false;
            }
            $backupDir .= $retain . '.0';

            $command = self::TAHOE_ALIAS . ' backup ' . $backupDir . ' ' . $this->_getJobTahoePath($job) . ' 2>&1';
            exec($command, $commandOutput, $status);
            if (0!=$status) {
                $this->_logger->err('Cannot perform backup on Tahoe storage [no_rot_backup]: ' . implode("\n", $commandOutput), $this->_context);
                return false;
            }

            $command = self::TAHOE_ALIAS . ' ls ' . $this->_getJobTahoePath($job) . 'Archives 2>&1';
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0!=$status) {
                $this->_logger->err('Cannot access to Tahoe storage [no_rot_ls1]: ' . implode("\n", $commandOutput), $this->_context);
                return false;
            }

            $i = count($commandOutput);
            //there should be only one but, just in case, take the last one
            $command = self::TAHOE_ALIAS . ' mv ' . $this->_getJobTahoePath($job) . 'Archives/' . $commandOutput[$i-1];
            $command .=                       ' ' . $this->_getJobTahoePath($job) . $retain . '/' . $commandOutput[$i-1] . ' 2>&1';
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0!=$status) {
                $this->_logger->err('Cannot access to Tahoe storage [no_rot_mv]: ' . implode("\n", $commandOutput), $this->_context);
                return false;
            }

            $path = $this->_getJobTahoePath($job) . $retain . '/';
            $result = $this->_fullRetain($path, $retention);
            if (0===$result) {
                $this->_logger->info($retain . ' was full: oldest item *deleted', $this->_context);
            } else {
                if (null!=$resutl) { //if $result == null, retain was not full
                    return false; //some error happened (shown in the log)
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
            } // $previous should never be null because it is a rotation

            $command = self::TAHOE_ALIAS . ' ls ' . $this->_getJobTahoePath($job) . $previousRetain . '/ 2>&1';
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0==$status && count($commandOutput) > 0) {
                $command = self::TAHOE_ALIAS . ' cp -r ' . $this->_getJobTahoePath($job) . $previousRetain  . '/' . $commandOutput[0];
                $command .=                          ' ' . $this->_getJobTahoePath($job) . $retain          . '/' . $commandOutput[0] . ' 2>&1';
                $commandOutput  = array();
                $status         = 0;
                exec($command, $commandOutput, $status);
                if (0!=$status) {
                    $this->_logger->err('Cannot access to Tahoe storage [rot_cp]: ' . implode("\n", $commandOutput), $this->_context);
                    return false;
                }

                $path = $this->_getJobTahoePath($job) . $retain . '/';
                $result = $this->_fullRetain($path, $retention);
                if (0===$result) {
                    $this->_logger->info($retain . ' was full: oldest item *deleted', $this->_context);
                } else {
                    if (null!=$resutl) { //if $result == null, retain was not full
                        return false; //some error happened (shown in the log)
                    }
                }
                $this->_logger->info('Tahoe backup done - ' . $retain . ' rotation', $this->_context);
            } else {
                $this->_logger->warn('Backup rotation was tried but no items were found in the previous retain level', $this->_context);
                return false;
            }
        }
        return true;
    }
}
