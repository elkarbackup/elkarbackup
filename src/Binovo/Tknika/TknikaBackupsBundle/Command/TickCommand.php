<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TickCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('backups:tick')
             ->setDescription('Look for backup jobs to execute')
             ->addArgument('retain', InputArgument::OPTIONAL, 'hourly|daily|weekly');
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $retain = $input->getArgument('retain');
        if (!$retain) {
            $retain = 'hourly';
        }
        $container = $this->getContainer();
        $backupDir = $container->getParameter('backup_dir');
        $rsnapshot = $container->getParameter('rsnapshot');
        $tmpDir    = $container->getParameter('tmp_dir');
        $engine = $container->get('templating');
        $repository = $container->get('doctrine')->getRepository('BinovoTknikaTknikaBackupsBundle:Client');
        $query = $repository->createQueryBuilder('client')
            ->orderBy('client.id')
            ->getQuery();
        $clients = $query->getResult();
        foreach ($clients as $client) {
            $idClient  = $client->getId();
            $scriptName = $client->getPreScript();
            $scriptFile = $client->getScriptPath('pre');
            if (!$this->runScript('pre', $idClient, $scriptName, $scriptFile, $output)) {
                $output->writeln(sprintf('Client "%s" pre script failed. Aborting backup.', $idClient));
                continue;
            }
            foreach ($client->getJobs() as $job) {
                $idJob     = $job->getId();
                $url       = $job->getUrl();
                $content = $engine->render('BinovoTknikaTknikaBackupsBundle:Default:rsnapshotconfig.txt.twig',
                                           array('cmdPreExec'    => $job->getPreScript()  ? $job->getScriptPath('pre') : '',
                                                 'cmdPostExec'   => $job->getPostScript() ? $job->getScriptPath('post'): '',
                                                 'idClient'      => sprintf('%04d', $idClient),
                                                 'idJob'         => sprintf('%04d', $idJob),
                                                 'backupDir'     => $backupDir,
                                                 'tmp'           => $tmpDir,
                                                 'snapshotRoot'  => $job->getSnapshotRoot(),
                                                 'url'           => $url));
                $confFileName = sprintf("%s/rsnapshot.%s_%s.cfg", $tmpDir, $idClient, $idJob);
                $fd = fopen($confFileName, 'w');
                if (false === $fd) {
                    $output->writeln(sprintf('Error opening config file %s. Aborting backup.', $confFileName));
                    continue;
                }
                $bytesWriten = fwrite($fd, $content);
                if (false === $bytesWriten) {
                    $output->writeln(sprintf('Error writing to config file %s. Aborting backup.', $confFileName));
                    continue;
                }
                $ok = fclose($fd);
                if (false === $ok) {
                    $output->writeln(sprintf('Error closing config file %s.', $confFileName));
                }
                $command       = sprintf('"%s" -c "%s" %s 2>&1', $rsnapshot, $confFileName, $retain);
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
                $ok = unlink($confFileName);
                if (false === $ok) {
                    $output->writeln(sprintf('Error unlinking config file %s.', $confFileName));
                }
            }
            $scriptName = $client->getPostScript();
            $scriptFile = $client->getScriptPath('post');
            if (!$this->runScript('post', $idClient, $scriptName, $scriptFile, $output)) {
                continue;
            }
        }
    }
}