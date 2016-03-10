<?php
/**
 * @copyright 2015 Xabier Ezpeleta <xezpeleta@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

 namespace Binovo\ElkarBackupBundle\Command;

 use \DateInterval;
 use \DateTime;
 use \Exception;
 use Binovo\ElkarBackupBundle\Entity\Job;
 use Binovo\ElkarBackupBundle\Lib\Globals;
 use Binovo\ElkarBackupBundle\Lib\BackupRunningCommand;
 use Symfony\Component\Console\Command\Command;
 use Symfony\Component\Console\Input\ArrayInput;
 use Symfony\Component\Console\Input\InputArgument;
 use Symfony\Component\Console\Input\InputInterface;
 use Symfony\Component\Console\Input\InputOption;
 use Symfony\Component\Console\Output\OutputInterface;

 class StopJobCommand extends BackupRunningCommand
 {
   protected function configure()
   {
     parent::configure();
     $this->setName('elkarbackup:stop_job')
          ->setDescription('Stop specified job. It will kill the rsnapshot process')
          ->addArgument('client', InputArgument::REQUIRED, 'clientId')
          ->addArgument('job', InputArgument::REQUIRED, 'jobId');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     $manager = $this->getContainer()->get('doctrine')->getManager();
     $result = $this->stopJob($input, $output);
     $manager->flush();
     if ($result) {
       return 0;
     } else {
       return 1;
     }
   }

   protected function stopJob(InputInterface $input, OutputInterface $output)
   {
     $manager = $this->getContainer()->get('doctrine')->getManager();
     $logHandler = $this->getContainer()->get('BnvLoggerHandler');
     $logHandler->startRecordingMessages();
     $clientId = $input->getArgument('client');
     $jobId    = $input->getArgument('job');
     $container = $this->getContainer();
     $repository = $container->get('doctrine')->getRepository('BinovoElkarBackupBundle:Job');
     $tmp = $container->getParameter('tmp_dir');
     $job = $repository->find($jobId);
     $context = array('link' => $this->generateJobRoute($jobId, $clientId));
     if (!$job || $job->getClient()->getId() != $clientId) {
         $this->err('No such job.');

         return false;
     }
     $lockfile = sprintf("%s/rsnapshot.%04d_%04d.pid",
                                          $tmp,
                                          $clientId,
                                          $jobId);

    if ($job->getStatus() == "ABORTED"){
        $this->info('Job previously aborted by tick command', array(), $context);
    } else {
        if (file_exists($lockfile)) {
           $command1 = shell_exec(sprintf("kill -TERM $(cat '%s')", $lockfile));
           $command2 = shell_exec("killall rsync");
           $this->info('Job backup aborted successfully', array(), $context);
           $job->setStatus('ABORTED');
           $context = array('link'   => $this->generateJobRoute($jobId, $clientId),
                            'source' => Globals::STATUS_REPORT);
           $job->setStatus('ABORTED');
        } else {
           $this->warn('Cannot abort job backup: not running', array(), $context);
        }
    }

    return true;
   }

   protected function getNameForLogs()
   {
       return 'StopJobCommand';
   }
 }
