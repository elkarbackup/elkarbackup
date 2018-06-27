<?php
namespace Binovo\ElkarBackupBundle\Command;

use Symfony\Component\Console\Command\Command;
use Binovo\ElkarBackupBundle\Lib\LoggingCommand;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


class RemoteRestoreCommand extends ContainerAwareCommand
{
    const PARAM_URL = 'url';
    const PARAM_SOURCE_PATH = 'sourcePath';
    const PARAM_REMOTE_PATH = 'remotePath';

    protected function configure()
    {
        $this->setName('elkarbackup:restore_backup');
        $this->setDescription('Restore a backup into remote machine.');
        $this->addArgument(self::PARAM_URL, InputArgument::REQUIRED,'The connection URL for the remote machine');
        $this->addArgument(self::PARAM_SOURCE_PATH, InputArgument::REQUIRED,'The source path to the directory or files that should be copied');
        $this->addArgument(self::PARAM_REMOTE_PATH, InputArgument::REQUIRED,'The remote path on which the directory or files should be retore');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $manager = $container->get('doctrine')->getManager();
        
        $logger = $this->getContainer()->get('BnvWebLogger');
        $translator = $this->getContainer()->get('translator');
        $context = array('source' => 'RestoreBackups');
                
        $volumes = $manager->getRepository('BinovoElkarBackupBundle:BackupLocation'); 

        $url = $input->getArgument(self::PARAM_URL);
        $sourcePath = $input->getArgument(self::PARAM_SOURCE_PATH);
        $remotePath = $input->getArgument(self::PARAM_REMOTE_PATH);

        $cmd = sprintf('rsync -azhv -e "ssh -o \\"StrictHostKeyChecking no\\" " %s %s:%s',$sourcePath,$url,$remotePath);
        $logger->info('Starting restore backups ',$context);
        $manager->flush();

        $process = new Process($cmd);
        $process->run();

        if(!$process->isSuccessful()) {
          $logger->err('Error message ' . $process->getErrorOutput(), $context);
          $manager->flush();
          return;
        } else {
          $logger->info('Restored successfully',$context);
          $manager->flush();
        }

    }
}
