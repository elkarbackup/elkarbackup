<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Lib;

use \DateTime;
use \Exception;
use Binovo\Tknika\TknikaBackupsBundle\Entity\Job;
use Binovo\Tknika\TknikaBackupsBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This class can be used as base class for those commands which need
 * to call backups jobs. It provides helper functions to run the jobs,
 * send notifications, write information to the log, etc.
 */
abstract class BackupRunningCommand extends LoggingCommand
{
    const NEW_CLIENT  = 1;
    const SKIP_CLIENT = 2;
    const RUN_JOB     = 3;

    protected function parseTime($time)
    {
        if (empty($time)) {
            $time = new DateTime();
        } else {
            $time = DateTime::createFromFormat("Y-m-d H:i", $time);
        }
        return $time;
    }

    /**
     * Send email with error log data.
     *
     * @param Job              $job      The Job whose log we are about to send.
     * @param array(LogRecord) $messages An array of LogRecords with the error information.
     */
    protected function sendNotifications(Job $job, $messages)
    {
        $adminEmail       = $this->getContainer()->get('doctrine')->getRepository('BinovoTknikaTknikaBackupsBundle:User')->find(User::SUPERUSER_ID)->getEmail();
        $idClient         = $job->getClient()->getId();
        $idJob            = $job->getId();
        $translator       = $this->getContainer()->get('translator');
        $recipients       = array();
        $engine           = $this->getContainer()->get('templating');
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
                    $recipients[] = $job->getOwner()->getEmail();
                    break;
                case Job::NOTIFY_TO_EMAIL:
                    $recipients[] = $job->getNotificationsEmail();
                    break;
                default:
                    // do nothing
                }
            }
            $message = \Swift_Message::newInstance()
                ->setSubject($translator->trans('Error log for backup from job %joburl%', array('%joburl%' => $job->getUrl()), 'BinovoTknikaBackups'))
                ->setFrom($adminEmail)
                ->setTo($recipients)
                ->setBody($engine->render('BinovoTknikaTknikaBackupsBundle:Default:logreport.html.twig',
                                          array('base'     => gethostname(),
                                                'job'      => $job,
                                                'messages' => $filteredMessages)),
                          'text/html');
            try {
                $this->getContainer()->get('mailer')->send($message);
            } catch (Exception $e) {
                $this->err('Command was unable to send the notification message: %exception%', array('%exception%' => $e->getMessage()));
            }
        }
    }

    protected function runJob(Job $job, $runnableRetains)
    {
        $container = $this->getContainer();

        $backupDir  = $container->getParameter('backup_dir');
        $rsnapshot  = $container->getParameter('rsnapshot');
        $tmpDir     = $container->getParameter('tmp_dir');
        $engine     = $container->get('templating');

        $idClient = $job->getClient()->getId();
        $idJob    = $job->getId();
        $url      = $job->getUrl();
        $retains  = $job->getPolicy()->getRetains();
        $includes = array();
        $include = $job->getInclude();
        if ($include) {
            $includes = explode("\n", $include);
        }
        $excludes = array();
        $exclude = $job->getExclude();
        if ($exclude) {
            $excludes = explode("\n", $exclude);
        }
        $syncFirst = (int)$job->getPolicy()->getSyncFirst();
        $context = array('link' => $this->generateJobRoute($idJob, $idClient));

        $content = $engine->render('BinovoTknikaTknikaBackupsBundle:Default:rsnapshotconfig.txt.twig',
                                   array('cmdPreExec'          => $job->getPreScript()  ? $job->getScriptPath('pre') : '',
                                         'cmdPostExec'         => $job->getPostScript() ? $job->getScriptPath('post'): '',
                                         'excludes'            => $excludes,
                                         'idClient'            => sprintf('%04d', $idClient),
                                         'idJob'               => sprintf('%04d', $idJob),
                                         'includes'            => $includes,
                                         'backupDir'           => $backupDir,
                                         'retains'             => $retains,
                                         'tmp'                 => $tmpDir,
                                         'snapshotRoot'        => $job->getSnapshotRoot(),
                                         'syncFirst'           => $syncFirst,
                                         'url'                 => $url,
                                         'useLocalPermissions' => $job->getUseLocalPermissions()));
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
        if (!is_dir($job->getSnapshotRoot())) {
            $ok = mkdir($job->getSnapshotRoot(), 0777, true);
            if (false === $ok) {
                $this->err('Error creating snapshot root %filename%. Aborting backup.', array('%filename%' => $job->getSnapshotRoot()), $context);

                return false;
            }
        }
        foreach ($runnableRetains as $retain) {
            if ($job->getPolicy()->mustSync($retain)) {
                $command = sprintf('"%s" -c "%s" sync 2>&1 && "%s" -c "%s" %s 2>&1', $rsnapshot, $confFileName, $rsnapshot, $confFileName, $retain);
            } else {
                $command = sprintf('"%s" -c "%s" %s 2>&1', $rsnapshot, $confFileName, $retain);
            }
echo $command;
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
     * @return boolean  true on success, false on error.
     *
     */
    protected function runScript($type, $idClient, $scriptName, $scriptFile)
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
        $this->info('Client "%clientid%" %scripttype% script "%scriptname%" execution succeeded. Output follows: %output%',
                    array('%clientid%'   => $idClient,
                          '%output%'     => "\n" . implode("\n", $commandOutput),
                          '%scriptname%' => $scriptName,
                          '%scripttype%' => $type),
                    $context);

        return true;
    }

    protected function runAllJobs($jobs, $policyIdToRetains)
    {
        $logHandler = $this->getContainer()->get('BnvLoggerHandler');
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $i = 0;
        $lastClient = null;
        $state = self::NEW_CLIENT;
        $clientMessages = array();
        while ($i < count($jobs)) { // the last clients post script runs after the loop
            $job = $jobs[$i];
            switch ($state) {
            case self::RUN_JOB:
                $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
                if ($job->getClient() == $lastClient) {
                    $retains = $policyIdToRetains[$job->getPolicy()->getId()]; // :TODO: controlar que está definido
                    $logHandler->clearMessages();
                    if ($this->runJob($job, $retains)) {
                        $this->info('Client "%clientid%", Job "%jobid%" ok.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()), $context);
                    } else {
                        $this->err('Client "%clientid%", Job "%jobid%" error.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()), $context);
                    }
                    $this->sendNotifications($job, array_merge($clientMessages, $logHandler->getMessages()));
                    $du = (int)shell_exec(sprintf("du -bs '%s' | sed 's/\t.*//'", $job->getSnapshotRoot()));
                    $job->setDiskUsage($du);
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
                    if ($this->runScript('post', $idClient, $scriptName, $scriptFile)) {
                        $this->info('Client "%clientid%" post script ok.', array('%clientid%' => $idClient), $context);
                    } else {
                        $this->err('Client "%clientid%" post script error.', array('%clientid%' => $idClient), $context);
                    }
                    $du = (int)shell_exec(sprintf("du -bs '%s' | sed 's/\t.*//'", $lastClient->getSnapshotRoot()));
                    $lastClient->setDiskUsage($du);
                }
                $client = $job->getClient();
                $idClient   = $client->getId();
                $scriptFile = $client->getScriptPath('pre');
                $scriptName = $client->getPreScript();
                $context = array('link' => $this->generateClientRoute($idClient));
                $logHandler->clearMessages();
                if ($this->runScript('pre', $idClient, $scriptName, $scriptFile)) {
                    $this->info('Client "%clientid%" pre script ok.', array('%clientid%' => $idClient), $context);
                    $state = self::RUN_JOB;
                } else {
                    $this->err('Client "%clientid%" pre script failed. Aborting backup.', array('%clientid%' => $idClient), $context);
                    $state = self::SKIP_CLIENT;
                }
                $clientMessages = $logHandler->getMessages();
                $lastClient = $client;
                break;
            case self::SKIP_CLIENT:
                if ($lastClient != $job->getClient()) {
                    $state = self::NEW_CLIENT;
                } else {
                    // report the client error to the people who might be interested on the fact that the job was not run.
                    $logHandler->clearMessages();
                    $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
                    $this->err('Client "%clientid%", Job "%jobid%" error. Client level error.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()), $context);
                    $this->sendNotifications($job, array_merge($clientMessages, $logHandler->getMessages()));
                    ++$i;
                }
                break;
            default:
                // this should never happen
                die();
            }
            $manager->flush();
        }
        if ($lastClient) {
            $idClient   = $lastClient->getId();
            $scriptFile = $lastClient->getScriptPath('post');
            $scriptName = $lastClient->getPostScript();
            $context = array('link' => $this->generateClientRoute($idClient));
            if ($this->runScript('post', $idClient, $scriptName, $scriptFile)) {
                $this->info('Client "%clientid%" post script ok.', array('%clientid%' => $idClient), $context);
            } else {
                $this->err('Client "%clientid%" post script error.', array('%clientid%' => $idClient), $context);
            }
            $du = (int)shell_exec(sprintf("du -bs '%s' | sed 's/\t.*//'", $lastClient->getSnapshotRoot()));
            $lastClient->setDiskUsage($du);
            $manager->flush();
        }
    }
}
