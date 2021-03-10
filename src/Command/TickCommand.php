<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace App\Command;

use App\Entity\User;
use App\Entity\Job;
use App\Entity\Client;
use App\Entity\Message;
use App\Entity\Queue;
use App\Lib\Globals;
use App\Lib\LoggingCommand;
use App\Logger\LoggerHandler;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Factory;
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
    private $loggerHandler;
    
    const STATE_CLIENT_NOT_READY = 'NOT READY';
    const STATE_CLIENT_PRE = 'PRE CLIENT';
    const STATE_CLIENT_READY = 'READY';
    const STATE_CLIENT_POST = 'POST CLIENT';
    const STATE_CLIENT_ERROR = 'ERROR';
    const STATE_JOB_QUEUED = 'QUEUED';
    const STATE_JOB_WAITING_CLIENT = 'WAITING FOR CLIENT';
    const STATE_JOB_PRE = 'PRE JOB';
    const STATE_JOB_RUNNING = 'RUNNING';
    const STATE_JOB_ABORTING = 'ABORTING';
    const STATE_JOB_POST = 'POST JOB';
    
    const BACKUP_STATUS_OK = 'OK';
    const BACKUP_STATUS_FAIL = 'FAIL';
    
    public function __construct(LoggerHandler $loggerHandler, Logger $logger)
    {
        $this->loggerHandler = $loggerHandler;
        parent::__construct($logger);
    }
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
        $errors = false;
        $errors = $this->enqueueScheduledBackups($input, $output) && $errors ;
        $errors = $this->executeMessages($input, $output) && $errors;
        $errors = $this->removeOldLogs() && $errors;
        
        $logHandler = $this->loggerHandler;
        $logHandler->startRecordingMessages();
        
        $store    = new FlockStore(sys_get_temp_dir());
        $factory  = new Factory($store);
        $lockHandler = $factory->createLock('tick.lock');
        
            if ($lockHandler->acquire()) {
                try {
                    $this->initializeClients();
                    $this->initializeQueue();
                    $dql =<<<EOF
SELECT COUNT(q)
FROM App:Queue q
EOF;
                    $queueCount = $this->manager->createQuery($dql)->getSingleScalarResult();
                    
                    $dql =<<<EOF
SELECT COUNT(c)
FROM App:Client c
WHERE c.state != 'NOT READY' AND c.state != 'ERROR'
EOF;
                    $clientCount = $this->manager->createQuery($dql)->getSingleScalarResult();

                    $noCandidate = false;
                    while (($queueCount > 0 && !$noCandidate) || $clientCount > 0) {
                        /*
                         * Make sure that we don't get stalled information by cleaning all
                         * the entities in the manager. 
                         */
                        $this->manager->clear();
                        $this->processQueueElements($input, $output);
                        $this->processClients($input, $output);
                        $this->waitWithTimeout();
                        
                        $dql =<<<EOF
SELECT COUNT(q)
FROM App:Queue q
EOF;
                        $queueCount = $this->manager->createQuery($dql)->getSingleScalarResult();
                        
                        $dql =<<<EOF
SELECT COUNT(c)
FROM App:Client c
WHERE c.state != 'NOT READY' AND c.state != 'ERROR'
EOF;
                        $clientCount = $this->manager->createQuery($dql)->getSingleScalarResult();
                        
                        //check that there are no jobs that can not be executed
                        $dql =<<<EOF
SELECT COUNT(q)
FROM App:Queue q
WHERE q.state = 'QUEUED'
EOF;
                        $queuedJobs = $this->manager->createQuery($dql)->getSingleScalarResult();

                        if ($queueCount == 0) {
                            $noCandidate = true;
                        } else if ($queuedJobs == $queueCount && $noCandidate == false) {
                            $this->warn('There are jobs remaining but their configuration does not allow to execute them');
                            $noCandidate = true;
                        } else {
                            $noCandidate = false;
                        }
                    }                    
                    $this->initializeClients();
                    
                } catch (Exception $e) {
                    echo "-----ERROR: " . $e;
                    $this->err('Exception running queued commands: %exceptionmsg%', array('%exceptionmsg%' => $e->getMessage()));
                    $this->manager->flush();
                    $errors = true;
                }
                $logHandler->stopRecordingMessages();
                if (false == $errors) {
                    return self::ERR_CODE_OK;
                } else {
                    return self::ERR_CODE_UNKNOWN;
                }
            }
            $this->info('Tick Command skipped, scheduler already executing');
            $this->manager->flush();
            return self::ERR_CODE_OK;
    }
    
    protected function enqueueScheduledBackups(InputInterface $input, OutputInterface $output)
    {
        $time = $this->parseTime($input->getArgument('time'));
        if (!$time) {
            $this->err('Invalid time specified.');
            
            return self::ERR_CODE_INPUT_ARG;
        }
        $repository = $this->container->get('doctrine')->getRepository('App:Policy');
        $query = $repository->createQueryBuilder('policy')->getQuery();
        $policies = array();
        foreach ($repository->createQueryBuilder('policy')->getQuery()->getResult() as $policy) {
            $retainsToRun = $policy->getRunnableRetains($time);
            if (count($retainsToRun) > 0) {
                $policies[$policy->getId()] = $retainsToRun;
            }
        }
	    if (count($policies) == 0) {
            //Debug message disabled (issue #341)
            //$this->debug('Nothing scheduled to run.');
            return self::ERR_CODE_OK;
        }
        $policyQuery = array();
        $runnablePolicies = implode(', ', array_keys($policies));
        $dql =<<<EOF
SELECT j, c, p
FROM  App:Job j
JOIN  j.client                            c
JOIN  j.policy                            p
WHERE j.isActive = 1 AND c.isActive = 1 AND j.policy IN ($runnablePolicies)
ORDER BY j.priority, c.id
EOF;
        
        $jobs = $this->manager->createQuery($dql)->getResult();
        foreach ($jobs as $job) {
            $isQueueIn = $this->container->get('doctrine')
            ->getRepository('App:Queue')
            ->findBy(array('job' => $job));
            if (! $isQueueIn) {
                $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
                $this->info(self::STATE_JOB_QUEUED, array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
                $queue = new Queue($job);
                $queue->setDate($time);
                $this->manager->persist($queue);
            }
        }
        $this->manager->flush();
        
        return self::ERR_CODE_OK;
    }

    protected function executeMessages(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->manager->getRepository('App:Message');
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
                    $job->setStatus(self::BACKUP_STATUS_FAIL);
                }
            } else {
                $this->err('Malformed command: ' . $commandText);
            }
            $this->manager->flush();
        }
        return self::ERR_CODE_OK;
    }

    protected function processQueueElements(InputInterface $input, OutputInterface $output)
    {
        $dql =<<<EOF
SELECT q,j,c
FROM App:Queue q
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
        $clients = $this->manager->getRepository('App:Client')
        ->findAll();
        foreach($clients as $client) {
            $this->processClientState($client);
        }
    }
    
    protected function waitWithTimeout(){
       if ($this->timeoutPid == $this->awakenPid) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                exit(self::ERR_CODE_UNKNOWN);
            } elseif ($pid == 0) {
                sleep(1);
                exit(self::ERR_CODE_OK);
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

        $logHandler = $this->loggerHandler;
        $state = $task->getState();
        $job = $task->getJob();
        $context = array('link' => $this->generateJobRoute(
            $job->getId(),
            $job->getClient()->getId()
            ), 
            'source' => Globals::STATUS_REPORT
        );
        $abortStatus = $task->getAborted();
        
        switch ($state){
            case self::STATE_JOB_QUEUED:
                $this->errors[$job->getId()] = false;
                if (true == $abortStatus) {
                    $this->manager->remove($task);
                    $this->warn(
                        'Job stop requested: aborting job',
                        array(),
                        $context
                    );
                } else {
                    if ($this->isCandidate($task)) {
                        $task->setState(self::STATE_JOB_WAITING_CLIENT);
                    }
                }
                break;
                
            case self::STATE_JOB_WAITING_CLIENT:
                $clientState = $job->getClient()->getState();
                
                if (true == $abortStatus) {
                    $this->manager->remove($task);
                    $this->warn(
                        'Job stop requested: aborting job',
                        array(),
                        $context
                    );
                    
                } elseif ($clientState == self::STATE_CLIENT_READY) {
                    $task->setState(self::STATE_JOB_PRE);
                    $startTime = new DateTime();
                    $task->setRunningSince($startTime);
                    $pid = $this->runPreJobScripts($job);
                    $this->jobsPid[$job->getId()] = $pid;
                    
                } elseif ($clientState == self::STATE_CLIENT_ERROR) {
                    $this->manager->remove($task);
                    $this->err(
                        'Job aborted: pre client scripts failed!',
                        array(),
                        $context
                    );
                }
                break;
                
            case self::STATE_JOB_PRE:
                $jobPid = $this->jobsPid[$job->getId()];
                $doPost = $this->container->getParameter('post_on_pre_fail');
                
                if ($this->awakenPid == $jobPid ){
                    if ($this->awakenStatus != self::ERR_CODE_OK) {
                        $this->err(
                            'Pre job scripts failed: aborting job',
                            array(),
                            $context
                        );
                        if (true == $doPost) {
                            $task->setState(self::STATE_JOB_POST);
                            $job->setLastResult(self::BACKUP_STATUS_FAIL);
                            $this->errors[$job->getId()] = true;
                            $pid = $this->runPostJobScripts($job, self::ERR_CODE_PRE_FAIL);
                            $this->jobsPid[$job->getId()] = $pid;
                        } else {
                            $this->manager->remove($task);
                            $this->err(
                                'Job aborted',
                                array(),
                                $context
                            );
                        }
                    } elseif ($this->awakenStatus == self::ERR_CODE_OK) {
                        if (true == $abortStatus) {
                            $this->warn(
                                'Job stop requested: aborting job',
                                array(),
                                $context
                            );
                            $task->setState(self::STATE_JOB_POST);
                            $pid = $this->runPostJobScripts($job, self::ERR_CODE_NO_RUN);
                            $this->jobsPid[$job->getId()] = $pid;
                            
                        }
                        $task->setState(self::STATE_JOB_RUNNING);
                        $pid = $this->runJob($job);
                        $this->jobsPid[$job->getId()] = $pid;
                    }
                }
                break;
                
            case self::STATE_JOB_RUNNING:
                if (true == $abortStatus) {
                    $this->warn(
                        'Job stop requested: aborting job',
                        array(),
                        $context
                    );
                    $task->setState(self::STATE_JOB_ABORTING);
                }
                
                $jobPid = $this->jobsPid[$job->getId()];
                if ($this->awakenPid == $jobPid ){
                    if ($this->awakenStatus != self::ERR_CODE_OK) {
                        $this->err(
                            'Job execution failed!',
                            array(),
                            $context
                        );
                        $task->setState(self::STATE_JOB_POST);
                        $job->setLastResult(self::BACKUP_STATUS_FAIL);
                        $this->errors[$job->getId()] = true;
                        $pid = $this->runPostJobScripts($job, $this->awakenStatus);
                        $this->jobsPid[$job->getId()] = $pid;
                        
                    } elseif ($this->awakenStatus == self::ERR_CODE_OK) {
                        $task->setState(self::STATE_JOB_POST);
                        $pid = $this->runPostJobScripts($job, $this->awakenStatus);
                        $this->jobsPid[$job->getId()] = $pid;
                    }
                }
                break;
            
            case self::STATE_JOB_ABORTING:
                $this->stopJob($job);
                $task->setState(self::STATE_JOB_POST);
                
                break;
                
            case self::STATE_JOB_POST:
                $jobPid = $this->jobsPid[$job->getId()];
                if ($this->awakenPid == $jobPid) {
                    if ($this->awakenStatus != self::ERR_CODE_OK) {
                        $this->err(
                            'Post job scripts failed!',
                            array(),
                            $context
                        );
                        $job->setLastResult(self::BACKUP_STATUS_FAIL);
                        $this->manager->remove($task);
                    } elseif ($this->awakenStatus == self::ERR_CODE_OK) {
                        $errors = $this->errors[$job->getId()];
                        if ($errors) {
                            $this->warn(
                                'Job finished with errors',
                                array(),
                                $context
                            );
                            $job->setLastResult(self::BACKUP_STATUS_FAIL);
                        } else {
                            $this->info(
                                self::BACKUP_STATUS_OK,
                                array(),
                                $context
                            );
                            $job->setLastResult(self::BACKUP_STATUS_OK);
                        }
                        $this->manager->remove($task);
                    }
                }
                $clientMessages = $logHandler->getMessages();
                $this->sendNotifications($job,$clientMessages);

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
        $context = array(
            'link' => $this->generateClientRoute($client->getId()),
            'source' => Globals::STATUS_REPORT
        );
        switch ($state) {
            case self::STATE_CLIENT_NOT_READY:
                $dql =<<<EOF
SELECT COUNT(q)
FROM App:Queue q
JOIN q.job j
WHERE j.client = :clientId AND q.state = 'WAITING FOR CLIENT'
EOF;
                $query = $this->manager->createQuery($dql);
                $query->setParameter('clientId', $client->getId());
                $jobsCount = $query->getSingleScalarResult();
                if ($jobsCount > 0) {
                    $client->setState(self::STATE_CLIENT_PRE);
                    $pid = $this->runPreClientScripts($client);
                    $this->clientsPid[$client->getId()] = $pid;
                }
                break;
                
            case self::STATE_CLIENT_PRE:
                $clientPid = $this->clientsPid[$client->getId()];
                if ($this->awakenPid == $clientPid ) {
                    if ($this->awakenStatus != self::ERR_CODE_OK) {
                        $client->setState(self::STATE_CLIENT_ERROR);
                        $this->err(
                            self::BACKUP_STATUS_FAIL,
                            array(),
                            $context
                        );
                    } elseif ($this->awakenStatus == self::ERR_CODE_OK) {
                        $client->setState(self::STATE_CLIENT_READY);
                    }
                }
                break;
                
            case self::STATE_CLIENT_READY:
                $dql =<<<EOF
SELECT q,j,c
FROM App:Queue q
JOIN q.job j
JOIN j.client c
WHERE c.id = :clientId
EOF;
                $query = $this->manager->createQuery($dql);
                $query->setParameter('clientId', $client->getId());
                $queue = $query->getResult();
                if (! $queue) {
                    $client->setState(self::STATE_CLIENT_POST);
                    $pid = $this->runPostClientScripts($client);
                    $this->clientsPid[$client->getId()] = $pid;
                }
                break;
                
            case self::STATE_CLIENT_POST:
                $clientPid = $this->clientsPid[$client->getId()];
                if ($this->awakenPid == $clientPid ) {
                    if ($this->awakenStatus != self::ERR_CODE_OK) {
                        $client->setState(self::STATE_CLIENT_ERROR);
                        $this->err(
                            self::BACKUP_STATUS_FAIL,
                            array(),
                            $context
                        );
                    } elseif ($this->awakenStatus == self::ERR_CODE_OK) {
                        $client->setState(self::STATE_CLIENT_NOT_READY);
                        $this->info(
                            self::BACKUP_STATUS_OK,
                            array(),
                            $context
                        );
                    }
                }
                break;
                
            case self::STATE_CLIENT_ERROR:
                //Nothing to do, when the scheduler finishes the state will reset.
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
SELECT q,j,c,bc
FROM App:Queue q
JOIN q.job j
JOIN j.client c
JOIN j.backupLocation bc
WHERE j.isActive = 1 AND c.isActive = 1 AND q.state != 'QUEUED'
ORDER BY q.date, q.priority
EOF;
        $runningItems = $this->manager->createQuery($dql)->getResult();
        $globalRunning = count($runningItems);
        if ($globalRunning >= $globalLimit) {
            return false;
        }
        
        $perClientRunning = 0;
        $perStorageRunning = 0;
        
        foreach ($runningItems as $item) {
            $client = $item->getJob()->getClient();
            if ($client->getId() == $myClient->getId()) {
                $perClientRunning ++;
            }
            $location = $item->getJob()->getBackupLocation();
            if ($location->getId() == $myLocation->getId()) {
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
            ->getRepository('App:Client')
            ->findAll();
        foreach ($clients as $client) {
            $client->setState(self::STATE_CLIENT_NOT_READY);
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
            $q = $this->manager->createQuery('DELETE FROM App:LogRecord l WHERE l.dateTime < :minDate');
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
    
    private function runPostJobScripts($job, $status = self::ERR_CODE_OK)
    {
        $jobId = $job->getId();
        $context = array('link' => $this->generateJobRoute($jobId, $job->getClient()->getId()));
        $command = 'run_post_job_scripts';
        $pid = $this->runBackgroundCommand($command, $jobId, $context, $status);
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
    
    private function runBackgroundCommand($command, $id, $context, $status = self::ERR_CODE_OK)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            $this->err(
                'Error forking.',
                $context
            );
            //Return unknown error code because this shouldn't happen
            exit(self::ERR_CODE_UNKNOWN);
        } elseif ($pid == 0) {
            $rootDir = $this->container->get('kernel')->getProjectDir();
            $consoleCmd = $rootDir.'/bin/console';
            
            if ('run_post_job_scripts' == $command) {
                pcntl_exec($consoleCmd, array('elkarbackup:'.$command, $id, $status));
                
            } else {
                pcntl_exec($consoleCmd, array('elkarbackup:'.$command, $id));
            }
            exit(self::ERR_CODE_PROC_EXEC_FAILURE);
        }
        $this->renewDbConnection();
        return $pid;
    }
    
    protected function stopJob($job)
    {
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $logHandler = $this->loggerHandler;
        $logHandler->startRecordingMessages();
        $clientId = $job->getClient()->getId();
        $jobId    = $job->getId();
        $container = $this->getContainer();
        $repository = $container->get('doctrine')->getRepository('App:Job');
        $tmp = $container->getParameter('tmp_dir');
        $job = $repository->find($jobId);
        $context = array('link' => $this->generateJobRoute($jobId, $clientId));
        if (!$job || $job->getClient()->getId() != $clientId) {
            $this->err('No such job.');
            
            return false;
        }
        $lockfile = sprintf(
            "%s/rsnapshot.%04d_%04d.pid",
            $tmp,
            $clientId,
            $jobId);
        
        if (file_exists($lockfile)) {
            $command1 = shell_exec(sprintf("kill -TERM $(cat '%s')", $lockfile));
            $this->info('Job backup aborted successfully', array(), $context);
            $context = array(
                'link'   => $this->generateJobRoute($jobId, $clientId),
                'source' => Globals::STATUS_REPORT
            );
        } else {
            $this->warn('Cannot abort job backup: not running', array(), $context);
        }
        
        return true;
    }


    
    private function initializeQueue()
    {
        $queue = $this->manager->getRepository('App:Queue')->findAll();
        
        foreach ($queue as $task) {
            if (self::STATE_JOB_QUEUED != $task->getState()) {
                $job = $task->getJob();
                $client = $job->getClient();
                $context = array(
                    'link' => $this->generateJobRoute($job->getId(), $client->getId()),
                    'source' => Globals::STATUS_REPORT
                );
                $this->warn('Job resetting', array(), $context);
                $task->setState(self::STATE_JOB_QUEUED);
            }
        }
    }


    /**
     * Send email with error log data.
     *
     * @param Job              $job      The Job whose log we are about to send.
     * @param array(LogRecord) $messages An array of LogRecords with the error information.
     */
    
    private function sendNotifications(Job $job, $messages)
    {

        $container        = $this->getContainer();
        $adminEmail       = $container->get('doctrine')->getRepository('App:User')->find(User::SUPERUSER_ID)->getEmail();

        if ($container->hasParameter('mailer_from') && $container->getParameter('mailer_from') != "") {
          $fromEmail      = $container->getParameter('mailer_from');
        } else {
          $fromEmail      = $adminEmail;
        }

        $idClient         = $job->getClient()->getId();
        $idJob            = $job->getId();
        $translator       = $container->get('translator');
        $recipients       = array();
        $engine           = $container->get('templating');
        $filteredMessages = array();


        foreach ($messages as $aMessage) {
            if ($aMessage->getLevel() >= $job->getMinNotificationLevel()) {
                $filteredMessages[] = $aMessage;
            }
        }

        if (count($filteredMessages) && $job->getNotificationsTo()) { // we have something to send and people willing to receive it
            foreach ($job->getNotificationsTo() as $recipient) { // decode emails
                switch ($recipient) {
                case Job::NOTIFY_TO_ADMIN:
                    $recipients[] = $adminEmail;
                    break;
                case Job::NOTIFY_TO_OWNER:
                    $recipients[] = $job->getClient()->getOwner()->getEmail();
                    break;
                case Job::NOTIFY_TO_EMAIL:
                    $recipients[] = $job->getNotificationsEmail();
                    break;
                default:
                    // do nothing
                }
            }

            $message = \Swift_Message::newInstance()
                ->setSubject($translator->trans('Log for backup from job %joburl%', array('%joburl%' => $job->getUrl()), 'BinovoElkarBackup'))
                ->setFrom(array($fromEmail => 'ElkarBackup'))
                ->setTo($recipients)
                ->setBody($engine->render('App:Default:logreport.html.twig',
                                          array('base'     => gethostname(),
                                                'job'      => $job,
                                                'messages' => $filteredMessages)),
                          'text/html');
            try {
                $container->get('mailer')->send($message);
            } catch (Exception $e) {
                $this->err('Command was unable to send the notification message: %exception%', array('%exception%' => $e->getMessage()));
            }
        }
    }


}
