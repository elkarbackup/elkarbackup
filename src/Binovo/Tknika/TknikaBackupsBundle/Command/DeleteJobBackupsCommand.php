<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Command;

use Binovo\Tknika\TknikaBackupsBundle\Lib\Globals;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteJobBackupsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('tknikabackups:delete_job_backups')
              ->addArgument('client', InputArgument::REQUIRED, 'Client id')
              ->addArgument('job'   , InputArgument::OPTIONAL, 'Job id')
              ->setDescription('Deletes a the backups of a job identified by its id');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('BnvWebLogger');
        $context = array('source' => 'DeleteJobBackupsCommand');
        $backupsDir = Globals::getSnapshotRoot($input->getArgument('client'), $input->getArgument('job'));
        if (Globals::delTree($backupsDir)) {
            $logger->info('Directory deleted: ' . $backupsDir);
        } else {
            $logger->err('Error deleting directory: ' . $backupsDir);
        }
    }
}
