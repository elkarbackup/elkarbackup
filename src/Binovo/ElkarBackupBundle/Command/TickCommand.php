<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Command;

use Binovo\ElkarBackupBundle\Entity\Client;
use Binovo\ElkarBackupBundle\Entity\Job;
use Binovo\ElkarBackupBundle\Entity\Message;
use Binovo\ElkarBackupBundle\Entity\Queue;
use Binovo\ElkarBackupBundle\Entity\Script;
use Binovo\ElkarBackupBundle\Lib\Globals;
use Binovo\ElkarBackupBundle\Lib\LoggingCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;
use DateInterval;
use DateTime;
use Exception;

class TickCommand extends LoggingCommand
{
    private $timeoutPid = 0;
    private $awakenPid = 0;
    private $awakenStatus = 0;
    private $jobsPid = array();
    private $clientsPid = array();
    private $errors = array();
    private $container;
    private $manager;
    
    
    protected function configure()
    {
        parent::configure();
        $this->setName('elkarbackup:tick')
             ->setDescription('Execute queued backups.')
             ->addArgument('time'  , InputArgument::OPTIONAL, 'As by date("Y-m-d H:i")');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //BUCLE DE CONTROL
        $this->container = $this->getContainer();
        $this->manager = $this->getContainer()->get('doctrine')->getManager();
        $allOk = $this->enqueueScheduledBackups($input, $output);
        $this->executeMessages($input, $output);
        $allOk = $this->removeOldLogs() && $allOk;
        $lockHandler = new LockHandler('tick.lock');
        
        try {
            if ($lockHandler->lock()) {
                try {
                    // we don't want to miss a backup because a command fails,
                    //so catch any exception
                    $dql =<<<EOF
SELECT q
FROM BinovoElkarBackupBundle:Queue q
EOF;
                    $queueCount = count($this->manager->createQuery($dql)->getResult());
                    
                    $dql =<<<EOF
SELECT c
FROM BinovoElkarBackupBundle:Client c
WHERE c.state NOT LIKE 'NOT READY' AND c.state NOT LIKE 'ERROR'
EOF;
                    $clientCount = count($this->manager->createQuery($dql)->getResult());
                    //mientras haya entradas en cola o cliente que no esta en NOT READY ni ERROR
                    while ($queueCount > 0 || $clientCount > 0) {
                        $this->processQueueElements($input, $output);
                        $this->processClients($input, $output);
                        $this->waitWithTimeout();
                        
                        $dql =<<<EOF
SELECT q
FROM BinovoElkarBackupBundle:Queue q
EOF;
                        $queueCount = count($this->manager->createQuery($dql)->getResult());
                        
                        $dql =<<<EOF
SELECT c
FROM BinovoElkarBackupBundle:Client c
WHERE c.state NOT LIKE 'NOT READY' AND c.state NOT LIKE 'ERROR'
EOF;
                        $clientCount = count($this->manager->createQuery($dql)->getResult());
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
        $repository = $this->container->get('doctrine')->getRepository('BinovoElkarBackupBundle:Policy');
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
        $runnablePolicies = implode(', ', array_keys($policies));
        $dql =<<<EOF
SELECT j, c, p
FROM  BinovoElkarBackupBundle:Job j
JOIN  j.client                            c
JOIN  j.policy                            p
WHERE j.isActive = 1 AND c.isActive = 1 AND j.policy IN ($runnablePolicies)
ORDER BY j.priority, c.id
EOF;
        
        $jobs = $this->manager->createQuery($dql)->getResult();
        foreach ($jobs as $job) {
            $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
            $this->info('QUEUED', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
            $queue = new Queue($job);
            $this->manager->persist($queue);
        }
        $this->manager->flush();
        
        return true;
    }

    protected function executeMessages(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->manager->getRepository('BinovoElkarBackupBundle:Message');
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
            $this->manager->remove($message);
            $this->info('About to run command: ' . $commandText);
            $this->manager->flush();
            $commandAndParams = json_decode($commandText, true);
            if (is_array($commandAndParams) && isset($commandAndParams['command'])) {
                $aborted = false;
                if ($commandAndParams['command'] == 'elkarbackup:run_job') {
                    // Check if run_job command has been aborted by user
                    $idJob = $commandAndParams['job'];
                    $repository2 = $this->container->get('doctrine')->getRepository('BinovoElkarBackupBundle:Job');
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
            $this->manager->flush();
        }
    }

    protected function executeJobs(InputInterface $input, OutputInterface $output)
    {
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
        $queue = $this->manager->createQuery($dql)->getResult();
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
    
    protected function processQueueElements(InputInterface $input, OutputInterface $output)
    {
        $logHandler = $this->container->get('BnvLoggerHandler');
        $logHandler->startRecordingMessages();
        $dql =<<<EOF
SELECT q,j,c
FROM BinovoElkarBackupBundle:Queue q
JOIN q.job j
JOIN j.client c
WHERE j.isActive = 1 AND c.isActive = 1
ORDER BY q.date, q.priority
EOF;
        $queue = $this->manager->createQuery($dql)->getResult();
        foreach($queue as $task) {
            $this->processJobState($task);
        }
    }
    
    protected function processClients(InputInterface $input, OutputInterface $output)
    {
        $logHandler = $this->container->get('BnvLoggerHandler');
        $logHandler->startRecordingMessages();
        $clients = $this->manager->getRepository('BinovoElkarBackupBundle:Client')
        ->findAll();
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
    
    /**
     * Processes the queued job state
     *
     * @param Queue         $task       An item from the queue
     */
    protected function processJobState($task)
    {
        $state = $task->getState();
        $job = $task->getJob();
        switch ($state){
            case 'QUEUED':
                $this->errors[$job->getId()] = false;
                if ($task->getAborted()) {
                    //remove from queue
                    $this->manager->remove($task);
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
                        $task->setState('WAITING FOR CLIENT');
                    }
                }
                break;
                
            case 'WAITING FOR CLIENT':
                $clientState = $job->getClient()->getState();
                if ($task->getAborted()) {
                    //remove from queue
                    $this->manager->remove($task);
                    $context = array('link' => $this->generateJobRoute(
                        $job->getId(),
                        $job->getClient()->getId()
                    ));
                    $this->warn(
                        'Job stop requested: aborting job',
                        array(),
                        $context
                    );
                    
                } elseif ($clientState == 'READY') {
                    $task->setState('PRE JOB');
                    //run prejob
                    $stats = array();
                    $model = $this->prepareJobModel($job, 'PRE', $stats);
                    $pid = $this->runInBackground(function () use ($model) {
                        $this->runJobScripts($model);
                    });
                    $this->jobsPid[$job->getId()] = $pid;
                } elseif ($clientState == 'ERROR') {
                    //abort & log error
                    //remove from queue
                    $this->manager->remove($task);
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
                
            case 'PRE JOB':
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
                        $task->setState('POST JOB');
                        //run postJob
                        $stats = array();
                        $model = $this->prepareJobModel($job, 'POST', $stats);
                        $pid = $this->runInBackground(function () use ($model) {
                            $this->runJobScripts($model);
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
                        $task->setState('POST JOB');
                        $job->setLastResult('FAIL');
                        $this->errors[$job->getId()] = true;
                        //run postJob
                        $stats = array();
                        $model = $this->prepareJobModel($job, 'POST', $stats);
                        $pid = $this->runInBackground(function () use ($model) {
                            $this->runJobScripts($model);
                        });
                        $this->jobsPid[$job->getId()] = $pid;
                    } elseif ($this->awakenStatus == 0) {
                        //PreJob correct
                        $task->setState('RUNNING');
                        //run job
                        $model = $this->prepareRunJobModel($job, $runnableRetains);
                        $pid = $this->runInBackground(function () use ($model) {
                            $this->runJob($model);
                        });
                        $this->jobsPid[$job->getId()] = $pid;
                    }
                }
                break;
                
            case 'RUNNING':
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
                    $task->setState('POST JOB');
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
                        $task->setState('POST JOB');
                        $job->setLastResult('FAIL');
                        $this->errors[$job->getId()] = true;
                        //run postJob
                        $stats = array();
                        $model = $this->prepareJobModel($job, 'POST', $stats);
                        $pid = $this->runInBackground(function () use ($model) {
                            $this->runJobScripts($model);
                        });
                        $this->jobsPid[$job->getId()] = $pid;
                        
                    } elseif ($this->awakenStatus == 0) {
                        //OK, post, run
                        $task->setState('POST JOB');
                        //run postJob
                        $stats = array();
                        $model = $this->prepareJobModel($job, 'POST', $stats);
                        $pid = $this->runInBackground(function () use ($model) {
                            $this->runJobScripts($model);
                        });
                        $this->jobsPid[$job->getId()] = $pid;
                    }
                }
                break;
                
            case 'POST JOB':
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
                        $this->manager->remove($task);
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
                        $this->manager->remove($task);
                        //se actualiza job y se borra cola, se flushea?
                    }
                }
                break;
        }
        $this->manager->flush();
    }
    
    /**
     * Processes the client state
     *
     * @param   Client      $client     Client entity
     */
    protected function processClientState($client)
    {
        $state = $client->getState();
        switch ($state) {
            case 'NOT READY':
                //search queue waiting for me
                $dql =<<<EOF
SELECT COUNT(q)
FROM BinovoElkarBackupBundle:Queue q
JOIN q.job j
WHERE j.client = :clientId AND q.state LIKE 'WAITING FOR CLIENT'
EOF;
                $query = $this->manager->createQuery($dql);
                $query->setParameter('clientId', $client->getId());
                $jobsCount = $query->getSingleScalarResult();
                if ($jobsCount > 0) {
                    //if so, pasar preclient y ejecutar
                    $client->setState('PRE CLIENT');
                    //run preclient
                    $stats = array();
                    $model = $this->prepareClientModel($client, 'PRE', $stats);
                    $pid = $this->runInBackground(function () use ($model) {
                        $this->runClientPreScripts($model);
                    });
                    $this->clientsPid[$client->getId()] = $pid;
                }
                
                break;
                
            case 'PRE CLIENT':
                $clientPid = $this->clientsPid[$client->getId()];
                if ($this->awakenPid == $clientPid ) {
                    //our child has finished
                    if ($this->awakenStatus != 0) {
                        //PreClient error
                        $client->setState('ERROR');
                        $context = array('link' => $this->generateClientRoute(
                            $job->getClient()->getId()
                        ));
                        $this->err(
                            'Pre client scripts failed!',
                            array(),
                            $context
                        );
                    } elseif ($this->awakenStatus == 0) {
                        $client->setState('READY');
                    }
                }
                break;
                
            case 'READY':
                //search queue waiting for me
                $dql =<<<EOF
SELECT q,j,c
FROM BinovoElkarBackupBundle:Queue q
JOIN q.job j
JOIN j.client c
WHERE c.id = :clientId'
EOF;
                $query = $this->manager->createQuery($dql);
                $query->setParameter('clientId', $client->getId());
                $queue = $query->getResult();
                if (! $queue) {
                    //if so, pasar postclient y ejecutar
                    $client->setState('POST CLIENT');
                    //run postclient
                    $stats = array();
                    $model = $this->prepareClientModel($client, 'POST', $stats);
                    $pid = $this->runInBackground(function () use ($model) {
                        $this->runClientPostScripts($model);
                    });
                    $this->clientsPid[$client->getId()] = $pid;
                }
                break;
                
            case 'POST CLIENT':
                $clientPid = $this->clientsPid[$client->getId()];
                if ($this->awakenPid == $clientPid ) {
                    //our child has finished
                    if ($this->awakenStatus != 0) {
                        //PostClient error
                        $client->setState('ERROR');
                        $context = array('link' => $this->generateClientRoute(
                            $job->getClient()->getId()
                        ));
                        $this->err(
                            'Post client scripts failed!',
                            array(),
                            $context
                        );
                        
                    } elseif ($this->awakenStatus == 0) {
                        $client->setState('NOT READY');
                    }
                }
                break;
                
            case 'ERROR':
                
                break;
        }
        $this->manager->flush();
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
    
    /**
     * Determines if the task is candidate to be executed
     *
     * @param   Queue       $task       An item from the queue
     */
    protected function isCandidate($task) {
        $globalLimit = $this->getContainer()->getParameter('max_parallel_jobs');
        $perClientLimit = $this->getContainer()->getParameter('client_parallel_jobs');
        $perStorageLimit = $this->getContainer()->getParameter('storage_parallel_jobs');
        
        $dql =<<<EOF
SELECT q,j,c
FROM BinovoElkarBackupBundle:Queue q
JOIN q.job j
JOIN j.client c
WHERE j.isActive = 1 AND c.isActive = 1 AND q.state NOT LIKE 'QUEUED'
ORDER BY q.date, q.priority
EOF;
        $runningItems = $this->manager->createQuery($dql)->getResult();
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
    
    /**
     * Prepares the model with the necessary data to run Client scripts.
     *
     * @param   Client      $client     Client entity.
     * 
     * @param   string      $type       Defines the type of scripts the model will be used for, PRE or POST.
     * 
     * @param   string      $stats      Stats for script environment vars (not stored in DB).
     * 
     * @return array        $model      The model needed to execute scripts.
     */
    protected function prepareClientModel($client, $type, $stats)
    {
        $model = array();
        
        $model['level'] = 'CLIENT';
        $model['type'] = $type; //must be PRE or POST
        $model['clientUrl'] = $client->getUrl();
        $model['clientId'] = $client->getId();
        $model['clientRoot'] = $client->getSnapshotRoot();
        $model['status'] = 0; //status from the previous command
        $model['clientName'] = $client->getName();
        $model['clientDiskUsage'] = $client->getDiskUsage();
        $model['clientSshArgs'] = $client->getSshArgs();
        $model['scriptFiles'] = array();
        
        if ('PRE' == $type){
            $model['clientEndTime'] = 0;
            $model['clientStartTime'] = 0;
            $scripts = $client->getPreScripts();
        } elseif ('POST' == $type) {
            $model['clientEndTime'] = $stats['ELKARBACKUP_CLIENT_ENDTIME'];
            $model['clientStartTime'] = $stats['ELKARBACKUP_CLIENT_STARTTIME'];
            $scripts = $client->getPostScripts();
        }
        
        foreach ($scripts as $script) {
            array_push($model['scriptFiles'], $script->getScriptPath());
        }
        return $model;
    }
    
    /**
     * Runs client level scripts
     *
     * @param   array       $model      Contains the pertinent information to run the scripts
     */
    protected function runClientScripts($model)
    {
        $status = $model['status'];
        $commandOutput = array();
        foreach ($model['scriptFiles'] as $scriptFile) {
            $command = sprintf('env ELKARBACKUP_LEVEL="%s" ELKARBACKUP_EVENT="%s" ELKARBACKUP_URL="%s" ELKARBACKUP_ID="%s" ELKARBACKUP_PATH="%s" ELKARBACKUP_STATUS="%s" ELKARBACKUP_CLIENT_NAME="%s" ELKARBACKUP_CLIENT_TOTAL_SIZE="%s" ELKARBACKUP_CLIENT_STARTTIME="%s" ELKARBACKUP_CLIENT_ENDTIME="%s" ELKARBACKUP_SSH_ARGS="%s" sudo "%s" 2>&1',
                $model['level'],
                $model['type'],
                $model['clientUrl'],
                $model['clientId'],
                $model['clientRoot'],
                $status,
                $model['clientName'],
                $model['clientDiskUsage'],
                $model['clientStartTime'],
                $model['clientEndTime'],
                $model['clientSshArgs'],
                $scriptFile);
            exec($command, $commandOutput, $newStatus);
            $status = $newStatus;
        }

    }
    
    /**
     * Prepares the model with the necessary data to run Job scripts.
     *
     * @param   Job         $job        Job entity.
     *
     * @param   string      $type       Defines the type of scripts the model will be used for, PRE or POST.
     *
     * @param   string      $stats      Stats for script environment vars (not stored in DB).
     * 
     * @return array        $model      The model needed to execute scripts.
     */
    protected function prepareJobModel($job, $type, $stats)
    {
        $model = array();
        $client = $job->getClient();
        
        $model['level']             = 'JOB';
        $model['type']              = $type; //must be PRE or POST
        $model['clientUrl']         = $client->getUrl();
        $model['clientId']          = $client->getId();
        $model['clientRoot']        = $client->getSnapshotRoot();
        $model['status']            = 0; //status from the previous command
        $model['clientName']        = $client->getName();
        $model['jobName']           = $job->getName();
        $model['ownerEmail']        = $client->getOwner()->getEmail();
        $model['recipientList']     = $job->getNotificationsEmail();
        $model['clientDiskUsage']   = $client->getDiskUsage();
        $model['jobTotalSize']      = $job->getDiskUsage();
        $model['clientSshArgs']     = $client->getSshArgs();
        $model['scriptFiles']       = array();
        
        if ('PRE' == $type){
            $scripts = $job->getPreScripts();
            $model['jobRunSize']    = 0;
            $model['jobStartTime']  = 0;
            $model['jobEndTime']    = 0;
        } elseif ('POST' == $type) {
            $scripts = $job->getPostScripts();
            $model['jobRunSize']    = $stats['ELKARBACKUP_JOB_RUN_SIZE'];
            $model['jobStartTime']  = $stats['ELKARBACKUP_JOB_STARTTIME'];
            $model['jobEndTime']    = $stats['ELKARBACKUP_JOB_ENDTIME'];
        }

        foreach ($scripts as $script) {
            array_push($model['scriptFiles'], $script->getScriptPath());
        }
        return $model;
    }
    
    /**
     * Runs job level scripts
     * 
     * @param   array       $model      Contains the pertinent information to run the scripts
     */
    protected function runJobScripts($model)
    {
        $status = $model['status'];
        $commandOutput = array();
        foreach ($model['scriptFiles'] as $scriptFile) {
            $command = sprintf('env ELKARBACKUP_LEVEL="%s" ELKARBACKUP_EVENT="%s" ELKARBACKUP_URL="%s" ELKARBACKUP_ID="%s" ELKARBACKUP_PATH="%s" ELKARBACKUP_STATUS="%s" ELKARBACKUP_CLIENT_NAME="%s" ELKARBACKUP_JOB_NAME="%s" ELKARBACKUP_OWNER_EMAIL="%s" ELKARBACKUP_RECIPIENT_LIST="%s" ELKARBACKUP_CLIENT_TOTAL_SIZE="%s" ELKARBACKUP_JOB_TOTAL_SIZE="%s" ELKARBACKUP_JOB_RUN_SIZE="%s" ELKARBACKUP_JOB_STARTTIME="%s" ELKARBACKUP_JOB_ENDTIME="%s" ELKARBACKUP_SSH_ARGS="%s" sudo "%s" 2>&1',
                $model['level'],
                $model['type'],
                $model['clientUrl'],
                $model['clientId'],
                $model['clientRoot'],
                $status,
                $model['clientName'],
                $model['jobName'],
                $model['ownerEmail'],
                $model['recipientList'],
                $model['clientDiskUsage'],
                $model['jobTotalSize'],
                $model['clientSshArgs'],
                $scriptFile);
            exec($command, $commandOutput, $newStatus);
            $status = $newStatus;
        }
        
    }
    
    protected function prepareRunJobModel($job, $runnableRetains)
    {
        $stats[] = array();
        $model = array();
        $warnings = False;
        
        $container = $this->container;
        
        $tmpDir     = $container->getParameter('tmp_dir');
        $engine     = $container->get('templating');
        
        $model['runnableRetains']   = $runnableRetains;
        $model['logDir']            = $container->get('kernel')->getLogDir();
        $model['idClient']          = $job->getClient()->getId();
        $model['idJob']             = $job->getId();
        $model['jobPolicy']         = $job->getPolicy(); //????
        $model['rsnapshot']         = $container->getParameter('rsnapshot');
        $model['context']           = array('link' => $this->generateJobRoute($model['idJob'], $model['idClient']));
        $model['confFileName'] = sprintf("%s/rsnapshot.%s_%s.cfg", $tmpDir, $model['idClient'], $model['idJob']);
        
        $backupDir  = $job->getBackupLocation()->getEffectiveDir();
        $client     = $job->getClient();
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
        $excludes   = array();
        $exclude    = $job->getExclude();
        if ($exclude) {
            $excludes = explode("\n", $exclude);
            foreach($excludes as &$theExclude) {
                $theExclude = str_replace('\ ', '?', trim($theExclude));
            }
        }
        $syncFirst = (int)$job->getPolicy()->getSyncFirst();
        
        $content = $engine->render(
            'BinovoElkarBackupBundle:Default:rsnapshotconfig.txt.twig',
            array(
                'cmdPreExec'          => '',
                'cmdPostExec'         => '',
                'excludes'            => $excludes,
                'idClient'            => sprintf('%04d', $model['idClient']),
                'idJob'               => sprintf('%04d', $model['idJob']),
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
                'logDir'              => $model['logDir']
            )
        );
        
        
        $fd = fopen($model['confFileName'], 'w');
        if (false === $fd) {
            $this->err('Error opening config file %filename%. Aborting backup.', array('%filename%' => $model['confFileName']), $model['context']);
            
            return false;
        }
        $bytesWriten = fwrite($fd, $content);
        if (false === $bytesWriten) {
            $this->err('Error writing to config file %filename%. Aborting backup.', array('%filename%' => $model['confFileName']), $model['context']);
            
            return false;
        }
        $ok = fclose($fd);
        if (false === $ok) {
            $this->warn('Error closing config file %filename%.', array('%filename%' => $model['confFileName']), $model['context']);
        }
        if (!is_dir($job->getSnapshotRoot())) {
            $ok = mkdir($job->getSnapshotRoot(), 0777, true);
            if (false === $ok) {
                $this->err('Error creating snapshot root %filename%. Aborting backup.', array('%filename%' => $job->getSnapshotRoot()), $model['context']);
                
                return false;
            }
        }
        return $model;
    }
    
    
    /**
     * Runs a job
     *
     * @param   Array       $model      Contains the pertinent information to run the job.
     */
    protected function runJob($model)
    {
        foreach ($model['runnableRetains'] as $retain) {
            $status = 0;
            
            $job_starttime = time();
            // run rsnapshot. sync first if needed
            $commands = array();
            if ($model['jobPolicy']->mustSync($retain)) { //FUNCIONARÁ??
                $commands[] = sprintf('"%s" -c "%s" sync 2>&1', $model['rsnapshot'], $model['confFileName']);
            }
            $commands[] = sprintf('"%s" -c "%s" %s 2>&1', $model['rsnapshot'], $model['confFileName'], $retain);
            $i=0; # Command number. Will be appended to the logfile name.
            foreach ($commands as $command) {
                $i = ++$i;
                $commandOutput = array();
                $status        = 0;
                // Clean logfile from previous context
                unset($model['context']['logfile']);
                exec($command, $commandOutput, $status);
                // Temporary logfile generated by rsnapshot (see rsnapshotconfig.txt.twig)
                $tmplogfile = sprintf('%s/tmp-c%04dj%04d.log', $model['logDir'], $model['idClient'], $model['idJob']);
                
                // Ends with errors / warnings
                if (0 != $status) {
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
                    $joblogfile = sprintf('%s/jobs/c%dj%d_%s_%d.log', $model['logDir'], $model['idClient'], $model['idJob'], date("YmdHis",time()),$i);
                    // Rename the logfile and link it to the log message
                    if ($this->moveLogfile($tmplogfile, $joblogfile) == True) {
                        $model['context']['logfile'] = basename($joblogfile);
                    }
                    
                    $ok = false;
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
                            $commandOutput = [];
                            $commandOutput[] = "Number of files: ".$files_total[1];
                            $commandOutput[] = "Number of created files: ".$files_created[1];
                            $commandOutput[] = "Number of deleted files: ".$files_deleted[1];
                            $commandOutput[] = "Total transferred file size: ".$total_transferred[1];
                        }
                    }
                    // DELETE tmp logfile
                    if (false === unlink($tmplogfile)) {
                        $this->warn('Error unlinking logfile %filename%.',
                            array('%filename%' => $tmplogfile),
                            $model['context']);
                    }
                    
                    $this->info('Command succeeded. %output%',
                        array('%output%'  => implode("\n", $commandOutput)),
                        $context);
                }
                
            }
            $job_endtime = time();
            if (isset($total_transferred[1])){
                $job_run_size = $total_transferred[1];
            } else {
                $job_run_size = 0;
            }
            
            //tahoe backup
            $tahoe = $container->get('Tahoe');
            $tahoeInstalled = $tahoe->isInstalled();
            $tahoeOn = $container->getParameter('tahoe_active');
            if ($tahoeInstalled && $tahoeOn) {
                $tahoe->enqueueJob($job, $retain);
            }
        }
        if (false === unlink($confFileName)) {
            $this->warn('Error unlinking config file %filename%.',
                array('%filename%' => $confFileName),
                $context);
        }
        
        if (True === $ok) {
            if (True === $warnings) {
                $ok = 2;
            }
        }
        return $ok;
    }

    /**
     * Turn clients to their initial state of execution: Not Ready
     * 
     */
    protected function initializeClients(){
        $clients = $this->container
            ->get('doctrine')
            ->getRepository('BinovoElkarBackupBundle:Client')
            ->findAll();
        foreach ($clients as $client) {
            $client->setState('NOT READY');
        }
        $this->manager->flush();
    }
    
    protected function getNameForLogs()
    {
        return 'TickCommand';
    }
    
    protected function parseTime($time)
    {
        if (empty($time)) {
            $time = new DateTime();
        } else {
            $time = DateTime::createFromFormat("Y-m-d H:i", $time);
        }
        return $time;
    }
    
    protected function removeOldLogs()
    {
        $maxAge  = $this->container->getParameter('max_log_age');
        if (!empty($maxAge)) {
            $interval = new DateInterval($maxAge);
            $interval->invert = true;
            $q = $this->manager->createQuery('DELETE FROM BinovoElkarBackupBundle:LogRecord l WHERE l.dateTime < :minDate');
            $q->setParameter('minDate', date_add(new DateTime(), $interval));
            $numDeleted = $q->execute();
        }
        return true;
    }

}
