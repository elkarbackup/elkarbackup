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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
        $allOk = $this->executeBackups($input, $output);
        $allOk = $this->removeOldLogs() && $allOk;
        try { // we don't want to miss a backup because a command fails, so catch any exception
            $this->executeMessages($input, $output);

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

    protected function getNameForLogs()
    {
        return 'TickCommand';
    }

    protected function executeBackups(InputInterface $input, OutputInterface $output)
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
        $this->runAllJobs($jobs, $policies);

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
