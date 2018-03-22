<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Command;

use Binovo\ElkarBackupBundle\Entity\Message;
use Binovo\ElkarBackupBundle\Entity\Queue;
use Binovo\ElkarBackupBundle\Lib\Globals;
use Binovo\ElkarBackupBundle\Lib\LoggingCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;
use Exception;

class ExecTickCommand extends LoggingCommand
{
    private $timeoutPid = 0;
    private $awakenPid = 0;
    private $awakenStatus = 0;
    private $jobsPid = array();
    private $clientsPid = array();
    private $errors = array();
    
    protected function configure()
    {
        parent::configure();
        $this->setName('elkarbackup:execTick')
             ->setDescription('Execute queued backups.')
             ->addArgument('time'  , InputArgument::OPTIONAL, 'As by date("Y-m-d H:i")');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //BUCLE DE CONTROL
        $allOk = $this->enqueueScheduledBackups($input, $output);
        $this->executeMessages($input, $output);
        $lockHandler = new LockHandler('tick.lock');
        
        try {
            if ($lockHandler->lock()) {
                $allOk = $this->removeOldLogs() && $allOk;
                try {
                    // we don't want to miss a backup because a command fails,
                    //so catch any exception
                    $container = $this->getContainer()->get('doctrine')->getManager();
                    $dql =<<<EOF
SELECT q
FROM BinovoElkarBackupBundle:Queue q
EOF;
                    $queueCount = count($manager->createQuery($dql)->getResult());
                    
                    $dql =<<<EOF
SELECT c
FROM BinovoElkarBackupBundle:Client c
WHERE c.state NOT LIKE 'NotReady' AND c.state NOT LIKE 'Error'
EOF;
                    $clientCount = count($manager->createQuery($dql)->getResult());
                    //mientras haya entradas en cola o cliente que no esta en NOT READY ni ERROR
                    while ($queueCount > 0 || $clientCount > 0) {
                        $this->updateQueueElements($input, $output);
                        $this->waitWithTimeout();
                        
                        $dql =<<<EOF
SELECT q
FROM BinovoElkarBackupBundle:Queue q
EOF;
                        $queueCount = count($manager->createQuery($dql)->getResult());
                        
                        $dql =<<<EOF
SELECT c
FROM BinovoElkarBackupBundle:Client c
WHERE c.state NOT LIKE 'NotReady' AND c.state NOT LIKE 'Error'
EOF;
                        $clientCount = count($manager->createQuery($dql)->getResult());
                    }
                    
                    //SET CLIENTS TO NOT READY
                    $this->initializeClients();
                    
                    //last but not least, backup @tahoe
                    $this->getContainer()->get('Tahoe')->runAllQueuedJobs();
                } catch (Exception $e) {
                    echo "-----ERROR: " . $e;
                    $this->err('Exception running queued commands: %exceptionmsg%', array('%exceptionmsg%' => $e->getMessage()));
                    $this->getContainer()->get('doctrine')->getManager()->flush();
                    $allOk = false;
                }
                return $allOk;
            }
            return false;
        } finally {
            $lockHandler->release();
        }
    }
    
    protected function enqueueScheduledBackups(InputInterface $input, OutputInterface $output)
    {
        $logHandler = $this->getContainer()->get('BnvLoggerHandler');
        $logHandler->startRecordingMessages();
        $time = $this->parseTime($input->getArgument('time'));
        if (!$time) {
            $this->err('Invalid time specified.');
            
            return false;
        }
        $container = $this->getContainer();
        $repository = $container->get('doctrine')->getRepository('BinovoElkarBackupBundle:Policy');
        $query = $repository->createQueryBuilder('policy')->getQuery();
        $policies = array();
        foreach ($repository->createQueryBuilder('policy')->getQuery()->getResult() as $policy) {
            $retainsToRun = $policy->getRunnableRetains($time);
            if (count($retainsToRun) > 0) {
                $policies[$policy->getId()] = $retainsToRun;
            }
        }
        if (count($policies) == 0) {
            $this->info('Nothing to run.');
            
            return true;
        }
        $policyQuery = array();
        $manager = $container->get('doctrine')->getManager();
        $runnablePolicies = implode(', ', array_keys($policies));
        $dql =<<<EOF
SELECT j, c, p
FROM  BinovoElkarBackupBundle:Job j
JOIN  j.client                            c
JOIN  j.policy                            p
WHERE j.isActive = 1 AND c.isActive = 1 AND j.policy IN ($runnablePolicies)
ORDER BY j.priority, c.id
EOF;
        
        $jobs = $manager->createQuery($dql)->getResult();
        foreach ($jobs as $job) {
            $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
            $this->info('QUEUED', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
            $queue = new Queue($job);
            $manager->persist($queue);
        }
        $manager->flush();
        
        return true;
    }

    protected function executeMessages(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $manager = $container->get('doctrine')->getManager();
        $repository = $manager->getRepository('BinovoElkarBackupBundle:Message');
        while (true) {
            /*
             * read messages one by one and remove them from the queue
             * as soon as read so that if any command takes too long
             * and the next invocation of the tick command starts
             * running it won't see the commands that are already in
             * process.
             */
            $message = $repository->createQueryBuilder('m')
            ->where("m.to = 'TickCommand'")
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->setMaxResults(1)
            ->getResult();
            if (count($message) == 0) {
                break;
            }
            $message = $message[0];
            $commandText = $message->getMessage();
            $manager->remove($message);
            $this->info('About to run command: ' . $commandText);
            $manager->flush();
            $commandAndParams = json_decode($commandText, true);
            if (is_array($commandAndParams) && isset($commandAndParams['command'])) {
                $aborted = false;
                if ($commandAndParams['command'] == 'elkarbackup:run_job') {
                    // Check if run_job command has been aborted by user
                    $idJob = $commandAndParams['job'];
                    $container = $this->getContainer();
                    $repository2 = $container->get('doctrine')->getRepository('BinovoElkarBackupBundle:Job');
                    $job = $repository2->find($idJob);
                    if (null == $job) {
                        throw $this->createNotFoundException($this->trans('Unable to find Job entity:') . $idJob);
                    }
                    if ($job->getStatus() == 'ABORTED'){
                        $aborted = true;
                        $this->info('Command aborted by user: ' . $commandText);
                    }
                }
                if (!$aborted) {
                    try {
                        $command = $this->getApplication()->find($commandAndParams['command']);
                        $input = new ArrayInput($commandAndParams);
                        $status = $command->run($input, $output);
                        if (0 == $status) {
                            $this->info('Command success: ' . $commandText);
                        } else {
                            $this->err('Command failure: ' . $commandText);
                        }
                    } catch (Exception $e) {
                        $idClient = $commandAndParams['client'];
                        $context = array('link' => $this->generateJobRoute($idJob, $idClient));
                        $this->err('Exception %exceptionmsg% running command %command%: ', array('%exceptionmsg%' => $e->getMessage(), '%command%' => $commandText), $context);
                        $job->setStatus('FAIL');
                    }
                }
            } else {
                $this->err('Malformed command: ' . $commandText);
            }
            $manager->flush();
        }
    }

    protected function executeJobs(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $manager = $container->get('doctrine')->getManager();
        $logHandler = $this->getContainer()->get('BnvLoggerHandler');
        $logHandler->startRecordingMessages();
        $dql =<<<EOF
SELECT q,j,c
FROM BinovoElkarBackupBundle:Queue q
JOIN q.job j
JOIN j.client c
WHERE j.isActive = 1 AND c.isActive = 1
ORDER BY q.date, q.priority
EOF;
        $queue = $manager->createQuery($dql)->getResult();
        $retainsToRun = array();
        
        foreach($queue as $task){
            $policy = $task->getJob()->getPolicy();
            if (!$policy) {
                $this->warn('Job %jobid% has no policy', array('%jobid%' => $task->getJob()->getId()));
                
                return false;
            }
            $retains = $policy->getRetains();
            if (empty($retains)) {
                $this->warn('Policy %policyid% has no active retains', array('%policyid%' => $policy->getId()));
                
                return false;
            }
            $retainsToRun[$policy->getId()] = array($retains[0][0]);
        }
        
        $this->runAllJobs($queue, $retainsToRun);
        
        return true;
    }
    
    protected function updateQueueElements(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $manager = $container->get('doctrine')->getManager();
        $logHandler = $this->getContainer()->get('BnvLoggerHandler');
        $logHandler->startRecordingMessages();
        $dql =<<<EOF
SELECT q,j,c
FROM BinovoElkarBackupBundle:Queue q
JOIN q.job j
JOIN j.client c
WHERE j.isActive = 1 AND c.isActive = 1
ORDER BY q.date, q.priority
EOF;
        $queue = $manager->createQuery($dql)->getResult();
        $clients = array();
        foreach($queue as $task) {
            $this->processJobState($task);
            $client = $task->getJob()->getClient();
            if (! in_array($client, $clients)) {
                array_push($clients, $client);
            }
        }
        
        foreach($clients as $client) {
            $this->processClientState($client);
        }
    }
    
    protected function waitWithTimeout(){
        if ($this->timeoutPid == $this->awakenPid) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                exit('could not fork');
            } elseif ($pid == 0) {
                sleep(1);
                exit(0);
            }
            $this->timeoutPid = $pid;
        }
        $this->awakenPid = pcntl_wait($status);
        $this->awakenStatus = $status;
    }
    
    /*
     * Processes the queued item state
     *
     * @param Queue $task An item from the queue
     */
    protected function processJobState($task)
    {
        $state = $task->getState();
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $job = $task->getJob();
        switch ($state){
            case 'Queued':
                $this->errors[$job->getId()] = false;
                if ($task->getAborted()) {
                    //remove from queue
                    $manager->remove($task);
                    $context = array('link' => $this->generateJobRoute(
                        $task->getJob()->getId(),
                        $job->getClient()->getId()
                    ));
                    $this->warn(
                        'Job stop requested: aborting job',
                        array(),
                        $context
                    );
                } else {
                    //if it is candidate
                    if ($this->isCandidate($task)) {
                        $task->setState('Waiting4Client');
                    }
                }
                break;
                
            case 'Waiting4Client':
                $clientState = $job->getClient()->getState();
                if ($task->getAborted()) {
                    //remove from queue
                    $manager->remove($task);
                    $context = array('link' => $this->generateJobRoute(
                        $job->getId(),
                        $job->getClient()->getId()
                    ));
                    $this->warn(
                        'Job stop requested: aborting job',
                        array(),
                        $context
                    );
                    
                } elseif ($clientState == 'Ready') {
                    $task->setState('PreJob');
                    //run prejob
                    $pid = $this->runInBackground(function () use ($job) {
                        $this->runJobPreScripts($job);
                    });
                    $this->jobsPid[$job->getId()] = $pid;
                } elseif ($clientState == 'Error') {
                    //abort & log error
                    //remove from queue
                    $manager->remove($task);
                    $context = array('link' => $this->generateJobRoute(
                        $job->getId(),
                        $job->getClient()->getId()
                    ));
                    $this->err(
                        'Job aborted: pre client scripts failed!',
                        array(),
                        $context
                    );
                }
                break;
                
            case 'PreJob':
                $jobPid = $this->jobsPid[$job->getId()];
                if ($this->awakenPid == $jobPid ){
                    //our child has finished
                    if ($task->getAborted()) {
                        //abort
                        $context = array('link' => $this->generateJobRoute(
                            $job->getId(),
                            $job->getClient()->getId()
                        ));
                        $this->warn(
                            'Job stop requested: aborting job',
                            array(),
                            $context
                        );
                        $task->setState('PostJob');
                        //run postJob
                        $pid = $this->runInBackground(function () use ($job) {
                            $this->runJobPostScripts($job);
                        });
                        $this->jobsPid[$job->getId()] = $pid;
                    } elseif ($this->awakenStatus != 0) {
                        //PreJob error
                        $context = array('link' => $this->generateJobRoute(
                            $job->getId(),
                            $job->getClient()->getId()
                        ));
                        $this->err(
                            'Pre job scripts failed: aborting job',
                            array(),
                            $context
                        );
                        $task->setState('PostJob');
                        $job->setLastResult('FAIL');
                        $this->errors[$job->getId()] = true;
                        //run postJob
                        $pid = $this->runInBackground(function () use ($job) {
                            $this->runJobPostScripts($job);
                        });
                        $this->jobsPid[$job->getId()] = $pid;
                    } elseif ($this->awakenStatus == 0) {
                        //PreJob correct
                        $task->setState('Running');
                        //run job
                        $pid = $this->runInBackground(function () use ($job) {
                            $this->runJob($job);
                        });
                        $this->jobsPid[$job->getId()] = $pid;
                    }
                }
                break;
                
            case 'Running':
                //Problema cada segundo se va a crear...
                if ($task->getAborted()) {
                    //Abort, kill, log, post
                    $msg = new Message(
                        'DefaultController',
                        'TickCommand',
                        json_encode(array(
                            'command' => 'elkarbackup:stop_job',
                            'client' => $job->getClient()->getId(),
                            'job' => $job->getId()
                        ))
                        );
                    $em->persist($msg);
                    $context = array('link' => $this->generateJobRoute(
                        $job->getId(),
                        $job->getClient()->getId()
                        ));
                    $this->warn(
                        'Job stop requested: aborting job',
                        array(),
                        $context
                        );
                    $task->setState('PostJob');
                    //COMO RECOGEMOS EL PID SI ABORTAMOS?
                    //EJECUTAMOS POST?
                }
                $jobPid = $this->jobsPid[$job->getId()];
                if ($this->awakenPid == $jobPid ){
                    if ($this->awakenStatus != 0) {
                        //Error running, log, remember, post
                        $context = array('link' => $this->generateJobRoute(
                            $job->getId(),
                            $job->getClient()->getId()
                            ));
                        $this->err(
                            'Job execution failed!',
                            array(),
                            $context
                            );
                        $task->setState('PostJob');
                        $job->setLastResult('FAIL');
                        $this->errors[$job->getId()] = true;
                        //run postJob
                        $pid = $this->runInBackground(function () use ($job) {
                            $this->runJobPostScripts($job);
                        });
                        $this->jobsPid[$job->getId()] = $pid;
                    } elseif ($this->awakenStatus == 0) {
                        //OK, post, run
                        $task->setState('PostJob');
                        //run postJob
                        $pid = $this->runInBackground(function () use ($job) {
                            $this->runJobPostScripts($job);
                        });
                            $this->jobsPid[$job->getId()] = $pid;
                    }
                }
                break;
                
            case 'PostJob':
                $jobPid = $this->jobsPid[$job->getId()];
                if ($this->awakenPid == $jobPid ){
                    if ($this->awakenStatus != 0) {
                        //PostJob error, log, lastResult, remove
                        $context = array('link' => $this->generateJobRoute(
                            $job->getId(),
                            $job->getClient()->getId()
                        ));
                        $this->err(
                            'Post job scripts failed!',
                            array(),
                            $context
                        );
                        $job->setLastResult('FAIL');
                        $manager->remove($task);
                    } elseif ($this->awakenStatus == 0) {
                        //success, log, lastResult rememebered, remove
                        $context = array('link' => $this->generateJobRoute(
                            $job->getId(),
                            $job->getClient()->getId()
                        ));
                        $errors = $this->errors[$job->getId()];
                        if ($errors) {
                            $this->warn(
                                'Job finished with errors',
                                array(),
                                $context
                            );
                        } else {
                            $this->info(
                                'Job successfully finished!',
                                array(),
                                $context
                            );
                        }
                        $job->setLastResult('OK');
                        $manager->remove($task);
                        //se actualiza job y se borra cola, se flushea?
                    }
                }
                break;
                
        }
        $em->flush();
    }
    
    protected function processClientState($client)
    {
        $state = $client->getState();
        $manager = $this->getContainer()->get('doctrine')->getManager();
        switch ($state) {
            case 'NotReady':
                //search queue waiting for me
                $dql =<<<EOF
SELECT q,j,c
FROM BinovoElkarBackupBundle:Queue q
JOIN q.job j
JOIN j.client c
WHERE c.id = :clientId AND j.state LIKE 'Waiting4Client'
EOF;
                $query = $manager->createQuery($dql);
                $query->setParameter('clientId', $client->getId());
                $queue = $query->getResult();
                if ($queue) {
                    //if so, pasar preclient y ejecutar
                    $client->setState('PreClient');
                    //run preclient
                    $pid = $this->runInBackground(function () use ($client) {
                        $this->runClientPreScripts($client);
                    });
                    $this->clientsPid[$client->getId()] = $pid;
                }
                
                break;
                
            case 'PreClient':
                $clientPid = $this->clientsPid[$client->getId()];
                if ($this->awakenPid == $clientPid ) {
                    //our child has finished
                    if ($this->awakenStatus != 0) {
                        //PreClient error
                        $client->setState('Error');
                        $context = array('link' => $this->generateClientRoute(
                            $job->getClient()->getId()
                        ));
                        $this->err(
                            'Pre client scripts failed!',
                            array(),
                            $context
                        );
                    } elseif ($this->awakenStatus == 0) {
                        $client->setState('Ready');
                    }
                }
                break;
                
            case 'Ready':
                //search queue waiting for me
                $dql =<<<EOF
SELECT q,j,c
FROM BinovoElkarBackupBundle:Queue q
JOIN q.job j
JOIN j.client c
WHERE c.id = :clientId'
EOF;
                $query = $manager->createQuery($dql);
                $query->setParameter('clientId', $client->getId());
                $queue = $query->getResult();
                if (! $queue) {
                    //if so, pasar postclient y ejecutar
                    $client->setState('PostClient');
                    //run preclient
                    $pid = $this->runInBackground(function () use ($client) {
                        $this->runClientPostScripts($client);
                    });
                    $this->clientsPid[$client->getId()] = $pid;
                }
                break;
                
            case 'PostClient':
                $clientPid = $this->clientsPid[$client->getId()];
                if ($this->awakenPid == $clientPid ) {
                    //our child has finished
                    if ($this->awakenStatus != 0) {
                        //PostClient error
                        $client->setState('Error');
                        $context = array('link' => $this->generateClientRoute(
                            $job->getClient()->getId()
                            ));
                        $this->err(
                            'Post client scripts failed!',
                            array(),
                            $context
                            );
                        
                    } elseif ($this->awakenStatus == 0) {
                        $client->setState('NotReady');
                    }
                }
                break;
                
            case 'Error':
                
                break;
        }
        $manager->flush();
    }
    
    private function runInBackground(callable $func)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            exit('could not fork');
        } elseif ($pid == 0) {
            call_user_func($func);
            exit(0);
        }
        return $pid;
    }
    
    /*
     * Determines if the task is candidate to be executed
     *
     * @param Queue $task      An item from the queue
     */
    protected function isCandidate($task) {
        $globalLimit = $this->getContainer()->getParameter('max_parallel_jobs');
        $perClientLimit = $this->getContainer()->getParameter('client_parallel_jobs');
        $perStorageLimit = $this->getContainer()->getParameter('storage_parallel_jobs');
        
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $dql =<<<EOF
SELECT q,j,c
FROM BinovoElkarBackupBundle:Queue q
JOIN q.job j
JOIN j.client c
WHERE j.isActive = 1 AND c.isActive = 1 AND q.state NOT LIKE 'Queued'
ORDER BY q.date, q.priority
EOF;
        $runningItems = $manager->createQuery($dql)->getResult();
        $globalRunning = count($runningItems);
        $perClientRunning = 0;
        $perStorageRunning = 0;
        
        $myClient = $task->getJob()->getClient();
        $myStorage = $task->getJob()->getBackupLocation();
        foreach ($runningItems as $item) {
            $client = $item->getJob()->getClient();
            if ($client == $myClient) {
                $perClientRunning ++;
            }
            $location = $item->getJob()->getBackupLocation();
            if ($location == $myStorage) {
                $perStorageRunning ++;
            }
        }
        
        if ($globalRunning < $globalLimit &&
            $perClientRunning < $perClientLimit &&
            $perStorageRunning < $perStorageLimit) {
                return true;
            } else {
                return false;
            }
    }
    
    protected function runJobPreScripts($job)
    {
        $allOk = true;
        $stats[] = array();
        $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
        foreach ($job->getPreScripts() as $script) {
            if ($this->runScript('pre', 0, $job->getClient(), $job, $script, $stats)) {
                $this->info('Job "%jobid%" pre script ok.', array('%jobid%' => $job->getId()), $context);
            } else {
                $this->err('Job "%jobid%" pre script error.', array('%jobid%' => $job->getId()), $context);
                $alOk = false;
            }
        }
        return $allOk;
    }
    
    protected function runJobPostScripts($job)
    {
        $allOk = true;
        $stats[] = array();
        $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
        foreach ($job->getPostScripts() as $script) {
            if ($this->runScript('post', 0, $job->getClient(), $job, $script, $stats)) {
                $this->info('Job "%jobid%" post script ok.', array('%jobid%' => $job->getId()), $context);
            } else {
                $this->err('Job "%jobid%" post script error.', array('%jobid%' => $job->getId()), $context);
                $alOk = false;
            }
        }
        return $allOk;
    }
    
    protected function runClientPreScripts($client)
    {
        
    }
    
    protected function runClientPostScripts($client)
    {
        
    }
    
    protected function runJob($job)
    {
        
    }
    
    /**
     * Runs a client level pre or post script.
     *
     * @param  string   $type       Either "pre" or "post".
     *
     * @param  int      $status     Status of previos command.
     *
     * @param  Client   $client     Client entity
     *
     * @param  Job      $job        Job entity. Null if running at the client level.
     *
     * @param  Script   $script     Script entity
     *
     * @param  string   $stats      Stats for script environment vars (not stored in DB)
     *
     * @return boolean  true on success, false on error.
     *
     */
    protected function runScript($type, $status, $client, $job, $script, $stats)
    {
        if ($script === null) {
            return true;
        }
        if (null == $job) {
            $entity = $client;
            $context          = array('link' => $this->generateClientRoute($client->getId()));
            $errScriptError   = 'Client "%entityid%" %scripttype% script "%scriptname%" execution failed. Diagnostic information follows: %output%';
            $errScriptMissing = 'Client "%entityid%" %scripttype% script "%scriptname%" present but file "%scriptfile%" missing.';
            $errScriptOk      = 'Client "%entityid%" %scripttype% script "%scriptname%" execution succeeded. Output follows: %output%';
            $level            = 'CLIENT';
            // Empty vars (only available under JOB level)
            $job_name         = '';
            $owner_email      = '';
            $recipient_list   = '';
            $job_total_size   = 0;
            $job_run_size     = 0;
            $job_starttime    = 0;
            $job_endtime      = 0;
            if ($type == 'post') {
                $client_endtime   = $stats['ELKARBACKUP_CLIENT_ENDTIME'];
                $client_starttime = $stats['ELKARBACKUP_CLIENT_STARTTIME'];
            } else {
                $client_endtime   = 0;
                $client_starttime = 0;
            }
        } else {
            $entity = $job;
            $context          = array('link' => $this->generateJobRoute($job->getId(), $client->getId()));
            $errScriptError   = 'Job "%entityid%" %scripttype% script "%scriptname%" execution failed. Diagnostic information follows: %output%';
            $errScriptMissing = 'Job "%entityid%" %scripttype% script "%scriptname%" present but file "%scriptfile%" missing.';
            $errScriptOk      = 'Job "%entityid%" %scripttype% script "%scriptname%" execution succeeded. Output follows: %output%';
            $level            = 'JOB';
            $job_name         = $job->getName();
            $owner_email      = $job->getClient()->getOwner()->getEmail();
            $recipient_list   = $job->getNotificationsEmail();
            $job_total_size   = $job->getDiskUsage();
            $client_starttime = 0;
            $client_endtime   = 0;
            if ($type == 'post') {
                $job_run_size     = $stats['ELKARBACKUP_JOB_RUN_SIZE'];
                $job_starttime    = $stats['ELKARBACKUP_JOB_STARTTIME'];
                $job_endtime      = $stats['ELKARBACKUP_JOB_ENDTIME'];
            } else {
                $job_run_size     = 0;
                $job_starttime    = 0;
                $job_endtime      = 0;
            }
        }
        $scriptName = $script->getName();
        $scriptFile = $script->getScriptPath();
        if (!file_exists($scriptFile)) {
            $this->err($errScriptMissing,
                array('%entityid%'   => $entity->getId(),
                    '%scriptfile%' => $scriptFile,
                    '%scriptname%' => $scriptName,
                    '%scripttype%' => $type),
                $context);
            
            return false;
        }
        $commandOutput = array();
        $command       = sprintf('env ELKARBACKUP_LEVEL="%s" ELKARBACKUP_EVENT="%s" ELKARBACKUP_URL="%s" ELKARBACKUP_ID="%s" ELKARBACKUP_PATH="%s" ELKARBACKUP_STATUS="%s" ELKARBACKUP_CLIENT_NAME="%s" ELKARBACKUP_JOB_NAME="%s" ELKARBACKUP_OWNER_EMAIL="%s" ELKARBACKUP_RECIPIENT_LIST="%s" ELKARBACKUP_CLIENT_TOTAL_SIZE="%s" ELKARBACKUP_JOB_TOTAL_SIZE="%s" ELKARBACKUP_JOB_RUN_SIZE="%s" ELKARBACKUP_CLIENT_STARTTIME="%s" ELKARBACKUP_CLIENT_ENDTIME="%s" ELKARBACKUP_JOB_STARTTIME="%s" ELKARBACKUP_JOB_ENDTIME="%s" ELKARBACKUP_SSH_ARGS="%s" sudo "%s" 2>&1',
            $level,
            'pre' == $type ? 'PRE' : 'POST',
            $entity->getUrl(),
            $entity->getId(),
            $entity->getSnapshotRoot(),
            $status,
            $client->getName(),
            $job_name,
            $owner_email,
            $recipient_list,
            $client->getDiskUsage(),
            $job_total_size,
            $job_run_size,
            $client_starttime,
            $client_endtime,
            $job_starttime,
            $job_endtime,
            $client->getSshArgs(),
            $scriptFile);
        exec($command, $commandOutput, $status);
        
        $commandOutputString = substr("\n" . implode("\n", $commandOutput), 0, 500); // Let's limit the output
        if (0 != $status) {
            $this->err($errScriptError,
                array('%entityid%'   => $entity->getId(),
                    '%output%'     => $commandOutputString,
                    '%scriptname%' => $scriptName,
                    '%scripttype%' => $type),
                $context);
            
            return false;
        }
        $this->info($errScriptOk,
            array('%entityid%'   => $entity->getId(),
                '%output%'     => $commandOutputString,
                '%scriptname%' => $scriptName,
                '%scripttype%' => $type),
            $context);
        
        return true;
    }
    
    protected function initializeClients(){
        $container = $this->getContainer()->get('doctrine');
        $manager = $container->getManager();
        $clients = $container
            ->getRepository('BinovoElkarBackupBundle:Client')
            ->findAll();
        foreach ($clients as $client) {
            $client->setState('NotReady');
        }
        $manager->flush();
    }
    
    protected function getNameForLogs()
    {
        
    }

}
