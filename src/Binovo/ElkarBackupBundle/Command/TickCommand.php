<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Command;

use Binovo\ElkarBackupBundle\Entity\Client;
use Binovo\ElkarBackupBundle\Entity\Message;
use Binovo\ElkarBackupBundle\Entity\Queue;
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
        $this->container = $this->getContainer();
        $this->manager = $this->container->get('doctrine')->getManager();
        $allOk = 0;
        $allOk = $this->enqueueScheduledBackups($input, $output) && $allOk ;
        $allOk = $this->executeMessages($input, $output) && $allOk;
        $allOk = $this->removeOldLogs() && $allOk;
        
        $logHandler = $this->container->get('BnvLoggerHandler');
        $logHandler->startRecordingMessages();
        
        $lockHandler = new LockHandler('tick.lock');
        
        try {
            if ($lockHandler->lock()) {
                try {
                    // we don't want to miss a backup because a command fails,
                    //so catch any exception
                    //SET CLIENTS TO NOT READY
                    $this->initializeClients();
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
                    $this->manager->flush();
                    $allOk = false;
                }
                $logHandler->stopRecordingMessages();
                if (0 == $allOk) {
                    return 0;
                } else {
                    return $this->ERR_CODE_UNKNOWN;
                }
            }
            return $this->ERR_CODE_UNKNOWN;
        } finally {
            $lockHandler->release();
        }
    }
    
    protected function enqueueScheduledBackups(InputInterface $input, OutputInterface $output)
    {
        $time = $this->parseTime($input->getArgument('time'));
        if (!$time) {
            $this->err('Invalid time specified.');
            
            return $this->ERR_CODE_INPUT_ARG;
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
            return 0;
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
            $isQueueIn = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:Queue')
            ->findBy(array('job' => $job));
            if (! $isQueueIn) {
                $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
                $this->info('QUEUED', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
                $queue = new Queue($job);
                $this->manager->persist($queue);
            }
        }
        $this->manager->flush();
        
        return 0;
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
        return 0;
    }

    protected function processQueueElements(InputInterface $input, OutputInterface $output)
    {
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
                exit($this->ERR_CODE_UNKNOWN);
            } elseif ($pid == 0) {
                sleep(10);
                //TODO: CUANDO ACABA EL SLEEP PETA
                exit(0);
            }
            $this->renewDbConnection();
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
        $context = array('link' => $this->generateJobRoute(
            $job->getId(),
            $job->getClient()->getId()
        ));
        
        switch ($state){
            case 'QUEUED':
                $this->errors[$job->getId()] = false;
                if ($task->getAborted()) {
                    //remove from queue
                    $this->manager->remove($task);
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
                    $this->warn(
                        'Job stop requested: aborting job',
                        array(),
                        $context
                    );
                    
                } elseif ($clientState == 'READY') {
                    $task->setState('PRE JOB');
                    //run prejob
                    $pid = $this->runPreJobScripts($job);
                    $this->jobsPid[$job->getId()] = $pid;
                    
                } elseif ($clientState == 'ERROR') {
                    //abort & log error & remove from queue
                    $this->manager->remove($task);
                    $this->err(
                        'Job aborted: pre client scripts failed!',
                        array(),
                        $context
                    );
                }
                break;
                
            case 'PRE JOB':
                $jobPid = $this->jobsPid[$job->getId()];
                $doPost = $this->container->getParameter('post_on_pre_fail');
                
                if ($this->awakenPid == $jobPid ){
                    //our child has finished
                    if ($task->getAborted()) {
                        //abort
                        $this->warn(
                            'Job stop requested: aborting job',
                            array(),
                            $context
                        );
                        if (true == $doPost) {
                            $task->setState('POST JOB');
                            //run postJob
                            $pid = $this->runPostJobScripts($job, '-1');
                            $this->jobsPid[$job->getId()] = $pid;
                        } else {
                            $this->manager->remove($task);
                            $this->warn(
                                'Job aborted',
                                array(),
                                $context
                            );
                        }
                    } elseif ($this->awakenStatus != 0) {
                        //PreJob error
                        $this->err(
                            'Pre job scripts failed: aborting job',
                            array(),
                            $context
                        );
                        if (true == $doPost) {
                            $task->setState('POST JOB');
                            $job->setLastResult('FAIL');
                            $this->errors[$job->getId()] = true;
                            //run postJob
                            $pid = $this->runPostJobScripts($job, '-1');
                            $this->jobsPid[$job->getId()] = $pid;
                        } else {
                            $this->manager->remove($task);
                            $this->err(
                                'Job aborted',
                                array(),
                                $context
                            );
                        }
                    } elseif ($this->awakenStatus == 0) {
                        //PreJob correct
                        $task->setState('RUNNING');
                        //run job
                        $pid = $this->runJob($job);
                        $this->jobsPid[$job->getId()] = $pid;
                    }
                }
                break;
                
            case 'RUNNING':
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
                    $this->warn(
                        'Job stop requested: aborting job',
                        array(),
                        $context
                    );
                    $task->setState('POST JOB');
                    //COMO RECOGEMOS EL PID SI ABORTAMOS?
                    //EJECUTAMOS POST?
                    //TODO: HACER ALGO!
                }
                $jobPid = $this->jobsPid[$job->getId()];
                if ($this->awakenPid == $jobPid ){
                    if ($this->awakenStatus != 0) {
                        //Error running, log, remember, post
                        $this->err(
                            'Job execution failed!',
                            array(),
                            $context
                        );
                        $task->setState('POST JOB');
                        $job->setLastResult('FAIL');
                        $this->errors[$job->getId()] = true;
                        //run postJob
                        $pid = $this->runPostJobScripts($job, '-1');
                        $this->jobsPid[$job->getId()] = $pid;
                        
                    } elseif ($this->awakenStatus == 0) {
                        //OK, post, run
                        $task->setState('POST JOB');
                        //run postJob
                        $pid = $this->runPostJobScripts($job);
                        $this->jobsPid[$job->getId()] = $pid;
                    }
                }
                break;
                
            case 'POST JOB':
                $jobPid = $this->jobsPid[$job->getId()];
                if ($this->awakenPid == $jobPid) {
                    if ($this->awakenStatus != 0) {
                        //PostJob error, log, lastResult, remove
                        $this->err(
                            'Post job scripts failed!',
                            array(),
                            $context
                        );
                        $job->setLastResult('FAIL');
                        $this->manager->remove($task);
                    } elseif ($this->awakenStatus == 0) {
                        //success, log, lastResult rememebered, remove
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
        $context = array('link' => $this->generateClientRoute(
            $client->getId()
        ));
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
                    $pid = $this->runPreClientScripts($client);
                    $this->clientsPid[$client->getId()] = $pid;
                }
                break;
                
            case 'PRE CLIENT':
                $doPost = $this->container->getParameter('post_on_pre_fail');
                $clientPid = $this->clientsPid[$client->getId()];
                if ($this->awakenPid == $clientPid ) {
                    //our child has finished
                    if ($this->awakenStatus != 0) {
                        if (true == $doPost) {
                            $client->setState('POST CLIENT');
                            //run postClient
                            $pid = $this->runPostClientScripts($client);
                            $this->clientsPid[$client->getId()] = $pid;
                        } else {
                            //PreClient error
                            $client->setState('ERROR');
                        }
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
                    $pid = $this->runPostClientScripts($client);
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
                        $this->err(
                            'Post client scripts failed',
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

    /**
     * Determines if the task is candidate to be executed
     *
     * @param   Queue       $task       An item from the queue
     */
    protected function isCandidate($task) {
        $myClient = $task->getJob()->getClient();
        $myLocation = $task->getJob()->getBackupLocation();
        
        $globalLimit = $this->getContainer()->getParameter('max_parallel_jobs');
        $perClientLimit = $myClient->getMaxParallelJobs();
        $perStorageLimit = $myLocation->getMaxParallelJobs();
        
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
        
        foreach ($runningItems as $item) {
            $client = $item->getJob()->getClient();
            if ($client == $myClient) {
                $perClientRunning ++;
            }
            $location = $item->getJob()->getBackupLocation();
            if ($location == $myLocation) {
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

    private function renewDbConnection()
    {
        $conn = $this->manager->getConnection();
        $conn->close();
        $conn->connect();
    }
    
    private function runPreClientScripts($client)
    {
        $context = array('link' => $this->generateClientRoute($client->getId()));
        $clientId = $client->getId();
        $command = 'run_pre_client_scripts';
        $pid = $this->runBackgroundCommand($command, $clientId, $context);
        return $pid;
    }
    
    private function runPostClientScripts($client)
    {
        $context = array('link' => $this->generateClientRoute($client->getId()));
        $clientId = $client->getId();
        $command = 'run_post_client_scripts';
        $pid = $this->runBackgroundCommand($command, $clientId, $context);
        return $pid;
    }
    
    private function runPreJobScripts($job)
    {
        $jobId = $job->getId();
        $context = array('link' => $this->generateJobRoute($jobId, $job->getClient()->getId()));
        $command = 'run_pre_job_scripts';
        $pid = $this->runBackgroundCommand($command, $jobId, $context);
        return $pid;
    }
    
    private function runPostJobScripts($job)
    {
        $jobId = $job->getId();
        $context = array('link' => $this->generateJobRoute($jobId, $job->getClient()->getId()));
        $command = 'run_post_job_scripts';
        $pid = $this->runBackgroundCommand($command, $jobId, $context);
        return $pid;
    }
    
    private function runJob($job)
    {
        $jobId = $job->getId();
        $context = array('link' => $this->generateJobRoute($jobId, $job->getClient()->getId()));
        $command = 'run_job';
        $pid = $this->runBackgroundCommand($command, $jobId, $context);
        return $pid;
    }
    
    private function runBackgroundCommand($command, $id, $context)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            $this->err(
                'Error forking.',
                $context
            );
            //Return unknown error code because this shouldn't happen
            exit($this->ERR_CODE_UNKNOWN);
        } elseif ($pid == 0) {
            $rootDir = $this->container->get('kernel')->getRootDir();
            $consoleCmd = $rootDir.'/console';
            pcntl_exec($consoleCmd, array('elkarbackup:'.$command));
            exit($this->ERR_CODE_PROC_EXEC_FAILURE);
        }
        $this->renewDbConnection();
        return $pid;
    }
}
