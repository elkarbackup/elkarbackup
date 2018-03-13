<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Command;

use \DateInterval;
use \DateTime;
use \Exception;
use Binovo\ElkarBackupBundle\Entity\Job;
use Binovo\ElkarBackupBundle\Lib\BackupRunningCommand;
use Binovo\ElkarBackupBundle\Lib\Globals;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

class TickCommand extends BackupRunningCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('elkarbackup:tick')
             ->setDescription('Look for backup jobs to execute')
             ->addArgument('time'  , InputArgument::OPTIONAL, 'As by date("Y-m-d H:i")');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $allOk = $this->enqueueScheduledBackups($input, $output);
        $lockHandler = new LockHandler('tick.lock');
        try {
            if ($lockHandler->lock()) {
                $allOk = $this->removeOldLogs() && $allOk;
                try { // we don't want to miss a backup because a command fails, so catch any exception
                    //$this->executeMessages($input, $output);
                    $this->executeJobs($input, $output);
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

    protected function getNameForLogs()
    {
        return 'TickCommand';
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
        //$this->runAllJobs($jobs, $policies);
        
        //Enqueue jobs
        foreach ($jobs as $job) {
            $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
            $this->info('QUEUED', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
            $job->setLastResult('QUEUED');
        }
        $manager->flush();

        return true;
    }

    protected function removeOldLogs()
    {
        $container = $this->getContainer();
        $manager = $container->get('doctrine')->getManager();
        $maxAge  = $container->getParameter('max_log_age');
        if (!empty($maxAge)) {
            $interval = new DateInterval($maxAge);
            $interval->invert = true;
            $q = $manager->createQuery('DELETE FROM BinovoElkarBackupBundle:LogRecord l WHERE l.dateTime < :minDate');
            $q->setParameter('minDate', date_add(new DateTime(), $interval));
            $numDeleted = $q->execute();
        }
        return true;
    }
}
