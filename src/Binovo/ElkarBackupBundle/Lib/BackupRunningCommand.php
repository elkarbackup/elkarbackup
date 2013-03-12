<?php

namespace Binovo\ElkarBackupBundle\Lib;

use \DateTime;
use \Exception;
use Binovo\ElkarBackupBundle\Entity\Job;
use Binovo\ElkarBackupBundle\Entity\User;
use Binovo\ElkarBackupBundle\Lib\Globals;
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
    const NEW_CLIENT     = 1;
    const SKIP_CLIENT    = 2;
    const RUN_JOB        = 3;
    const QUOTA_EXCEEDED = 4;

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
        $adminEmail       = $this->getContainer()->get('doctrine')->getRepository('BinovoElkarBackupBundle:User')->find(User::SUPERUSER_ID)->getEmail();
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
                ->setSubject($translator->trans('Error log for backup from job %joburl%', array('%joburl%' => $job->getUrl()), 'BinovoElkarBackup'))
                ->setFrom($adminEmail)
                ->setTo($recipients)
                ->setBody($engine->render('BinovoElkarBackupBundle:Default:logreport.html.twig',
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

        $content = $engine->render('BinovoElkarBackupBundle:Default:rsnapshotconfig.txt.twig',
                                   array('cmdPreExec'          => '',
                                         'cmdPostExec'         => '',
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
            $status = 0;
            // pre script execution if needed
            $mustRunScripts = !$job->getPolicy()->isRotation($retain);
            if ($mustRunScripts) {
                foreach ($job->getPreScripts() as $script) {
                    if ($this->runScript('pre', 0, $job->getClient(), $job, $script)) {
                        $this->info('Job "%jobid%" pre script ok.', array('%jobid%' => $job->getId()), $context);
                    } else {
                        $this->err('Job "%jobid%" pre script error.', array('%jobid%' => $job->getId()), $context);
                    }
                }
            }
            // run rsnapshot. sync first if needed
            $commands = array();
            if ($job->getPolicy()->mustSync($retain)) {
                $commands[] = sprintf('"%s" -c "%s" sync 2>&1', $rsnapshot, $confFileName);
            }
            $commands[] = sprintf('"%s" -c "%s" %s 2>&1', $rsnapshot, $confFileName, $retain);
            foreach ($commands as $command) {
                $commandOutput = array();
                $status        = 0;
                $this->info('Running %command%', array('%command%' => $command), $context);
                exec($command, $commandOutput, $status);
                if (0 != $status) {
                    $this->err('Command %command% failed. Diagnostic information follows: %output%',
                               array('%command%' => $command,
                                     '%output%'  => "\n" . implode("\n", $commandOutput)),
                               $context);
                    break;
                } else {
                    $this->info('Command %command% succeeded with output: %output%',
                                array('%command%' => $command,
                                      '%output%'  => implode("\n", $commandOutput)),
                                $context);
                }
            }
            // post script execution if needed
            if ($mustRunScripts) {
                foreach ($job->getPostScripts() as $script) {
                    if ($this->runScript('post', $status, $job->getClient(), $job, $script)) {
                        $this->info('Job "%jobid%" post script ok.', array('%jobid%' => $job->getId()), $context);
                    } else {
                        $this->err('Job "%jobid%" post script error.', array('%jobid%' => $job->getId()), $context);
                    }
                }
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
     * @param  int      $status     Status of previos command.
     *
     * @param  Client   $client     Client entity
     *
     * @param  Job      $job        Job entity. Null if running at the client level.
     *
     * @param  Script   $script     Script entity
     *
     * @return boolean  true on success, false on error.
     *
     */
    protected function runScript($type, $status, $client, $job, $script)
    {
        if ($script === null) {
            return true;
        }
        if (null == $job) {
            $entity = $client;
            $context          = array('link' => $this->generateClientRoute($client->getId()));
            $errScriptError   = 'Client "%entityid%" %scripttype% script "%scriptname%" execution failed. Diagnostic information follows: %output%';
            $errScriptMissing = 'Client "%entityid%" %scripttype% script "%scriptname%" present but file "%scriptfile%" missing.';
            $errScriptOk      = 'Client "%entityid%" %scripttype% script "%scriptname%" execution succeeded. Output follows: %output%';
            $level            = 'CLIENT';
        } else {
            $entity = $job;
            $context          = array('link' => $this->generateJobRoute($job->getId(), $client->getId()));
            $errScriptError   = 'Job "%entityid%" %scripttype% script "%scriptname%" execution failed. Diagnostic information follows: %output%';
            $errScriptMissing = 'Job "%entityid%" %scripttype% script "%scriptname%" present but file "%scriptfile%" missing.';
            $errScriptOk      = 'Job "%entityid%" %scripttype% script "%scriptname%" execution succeeded. Output follows: %output%';
            $level            = 'JOB';
        }
        $scriptName = $script->getName();
        $scriptFile = $script->getScriptPath();
        if (!file_exists($scriptFile)) {
            $this->err($errScriptMissing,
                       array('%entityid%'   => $entity->getId(),
                             '%scriptfile%' => $scriptFile,
                             '%scriptname%' => $scriptName,
                             '%scripttype%' => $type),
                       $context);

            return false;
        }
        $commandOutput = array();
        $command       = sprintf('env ELKARBACKUP_LEVEL="%s" ELKARBACKUP_EVENT="%s" ELKARBACKUP_URL="%s" ELKARBACKUP_ID="%s" ELKARBACKUP_PATH="%s" ELKARBACKUP_STATUS="%s" sudo "%s" 2>&1',
                                 $level,
                                 'pre' == $type ? 'PRE' : 'POST',
                                 $entity->getUrl(),
                                 $entity->getId(),
                                 $entity->getSnapshotRoot(),
                                 $status,
                                 $scriptFile);
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            $this->err($errScriptError,
                       array('%entityid%'   => $entity->getId(),
                             '%output%'     => "\n" . implode("\n", $commandOutput),
                             '%scriptname%' => $scriptName,
                             '%scripttype%' => $type),
                       $context);

            return false;
        }
        $this->info($errScriptOk,
                    array('%entityid%'   => $entity->getId(),
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
        // set all jobs and clients to the queued status
        $lastClient = null;
        foreach ($jobs as $job) {
            if ($job->getClient() != $lastClient) {
                $lastClient = $job->getClient();
                $context = array('link' => $this->generateClientRoute($job->getClient()->getId()));
                $this->info('QUEUED', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
            }
            $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
            $this->info('QUEUED', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
        }
        $manager->flush();
        $storageLoadLevel = 0;
        $warningLoadLevel = (float)$this->getContainer()->getParameter('warning_load_level');
        $i = 0;
        $lastClient = null;
        $state = self::NEW_CLIENT;
        $clientMessages = array();
        while ($i < count($jobs)) { // the last clients post script runs after the loop
            $job = $jobs[$i];
            switch ($state) {
            case self::RUN_JOB:
                $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
                $this->info('RUNNING', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
                if ($job->getClient() == $lastClient) {
                    $retains = $policyIdToRetains[$job->getPolicy()->getId()];
                    $logHandler->clearMessages();
                    if ($storageLoadLevel > $warningLoadLevel) {
                        $this->warn('Client "%clientid%" at %loadLevel% of quota (%quota%MB).',
                                    array('%clientid%'  => $idClient,
                                          '%loadLevel%' => $storageLoadLevel,
                                          '%quota%'     => $client->getQuota() / 1024),
                                    $context);
                    }
                    if ($this->runJob($job, $retains)) {
                        $this->info('Client "%clientid%", Job "%jobid%" ok.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()), $context);
                        $this->info('OK', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
                    } else {
                        $this->err('Client "%clientid%", Job "%jobid%" error.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()), $context);
                        $this->err('FAIL', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
                    }
                    $this->sendNotifications($job, array_merge($clientMessages, $logHandler->getMessages()));
                    $this->info('Client "%clientid%", Job "%jobid%" du begin.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()), $context);
                    $du = (int)shell_exec(sprintf("du -ks '%s' | sed 's/\t.*//'", $job->getSnapshotRoot()));
                    $job->setDiskUsage($du);
                    $this->info('Client "%clientid%", Job "%jobid%" du end.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()), $context);
                    ++$i;
                } else {
                    $state = self::NEW_CLIENT;
                }
                break;
            case self::NEW_CLIENT:
                if ($lastClient) {
                    $idClient   = $lastClient->getId();
                    $scripts    = $lastClient->getPostScripts();
                    $context = array('link' => $this->generateClientRoute($idClient));
                    $allScriptsOk = true;
                    foreach ($scripts as $script) {
                        if ($this->runScript('post', 0, $lastClient, null, $script)) {
                            $this->info('Client "%clientid%" post script ok.', array('%clientid%' => $idClient), $context);
                        } else {
                            $this->err('Client "%clientid%" post script error.', array('%clientid%' => $idClient), $context);
                            $allScriptsOk = false;
                        }
                    }
                    if ($allScriptsOk) {
                        $this->info('OK', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
                    } else {
                        $this->err('FAIL', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
                    }
                }
                $client = $job->getClient();
                $idClient = $client->getId();
                $scripts  = $client->getPreScripts();
                $context = array('link' => $this->generateClientRoute($idClient));
                $this->info('RUNNING', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
                $logHandler->clearMessages();
                $storageLoadLevel = $client->getDiskUsage() / $client->getQuota();
                if ($storageLoadLevel > 1) {
                    $this->err('Client "%clientid%" quota exceeded (quota: %quota%MB, disk usage: %du%MB).',
                               array('%clientid%' => $idClient,
                                     '%quota%'    => $client->getQuota() / 1024,
                                     '%du%'       => $client->getDiskUsage() / 1024),
                               $context);
                    $this->err('QUOTA EXCEEDED', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
                    $state = self::QUOTA_EXCEEDED;
                } else {
                    $state = self::RUN_JOB;
                    foreach ($scripts as $script) {
                        if ($this->runScript('pre', 0, $client, null, $script)) {
                            $this->info('Client "%clientid%" pre script ok.', array('%clientid%' => $idClient), $context);
                        } else {
                            $this->err('Client "%clientid%" pre script failed. Aborting backup.', array('%clientid%' => $idClient), $context);
                            $state = self::SKIP_CLIENT;
                            break;
                        }
                    }
                }
                $clientMessages = $logHandler->getMessages();
                $lastClient = $client;
                break;
            case self::QUOTA_EXCEEDED:
                if ($lastClient != $job->getClient()) {
                    $state = self::NEW_CLIENT;
                } else {
                    // report the client quota exceeded error to the people who might be interested on the fact that the job was not run.
                    $logHandler->clearMessages();
                    $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
                    $this->err('Client "%clientid%" quota exceeded (quota: %quota%MB, disk usage: %du%MB).',
                               array('%clientid%' => $idClient,
                                     '%quota%'    => $client->getQuota() / 1024,
                                     '%du%'       => $client->getDiskUsage() / 1024),
                               $context);
                    $this->err('QUOTA EXCEEDED', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
                    $this->sendNotifications($job, array_merge($clientMessages, $logHandler->getMessages()));
                    ++$i;
                }
                break;
            case self::SKIP_CLIENT:
                if ($lastClient != $job->getClient()) {
                    $state = self::NEW_CLIENT;
                } else {
                    // report the client error to the people who might be interested on the fact that the job was not run.
                    $logHandler->clearMessages();
                    $context = array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId()));
                    $this->err('Client "%clientid%", Job "%jobid%" error. Client level error.', array('%clientid%' => $job->getClient()->getId(), '%jobid%' => $job->getId()), $context);
                    $this->err('FAIL', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
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
            $idClient = $lastClient->getId();
            $scripts  = $lastClient->getPostScripts();
            $context  = array('link' => $this->generateClientRoute($idClient));
            $allScriptsOk = true;
            foreach ($scripts as $script) {
                if ($this->runScript('post', 0, $lastClient, null, $script)) {
                    $this->info('Client "%clientid%" post script ok.', array('%clientid%' => $idClient), $context);
                } else {
                    $this->err('Client "%clientid%" post script error.', array('%clientid%' => $idClient), $context);
                    $allScriptsOk = false;
                }
            }
            if ($allScriptsOk) {
                $this->info('OK', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
            } else {
                $this->err('FAIL', array(), array_merge($context, array('source' => Globals::STATUS_REPORT)));
            }
            $manager->flush();
        }
    }
}
