<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use App\Lib\LoggingCommand;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


class RemoteRestoreCommand extends ContainerAwareCommand
{
    private $logger;
    
    const PARAM_URL = 'url';
    const PARAM_SOURCE_PATH = 'sourcePath';
    const PARAM_REMOTE_PATH = 'remotePath';
    const PARAM_SSHARGS = 'sshArgs';
    
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        parent::__construct();
    }
    
    protected function configure()
    {
        $this->setName('elkarbackup:restore_backup');
        $this->setDescription('Restore local elkarbackup host files to remote host.');
        $this->addArgument(self::PARAM_URL, InputArgument::REQUIRED,'The connection URL for the remote host');
        $this->addArgument(self::PARAM_SOURCE_PATH, InputArgument::REQUIRED,'The source path to the directory or files that should be copied');
        $this->addArgument(self::PARAM_REMOTE_PATH, InputArgument::REQUIRED,'Path in the remote host to restore the directory or files');
        $this->addArgument(self::PARAM_SSHARGS,InputArgument::OPTIONAL,'Extra SSH parameters for connection to the remote host');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $manager = $container->get('doctrine')->getManager();
        
        $logger = $this->logger;
        $translator = $this->getContainer()->get('translator');
        $context = array('source' => 'RestoreBackups');
                
        $volumes = $manager->getRepository('App:BackupLocation'); 

        $url = $input->getArgument(self::PARAM_URL);
        $sourcePath = $input->getArgument(self::PARAM_SOURCE_PATH);
        $remotePath = $input->getArgument(self::PARAM_REMOTE_PATH);
        $sshArgs = $input->getArgument(self::PARAM_SSHARGS);
        
        if($sshArgs !== ''){
               $port = preg_replace('/[^0-9]/', '', $sshArgs);
               if ($port !== ''){
                   $sshArgs = '-p '.$port;
               } else {
                   $sshArgs = '-p 22';
               }
               $cmd = sprintf('rsync -azhv -e "ssh -o \\"StrictHostKeyChecking no\\"  %s" "%s" "%s:%s"',$sshArgs,$sourcePath,$url,$remotePath);
        } else {
               $cmd = sprintf('rsync -azhv -e "ssh -o \\"StrictHostKeyChecking no\\" " "%s" "%s:%s"',$sourcePath,$url,$remotePath);
        }
        $logger->info('Starting restore job ',$context);
        $manager->flush();

        $process = new Process($cmd);
        $process->run();

        if(!$process->isSuccessful()) {
              $logger->error('Error message ' . $process->getErrorOutput(), $context);
              $manager->flush();
              return;
        } else {
              $logger->info('Restored successfully',$context);
              $manager->flush();
        }

    }
}
