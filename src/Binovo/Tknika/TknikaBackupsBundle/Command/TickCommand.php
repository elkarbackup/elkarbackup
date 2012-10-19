<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Command;

use \DateTime;
use Binovo\Tknika\TknikaBackupsBundle\Entity\Job;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TickCommand extends ContainerAwareCommand
{
    const NEW_CLIENT  = 1;
    const SKIP_CLIENT = 2;
    const RUN_JOB     = 3;

    protected function configure()
    {
        parent::configure();
        $this->setName('backups:tick')
             ->setDescription('Look for backup jobs to execute')
             ->addArgument('time'  , InputArgument::OPTIONAL, 'As by date("Y-m-d H:i")')
             ->addArgument('job'   , InputArgument::OPTIONAL, 'jobId')
             ->addArgument('retain', InputArgument::OPTIONAL, 'hourly|daily|weekly|monthly|yearly|all');
    }

    protected function runJob(Job $job, OutputInterface $output, $runnableRetains)
    {
        $container = $this->getContainer();

        $backupDir = $container->getParameter('backup_dir');
        $rsnapshot = $container->getParameter('rsnapshot');
        $tmpDir    = $container->getParameter('tmp_dir');
        $engine    = $container->get('templating');

        $idClient = $job->getClient()->getId();
        $idJob    = $job->getId();
        $url      = $job->getUrl();
        $retains  = $job->getPolicy()->getRetains();
        $includes = array();
        $include = $job->getPolicy()->getInclude();
        if ($include) {
            $includes = explode("\n", $include);
        }
        $excludes = array();
        $exclude = $job->getPolicy()->getExclude();
        if ($exclude) {
            $excludes = explode("\n", $exclude);
        }
        $syncFirst = (int)$job->getPolicy()->getSyncFirst();

        $content = $engine->render('BinovoTknikaTknikaBackupsBundle:Default:rsnapshotconfig.txt.twig',
                                   array('cmdPreExec'   => $job->getPreScript()  ? $job->getScriptPath('pre') : '',
                                         'cmdPostExec'  => $job->getPostScript() ? $job->getScriptPath('post'): '',
                                         'excludes'     => $excludes,
                                         'idClient'     => sprintf('%04d', $idClient),
                                         'idJob'        => sprintf('%04d', $idJob),
                                         'includes'     => $includes,
                                         'backupDir'    => $backupDir,
                                         'retains'      => $retains,
                                         'tmp'          => $tmpDir,
                                         'snapshotRoot' => $job->getSnapshotRoot(),
                                         'syncFirst'    => $syncFirst,
                                         'url'          => $url));
        $confFileName = sprintf("%s/rsnapshot.%s_%s.cfg", $tmpDir, $idClient, $idJob);
        $fd = fopen($confFileName, 'w');
        if (false === $fd) {
            $output->writeln(sprintf('Error opening config file %s. Aborting backup.', $confFileName));

            return false;
        }
        $bytesWriten = fwrite($fd, $content);
        if (false === $bytesWriten) {
            $output->writeln(sprintf('Error writing to config file %s. Aborting backup.', $confFileName));

            return false;
        }
        $ok = fclose($fd);
        if (false === $ok) {
            $output->writeln(sprintf('Error closing config file %s.', $confFileName));
        }
        foreach ($runnableRetains as $retain) {
            if ($job->getPolicy()->mustSync($retain)) {
                $command = sprintf('"%s" -c "%s" sync 2>&1 && "%s" -c "%s" %s 2>&1', $rsnapshot, $confFileName, $rsnapshot, $confFileName, $retain);
            } else {
                $command = sprintf('"%s" -c "%s" %s 2>&1', $rsnapshot, $confFileName, $retain);
            }
            $commandOutput = array();
            $status        = 0;
            $output->writeln(sprintf('Running %s', $command));
            exec($command, $commandOutput, $status);
            if (0 != $status) {
                $output->writeln(sprintf('Command %s failed. Diagnostic information follows:', $command));
                $output->writeln(implode("\n", $commandOutput));
            } else {
                $output->writeln(sprintf('Command %s succeeded with output:', $command));
                $output->writeln(implode("\n", $commandOutput));
            }
        }
        $ok = unlink($confFileName);
        if (false === $ok) {
            $output->writeln(sprintf('Error unlinking config file %s.', $confFileName));
        }

        return $ok;
    }

    /**
     * Runs a client level pre or post script.
     *
     * @param  string   $type       Either "pre" or "post".
     *
     * @param  int      $idClient   Client entity identificator
     *
     * @param  string   $scriptName Script name as user legible string
     *
     * @param  string   $scriptFile Full path to script in filesystem
     *
     * @param  object   $output     Write output here
     *
     * @return boolean  true on success, false on error.
     *
     */
    protected function runScript($type, $idClient, $scriptName, $scriptFile, OutputInterface $output)
    {
        if ($scriptName === null) {
            return true;
        }
        if (!file_exists($scriptFile)) {
            $output->writeln(sprintf('Client "%s" %s script "%s" present but file "%s" missing.',
                                     $idClient, $type, $scriptName, $scriptFile));
            return false;
        }
        $command       = sprintf('env TYPE=%s CLIENTID=%s "%s" 2>&1', $type, $idClient, $scriptFile);
        $commandOutput = array();
        $status        = 0;
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            $output->writeln(sprintf('Client "%s" %s script "%s" execution failed. Diagnostic information follows:',
                                     $idClient, $type, $scriptFile));
            $output->writeln(implode("\n", $commandOutput));
            return false;
        }
        $output->writeln(sprintf('Client "%s" %s script "%s" execution succeeded',
                                 $idClient, $type, $scriptFile));
        return true;
    }

    protected function _parseTime($time)
    {
        if (empty($time)) {
            $time = new DateTime();
        } else {
            $time = DateTime::createFromFormat("Y-m-d H:i", $time);
        }
        return $time;
    }

    protected function err($msg, $translatorParams = array(), $source = 'TickCommand')
    {
        $logger = $this->getContainer()->get('BnvWebLogger');
        $translator = $this->getContainer()->get('translator');
        $logger->err($translator->trans($msg, $translatorParams, 'BinovoTknikaBackups'), array('source' => $source));
    }

    protected function info($msg, $translatorParams = array(), $source = 'TickCommand')
    {
        $logger = $this->getContainer()->get('BnvWebLogger');
        $translator = $this->getContainer()->get('translator');
        $logger->info($translator->trans($msg, $translatorParams, 'BinovoTknikaBackups'), array('source' => $source));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = $this->_parseTime($input->getArgument('time'));
        if (!$time) {
            $this->err('Invalid time specified.'); // :TODO: trans
            return false;
        }
        $job = $this->_parseTime($input->getArgument('time'));
        $retain = $input->getArgument('retain');
        if (!$retain) {
            $retain = 'all';
        }
        $container = $this->getContainer();
        $repository = $container->get('doctrine')->getRepository('BinovoTknikaTknikaBackupsBundle:Client');


        $repository = $container->get('doctrine')->getRepository('BinovoTknikaTknikaBackupsBundle:Policy');
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
FROM  BinovoTknikaTknikaBackupsBundle:Job j
JOIN  j.client                            c
JOIN  j.policy                            p
WHERE j.isActive = 1 AND c.isActive = 1 AND j.policy IN ($runnablePolicies)
ORDER BY c.id
EOF;
        $jobs = $manager->createQuery($dql)->getResult();
        $i = 0;
        $lastClient = null;
        $state = self::NEW_CLIENT;
        while ($i < count($jobs)) { // the last clients post script runs after the loop
            $job = $jobs[$i];
            switch ($state) {
            case self::RUN_JOB:
                if ($job->getClient() == $lastClient) {
                    $retains = $policies[$job->getPolicy()->getId()];
                    if ($this->runJob($job, $output, $retains)) {
                        $this->info('Client "%clientid%", Job "%jobid%" ok.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()));
                    } else {
                        $this->err('Client "%clientid%", Job "%jobid%" error.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()));
                    }
                    ++$i;
                } else {
                    $state = self::NEW_CLIENT;
                }
                break;
            case self::NEW_CLIENT:
                if ($lastClient) {
                    $idClient   = $lastClient->getId();
                    $scriptFile = $lastClient->getScriptPath('post');
                    $scriptName = $lastClient->getPostScript();
                    if ($this->runScript('post', $idClient, $scriptName, $scriptFile, $output)) {
                        $this->info('Client "%clientid%" post script ok.', array('%clientid%' => $idClient));
                    } else {
                        $this->err('Client "%clientid%" post script error.', array('%clientid%' => $idClient));
                    }
                }
                $client = $job->getClient();
                $idClient   = $client->getId();
                $scriptFile = $client->getScriptPath('pre');
                $scriptName = $client->getPreScript();
                if ($this->runScript('pre', $idClient, $scriptName, $scriptFile, $output)) {
                    $this->info('Client "%clientid%" pre script ok.', array('%clientid%' => $idClient));
                    $state = self::RUN_JOB;
                } else {
                    $this->err('Client "%clientid%" pre script failed. Aborting backup.', array('%clientid%' => $idClient));
                    $state = self::SKIP_CLIENT;
                }
                $lastClient = $client;
                break;
            case self::SKIP_CLIENT:
                if ($lastClient != $job->getClient()) {
                    $state = self::NEW_CLIENT;
                } else {
                    ++$i;
                }
                break;
            default:
                // this should never happen
                die();
            }
        }
        if ($lastClient) {
            $idClient   = $lastClient->getId();
            $scriptFile = $lastClient->getScriptPath('post');
            $scriptName = $lastClient->getPostScript();
            if ($this->runScript('post', $idClient, $scriptName, $scriptFile, $output)) {
                $this->info('Client "%clientid%" post script ok.', array('%clientid%' => $idClient));
            } else {
                $this->err('Client "%clientid%" post script error.', array('%clientid%' => $idClient));
            }
        }
    }
}