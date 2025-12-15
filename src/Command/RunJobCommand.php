<?php
namespace App\Command;

use Assetic\Exception\Exception;
use App\Entity\Job;
use App\Lib\LoggingCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunJobCommand extends LoggingCommand
{
    
    protected function configure()
    {
        parent::configure();
        $this->setName('elkarbackup:run_job')
            ->setDescription('Runs specified job (must be in the queue). Runs all pending retains')
            ->addArgument('job', InputArgument::REQUIRED, 'The ID of the job.');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $container = $this->getContainer();
            $manager = $container->get('doctrine')->getManager();
            
            $jobId = $input->getArgument('job');
            if (! ctype_digit($jobId)) {
                $this->err('Input argument not valid');
                return self::ERR_CODE_INPUT_ARG;
            }
            $job = $container
            ->get('doctrine')
            ->getRepository('App:Job')
            ->find($jobId);
            if (null == $job) {
                $this->err('Job not found');
                return self::ERR_CODE_ENTITY_NOT_FOUND;
            }
            
            $policy = $job->getPolicy();
            $retains = $policy->getRetains();
            if (empty($retains)) {
                $this->warn('Policy %policyid% has no active retains', array('%policyid%' => $policy->getId()));
                return self::ERR_CODE_NO_ACTIVE_RETAINS;
            }
            $retainsToRun = $this->getRunnableRetains($job);
            if (count($retainsToRun) == 0) {
                $this->warn('Job %jobid% not found in queue', array('%jobid%' => $jobId));
                //Return unknown error code because this shouldn't happen
                return self::ERR_CODE_UNKNOWN;
            }
            
            $result = $this->runJob($job, $retainsToRun);
            $manager->flush();
            
            return $result;
            
        } catch (Exception $e) {
            $this->err('Unknown exception\n'.$e->getMessage());
            return self::ERR_CODE_UNKNOWN;
        }

    }
    
    protected function runJob(Job $job, $runnableRetains)
    {
        $container = $this->getContainer();
        
        $backupDir  = $job->getBackupLocation()->getEffectiveDir();
        $rsnapshot  = $container->getParameter('rsnapshot');
        $logDir     = $container->get('kernel')->getLogDir();
        $tmpDir     = $container->getParameter('tmp_dir');
        $engine     = $container->get('templating');
        $idClient   = $job->getClient()->getId();
        $client     = $job->getClient();
        $idJob      = $job->getId();
        $url        = $job->getUrl();
        $retains    = $job->getPolicy()->getRetains();
        $includes   = array();
        $include    = $job->getInclude();
        if ($include) {
            $includes = explode("\n", $include);
            foreach($includes as &$theInclude) {
                $theInclude = str_replace('\ ', '?', trim($theInclude));
            }
        }
        $excludes = array();
        $exclude = $job->getExclude();
        if ($exclude) {
            $excludes = explode("\n", $exclude);
            foreach($excludes as &$theExclude) {
                $theExclude = str_replace('\ ', '?', trim($theExclude));
            }
        }
        $syncFirst = (int)$job->getPolicy()->getSyncFirst();
        $context = array('link' => $this->generateJobRoute($idJob, $idClient));
        
        $content = $engine->render(
            'default/rsnapshotconfig.txt.twig',
            array('cmdPreExec'          => '',
                'cmdPostExec'         => '',
                'excludes'            => $excludes,
                'idClient'            => sprintf('%04d', $idClient),
                'idJob'               => sprintf('%04d', $idJob),
                'includes'            => $includes,
                'backupDir'           => $backupDir,
                'retains'             => $retains,
                'tmp'                 => $tmpDir,
                'snapshotRoot'        => $job->getSnapshotRoot(),
                'syncFirst'           => $syncFirst,
                'url'                 => $url,
                'useLocalPermissions' => $job->getUseLocalPermissions(),
                'sshArgs'             => $client->getSshArgs(),
                'rsyncShortArgs'      => $client->getRsyncShortArgs(),
                'rsyncLongArgs'       => $client->getRsyncLongArgs(),
                'logDir'              => $logDir
            )
        );
        $confFileName = sprintf("%s/rsnapshot.%s_%s.cfg", $tmpDir, $idClient, $idJob);
        
        $fd = @fopen($confFileName, 'w');
        if (false === $fd) {
            $this->err('Error opening config file %filename%. Aborting backup.', array('%filename%' => $confFileName), $context);
            return self::ERR_CODE_OPEN_FILE;
        }
        $bytesWriten = fwrite($fd, $content);
        if (false === $bytesWriten) {
            $this->err('Error writing to config file %filename%. Aborting backup.', array('%filename%' => $confFileName), $context);
            return self::ERR_CODE_WRITE_FILE;
        }
        $ok = fclose($fd);
        if (false === $ok) {
            $this->warn('Error closing config file %filename%.', array('%filename%' => $confFileName), $context);
        }
        if (!is_dir($job->getSnapshotRoot())) {
            $ok = @mkdir($job->getSnapshotRoot(), 0777, true);
            if (false === $ok) {
                $this->err('Error creating snapshot root %filename%. Aborting backup.', array('%filename%' => $job->getSnapshotRoot()), $context);
                return self::ERR_CODE_CREATE_FILE;
            }
        }
        
        $ok = self::ERR_CODE_OK;
        foreach ($runnableRetains as $retain) {
            $job_starttime = time();
            // run rsnapshot. sync first if needed
            $commands = array();
            if ($job->getPolicy()->mustSync($retain)) {
                $commands[] = sprintf('"%s" -c "%s" sync 2>&1', $rsnapshot, $confFileName);
            }
            $commands[] = sprintf('"%s" -c "%s" %s 2>&1', $rsnapshot, $confFileName, $retain);
            $i=0; # Command number. Will be appended to the logfile name.
            foreach ($commands as $command) {
                $i = ++$i;
                $commandOutput = array();
                $status = self::ERR_CODE_OK;
                // Clean logfile from previous context
                unset($context['logfile']);
                $this->info('Running %command%', array('%command%' => $command), $context);
                exec($command, $commandOutput, $status);
                // Temporary logfile generated by rsnapshot (see rsnapshotconfig.txt.twig)
                $tmplogfile = sprintf('%s/tmp-c%04dj%04d.log', $logDir, $idClient, $idJob);
                
                // Ends with errors / warnings
                if (self::ERR_CODE_OK != $status) {
                    // Capture error from logfile
                    $commandOutput = $this->captureErrorFromLogfile($tmplogfile);
                    // Log output limited to 500 chars
                    if (strlen("\n" . $commandOutput) >= 500) {
                        $commandOutputString = substr("\n" . $commandOutput, 0, 500);
                        $commandOutputString = "$commandOutputString (...)";
                    } else {
                        $commandOutputString = "\n" . $commandOutput;
                    }
                    
                    // Let's save this log for debug
                    $joblogfile = sprintf('%s/jobs/c%dj%d_%s_%d.log', $logDir, $idClient, $idJob, date("YmdHis",time()),$i);
                    // Rename the logfile and link it to the log message
                    if ($this->moveLogfile($tmplogfile, $joblogfile) == True) {
                        $context['logfile'] = basename($joblogfile);
                    }
                    
                    $this->err(
                        'Command failed: %output%',
                        array('%output%'  => $commandOutputString),
                        $context
                    );
                    $ok = self::ERR_CODE_PROC_EXEC_FAILURE;
                    break;
                    // Ends successfully
                } else {
                    // Capture stats from logfile
                    $commandOutput = $this->captureStatsFromLogfile($tmplogfile);
                    if ($commandOutput){
                        $commandOutputString = implode("\n", $commandOutput);
                        // Parse rsnapshot/rsync output stats
                        preg_match('/^Number of files:(.*)$/m', $commandOutputString, $files_total);
                        preg_match('/^Number of created files:(.*)$/m', $commandOutputString, $files_created);
                        preg_match('/^Number of deleted files:(.*)$/m', $commandOutputString, $files_deleted);
                        preg_match('/^Total transferred file size:(.*)$/m', $commandOutputString, $total_transferred);
                        if (isset($files_total[1], $files_created[1], $files_deleted[1], $total_transferred[1])){
                            $commandOutput      = [];
                            $commandOutput[]    = "Number of files: ".$files_total[1];
                            $commandOutput[]    = "Number of created files: ".$files_created[1];
                            $commandOutput[]    = "Number of deleted files: ".$files_deleted[1];
                            $commandOutput[]    = "Total transferred file size: ".$total_transferred[1];
                        }
                    }
                    // DELETE tmp logfile
                    if (false === unlink($tmplogfile)) {
                        $this->warn(
                            'Error unlinking logfile %filename%.',
                            array('%filename%' => $tmplogfile),
                            $context
                        );
                    }
                    $this->info(
                        'Command succeeded. %output%',
                        array('%output%'  => implode("\n", $commandOutput)),
                        $context
                    );
                }
                
            }
            $job_endtime = time();
            if (isset($total_transferred[1])){
                $job_run_size = $total_transferred[1];
            } else {
                $job_run_size = 0;
            }
            
            $data = array();
            $data['ELKARBACKUP_JOB_RUN_SIZE']      = $job_run_size;
            $data['ELKARBACKUP_JOB_STARTTIME']     = $job_starttime;
            $data['ELKARBACKUP_JOB_ENDTIME']       = $job_endtime;

            $this->renew_db_connection();

            $queue = $container
            ->get('doctrine')
            ->getRepository('App:Queue')
            ->findOneBy(array('job' => $job));
            
            if (null == $queue) {
                $this->warn(
                    'Job data could not be stored!',
                    array(),
                    $context
                );
                
            } else {
                $queue->setData($data);
                
            }
            
        }
        if (false === unlink($confFileName)) {
            $this->warn(
                'Error unlinking config file %filename%.',
                array('%filename%' => $confFileName),
                $context
            );
        }
        
        
        /* setDiskUsage()*/
        $this->info('Client "%clientid%", Job "%jobid%" du begin.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()), $context);
        // if diskus is installed, use that as it is so much faster than plain du
        if (trim(shell_exec('command -v diskus'))) {
            $du = (int)shell_exec(sprintf("diskus '%s' | awk -F '[() ]' '{print $0 / 1024}'", $job->getSnapshotRoot()));
        } else {
            $du = (int)shell_exec(sprintf("du -ks '%s' | sed 's/\t.*//'", $job->getSnapshotRoot()));
        }
        $this->renew_db_connection();
        $job->setDiskUsage($du);
        $this->info('Client "%clientid%", Job "%jobid%" du end.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()), $context);
        
        return $ok;
    }
    
    protected function captureErrorFromLogfile($logfile){
        // Detect well known errors and return a customized error message
        // Search for common error, else return last lines (string)
        // cut -f2- -d\ => ignore first column (timestamp)
        // grep -i 'ssh:\|permission\|such file\|sigterm' => search for common errors
        $error = [];
        $command = "cat $logfile | cut -f2- -d\ | grep -i 'ssh:\|permission\|such file\|sigterm'";
        exec($command, $error, $retval);
        if ($retval != 0) {
            // If we don't find any common error, return the last 5 lines from the log
            $command2 = "cat $logfile | cut -f2- -d\ | tail -n5";
            exec($command2, $lastlines, $retval2);
            if ($retval2 == 0) {
                $error = "[unknown error] " . implode('\n', $lastlines);
            } else {
                $error = "[unknown error]";
            }
        } else {
            $error = implode('\n', $error);
        }
        return $error;
    }
    
    protected function captureStatsFromLogfile($logfile){
        // Capture rsync stats. Else, return an empty array.
        $stats = [];
        $command = "grep -B13 'speedup is' $logfile |cut -f2- -d\ ";
        exec($command, $stats, $retval);
        return $stats;
    }
    
    protected function moveLogfile($tmplogfile, $joblogfile){
        // Save jobs under job logs directory
        $retval = False;
        if (is_file($tmplogfile)) {
            $jobLogDir = dirname($joblogfile);
            if ( !is_dir($jobLogDir) ){
                // If job log directory does not exist, create it
                if (False == mkdir($jobLogDir, 0750)){
                    $retval = False;
                }
            }
            $retval = rename($tmplogfile, $joblogfile);
        } else {
            $retval = False;
        }
        return $retval;
    }
    
    protected function getNameForLogs()
    {
        return 'RunJobCommand';
    }
    
    protected function getRunnableRetains($job)
    {
        $runnableRetains = array();
        
        $queue = $this->getContainer()
        ->get('doctrine')
        ->getRepository('App:Queue')
        ->findOneBy(array('job' => $job));
        
        if (null == $queue) {
            // This should not happen, job must be in the queue!
            return $runnableRetains;
        }
        $time = $queue->getDate();
        $policy = $job->getPolicy();
        $runnableRetains = $policy->getRunnableRetains($time);
        if (count($runnableRetains) == 0){
            // Job has been enqueued on demand, not scheduled
            // We will run the lowest of all retains (the one that actually syncs)
            $retains = $policy->getRetains();
            $runnableRetains = array($retains[0][0]);
        }
        return $runnableRetains;
    }

    /*
     * Renew the database connection
     * If the connection is not alive, close and reconnect
     * Prevents a server gone away message after long jobs
     */
    protected function renew_db_connection()
    {
        // Renew the DB connection
        $em = $this->getContainer()->get('doctrine')->getManager();
        if ($em->getConnection()->ping() === false) {
            $em->getConnection()->close();
            $em->getConnection()->connect();
        }
    }
}
