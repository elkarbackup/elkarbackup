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

class RunJobCommand extends BackupRunningCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('elkarbackup:run_job')
             ->setDescription('Run specified job. Runs the lowest of all retains (the one that actually syncs)')
             ->addArgument('client', InputArgument::REQUIRED, 'clientId')
             ->addArgument('job'   , InputArgument::REQUIRED, 'jobId');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $result = $this->executeJob($input, $output);
        $manager->flush();
        if ($result) {

            return 0;
        } else {

            return 1;
        }
    }

    protected function executeJob(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $logHandler = $this->getContainer()->get('BnvLoggerHandler');
        $logHandler->startRecordingMessages();
        $clientId = $input->getArgument('client');
        $jobId    = $input->getArgument('job');
        $container = $this->getContainer();
        $repository = $container->get('doctrine')->getRepository('BinovoElkarBackupBundle:Job');
        $job = $repository->find($jobId);
        if (!$job || $job->getClient()->getId() != $clientId) {
            $this->err('No such job.');

            return false;
        }
        $policy = $job->getPolicy();
        if (!$policy) {
            $this->warn('Job %jobid% has no policy', array('%jobid%' => $job->getId()));

            return false;
        }
        $retains = $policy->getRetains();
        if (empty($retains)) {
            $this->warn('Policy %policyid% has no active retains', array('%policyid%' => $policy->getId()));

            return false;
        }
        $retains = array($policy->getId() => array($retains[0][0]));
        $jobs = array($job);
        $this->runAllJobs($jobs, $retains);

        return true;
    }

    protected function getNameForLogs()
    {
        return 'RunJobCommand';
    }
}
