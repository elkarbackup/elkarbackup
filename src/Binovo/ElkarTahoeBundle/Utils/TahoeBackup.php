<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarTahoeBundle\Utils;

use Binovo\ElkarBackupBundle\Entity\Job;
use Symfony\Bridge\Monolog\Logger;


class TahoeBackup
{
    const TAHOE_ALIAS = 'tahoe'; //tahoe bin file path
    protected $_logger;
    protected $_context;

    public function __construct(Logger $log)
    {
        $this->_logger = $log;
        $this->_context = array('source' => $this->_getNameForLogs());
    }

    public function backup(Job $job, $retain) {
        //TODO

        return true;
    }

    public function isInstalled() {
        $command = 'dpkg-query -W tahoe';
        exec($command, $commandOutput, $status);
        if (0 != $status)
            return false;
            
        return true;
    }

    public function runJob(Job $job, $retain) {
        //TODO

        return true;
    }
    
    protected function rotate(Job $job, $retain) {
        //TODO

        return true;
    }

    protected function _getLastFile(Job $job, $retain) { 
        //TEST
        
        $filePath = $this->getJobPath($job) . $retain . '/';
        $command = TAHOE_ALIAS . ' ls ' . $filePath . ' 2>&1';
        $commandOutput  = array();
        $status         = 0;
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            return false;
        }
        $i=count($commandOutput)-1;
        $fileName = $commandOutput[$i];

        return $filePath . $fileName;
    }

    protected function _getJobPath(Job $job) {

        $idClient = $job->getClient()->getId();
        $idJob    = $job->getId();
        
        return 'elkarbackup:Backups/' . sprintf('%04d', $idClient) . '/' . sprintf('%04d', $idJob) . '/';
    }

    protected function _getNameForLogs() {

        return 'TahoeBackup';
    }

}
