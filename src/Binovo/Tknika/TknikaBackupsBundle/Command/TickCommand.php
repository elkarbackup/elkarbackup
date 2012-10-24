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

    protected function generateClientRoute($id)
    {
        return $this->getContainer()->get('router')->generate('editClient', array('id' => $id), true);
    }

    protected function generateJobRoute($idJob, $idClient)
    {
        return $this->getContainer()->get('router')->generate('editJob',
                                                              array('idClient' => $idClient,
                                                                    'idJob'    => $idJob),
                                                              true);
    }

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
        $context = array('link' => $this->generateJobRoute($idJob, $idClient));

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
            $this->err('Error opening config file %filename%. Aborting backup.', array('%filename%' => $confFileName), $context);

            return false;
        }
        $bytesWriten = fwrite($fd, $content);
        if (false === $bytesWriten) {
            $this->err('Error writing to config file %filename%. Aborting backup.', array('%filename%' => $confFileName), $context);

            return false;
        }
        $ok = fclose($fd);
        if (false === $ok) {
            $this->warn('Error closing config file %filename%.', array('%filename%' => $confFileName), $context);
        }
        foreach ($runnableRetains as $retain) {
            if ($job->getPolicy()->mustSync($retain)) {
                $command = sprintf('"%s" -c "%s" sync 2>&1 && "%s" -c "%s" %s 2>&1', $rsnapshot, $confFileName, $rsnapshot, $confFileName, $retain);
            } else {
                $command = sprintf('"%s" -c "%s" %s 2>&1', $rsnapshot, $confFileName, $retain);
            }
            $commandOutput = array();
            $status        = 0;
            $this->info('Running %command%', array('%command%' => $command), $context);
            exec($command, $commandOutput, $status);
            if (0 != $status) {
                $this->err('Command %command% failed. Diagnostic information follows: %output%',
                           array('%command%' => $command,
                                 '%output%'  => "\n" . implode("\n", $commandOutput)),
                           $context);
            } else {
                $this->info('Command %command% succeeded with output: %output%',
                            array('%command%' => $command,
                                  '%output%'  => implode("\n", $commandOutput)),
                            $context);
            }
        }
        $ok = unlink($confFileName);
        if (false === $ok) {
            $this->warn('Error unlinking config file %filename%.',
                        array('%filename%' => $confFileName),
                        $context);
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
        $context = array('link' => $this->generateClientRoute($idClient));
        if (!file_exists($scriptFile)) {
            $this->err('Client "%clientid%" %scripttype% script "%scriptname%" present but file "%scriptfile%" missing.',
                       array('%clientid%'   => $idClient,
                             '%scriptfile%' => $scriptFile,
                             '%scriptname%' => $scriptName,
                             '%scripttype%' => $type),
                       $context);

            return false;
        }
        $command       = sprintf('env TYPE=%s CLIENTID=%s "%s" 2>&1', $type, $idClient, $scriptFile);
        $commandOutput = array();
        $status        = 0;
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            $this->err('Client "%clientid%" %scripttype% script "%scriptname%" execution failed. Diagnostic information follows: %output%',
                       array('%clientid%'   => $idClient,
                             '%output%'     => "\n" . implode("\n", $commandOutput),
                             '%scriptname%' => $scriptName,
                             '%scripttype%' => $type),
                       $context);

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

    protected function err($msg, $translatorParams = array(), $context = array())
    {
        $logger = $this->getContainer()->get('BnvWebLogger');
        $translator = $this->getContainer()->get('translator');
        $context = array_merge(array('source' => 'TickCommand'), $context);
        $logger->err($translator->trans($msg, $translatorParams, 'BinovoTknikaBackups'), $context);
    }

    protected function info($msg, $translatorParams = array(), $context = array())
    {
        $logger = $this->getContainer()->get('BnvWebLogger');
        $translator = $this->getContainer()->get('translator');
        $context = array_merge(array('source' => 'TickCommand'), $context);
        $logger->info($translator->trans($msg, $translatorParams, 'BinovoTknikaBackups'), $context);
    }

    protected function warn($msg, $translatorParams = array(), $context = array())
    {
        $logger = $this->getContainer()->get('BnvWebLogger');
        $translator = $this->getContainer()->get('translator');
        $context = array_merge(array('source' => 'TickCommand'), $context);
        $logger->warn($translator->trans($msg, $translatorParams, 'BinovoTknikaBackups'), $context);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('router')->getContext()->setHost(gethostname());
        $time = $this->_parseTime($input->getArgument('time'));
        if (!$time) {
            $this->err('Invalid time specified.');
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
                $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
                if ($job->getClient() == $lastClient) {
                    $retains = $policies[$job->getPolicy()->getId()];
                    if ($this->runJob($job, $output, $retains)) {
                        $this->info('Client "%clientid%", Job "%jobid%" ok.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()), $context);
                    } else {
                        $this->err('Client "%clientid%", Job "%jobid%" error.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()), $context);
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
                    $context = array('link' => $this->generateClientRoute($idClient));
                    if ($this->runScript('post', $idClient, $scriptName, $scriptFile, $output)) {
                        $this->info('Client "%clientid%" post script ok.', array('%clientid%' => $idClient), $context);
                    } else {
                        $this->err('Client "%clientid%" post script error.', array('%clientid%' => $idClient), $context);
                    }
                }
                $client = $job->getClient();
                $idClient   = $client->getId();
                $scriptFile = $client->getScriptPath('pre');
                $scriptName = $client->getPreScript();
                $context = array('link' => $this->generateClientRoute($idClient));
                if ($this->runScript('pre', $idClient, $scriptName, $scriptFile, $output)) {
                    $this->info('Client "%clientid%" pre script ok.', array('%clientid%' => $idClient), $context);
                    $state = self::RUN_JOB;
                } else {
                    $this->err('Client "%clientid%" pre script failed. Aborting backup.', array('%clientid%' => $idClient), $context);
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
            $context = array('link' => $this->generateClientRoute($idClient));
            if ($this->runScript('post', $idClient, $scriptName, $scriptFile, $output)) {
                $this->info('Client "%clientid%" post script ok.', array('%clientid%' => $idClient), $context);
            } else {
                $this->err('Client "%clientid%" post script error.', array('%clientid%' => $idClient), $context);
            }
        }
    }
}