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


    protected function _fullRetain($path, $retention) {

        $command = self::TAHOE_ALIAS . ' ls ' . $path . ' 2>&1';
        $commandOutput  = array();
        $status         = 0;
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            $this->_logger->err('Cannot access to tahoe storage [fullRetain_ls]: ' . implode("\n",$commandOutput), $this->_context);
            return $status;
        }
        $i = count($commandOutput);
        if($i > $retention) {
            $command = self::TAHOE_ALIAS . ' unlink ' . $path . $commandOutput[$i-1] . ' 2>&1';
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

        $idClient = $job->getClient()->getId();
        $idJob    = $job->getId();
        
        return 'elkarbackup:Backups/' . sprintf('%04d', $idClient) . '/' . sprintf('%04d', $idJob) . '/';
    }


    protected function _runJob(Pair $pair) {

        $job = $pair->getJob();
        $retain = $pair->getRetain();

        $retains = $job->getPolicy()->getRetains();
        foreach ($retains as $r) {
            if($r[0]===$retain) {
                $retention = $r[1];
                break;
            }
        }
        // $retention should always be greater than 0

        $this->_logger->info('runJob');


        if(!$job->getPolicy()->isRotation($retain)) { //no rotation
            $this->_logger->info('no rotation');
            /*
            NO rotation
            ejemplo de guia:
            tahoe backup /home/ales/Documentos/pruebabackup elkarbackup:Backups/0005/0007/
            tahoe ls elkarbackup:Backups/0005/0007/Archives
            tahoe mv elkarbackup:Backups/0005/0007/Archives/2015-10-30_09:10:03Z elkarbackup:Backups/0005/0007/Hourly/2015-10-30_09:10:03Z
            */

            //TODO obtener $what;
            //TODO obtener $filename;
            $what = '/home/ales/Documentos/pruebabackup';
            $fileName = 'pruebabackup';

            $command = self::TAHOE_ALIAS . ' backup ' . $what . ' ' . $this->_getJobPath() . ' 2>&1';
            exec($command, $commandOutput, $status);
            if (0 != $status) {
                $this->_logger->err('Cannot perform backup on tahoe storage [no_rot_backup]: ' . implode("\n",$commandOutput), $this->_context);
                return $status;
            }

            $command = self::TAHOE_ALIAS . ' ls ' . $this->_getJobPath() . 'Archives 2>&1';
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0 != $status) {
                $this->_logger->err('Cannot access to tahoe storage [no_rot_ls1]: ' . implode("\n",$commandOutput), $this->_context);
                return $status;
            }

            $command = self::TAHOE_ALIAS . ' mv ' . $this->_getJobPath() . 'Archives/' . $commandOutput[0];
            $command .=                       ' ' . $this->_getJobPath() . $retain . '/' . $commandOutput[0] . '_' . $fileName . ' 2>&1';
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0 != $status) {
                $this->_logger->err('Cannot access to tahoe storage [no_rot_mv]: ' . implode("\n",$commandOutput), $this->_context);
                return $status;
            }

            $this->_logger->info('Backup performed.');


            $path = $this->_getJobPath($job) . $retain . '/';
            $result = $this->_fullRetain($path, $retention);
            if(0 === $result) $this->_logger->info($retain . ' was full: oldest item *deleted');
            else if (null != $resutl) return $result;
 

        } else { //rotation
            $this->_logger->info('rotation');

            $previousRetain = null;
            foreach ($retains as $r) {
                if($r[0]===$retain) break;
                $previousRetain = $r[0];
            }
            // $previous should never be null if it's a rotation

            $command = self::TAHOE_ALIAS . ' ls ' . $this->_getJobPath($job) . $previousRetain . '/ 2>&1';
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0 != $status) {
                $this->_logger->err('Cannot access to tahoe storage [rot_ls1]: ' . implode("\n",$commandOutput), $this->_context);
                return $status;
            }
            $i = count($commandOutput);
            if($i > 0) {

                $command = self::TAHOE_ALIAS . ' cp ' . $this->_getJobPath() . $previousRetain  . '/' . $commandOutput[$i-1];
                $command .=                       ' ' . $this->_getJobPath() . $retain          . '/' . $commandOutput[$i-1] ' 2>&1';
                $commandOutput  = array();
                $status         = 0;
                exec($command, $commandOutput, $status);
                if (0 != $status) {
                    $this->_logger->err('Cannot access to tahoe storage [rot_cp]: ' . implode("\n",$commandOutput), $this->_context);
                    return $status;
                }

                $path = $this->_getJobPath($job) . $retain . '/';
                $result = $this->_fullRetain($path, $retention);
                if(0 === $result) $this->_logger->info($retain . ' was full: oldest item *deleted');
                else if (null != $resutl) return $result;

            } else {
                $this->_logger->warn('Backup rotation was tried but no items were found in the previous retain level');
            }
        }

        
        return true;
    }


    public function __construct(Logger $log)
    {
    //it is a service so it gets called only once

        $this->_logger = $log;
        //TODO: inject translator
        $this->_context = array('source' => 'TahoeBackup');
        $this->_queue = new SplQueue();
    }
    
    public function enqueueJob(Job $job, $retain) {

        $this->_queue->enqueue(new Pair($job, $retain));
    }

    public function isInstalled() {
        $command = 'dpkg-query -W tahoe-lafs';
        exec($command, $commandOutput, $status);
        if (0 != $status)
            return $status;

        return true;
    }

    public function runAllQueuedJobs() {

        foreach($this->_queue as $pair) {
            $this->_logger->info('job dequeued');
            $this->_runJob($pair);
        }

    }

    /*  //No reason to make runJob public (for now)
    public function runJob(Job $job, $retain) {
        $this->_runJob(new Pair($job, $retain));
    } */

}
