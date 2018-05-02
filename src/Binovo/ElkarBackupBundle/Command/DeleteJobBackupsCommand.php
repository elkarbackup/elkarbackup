<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Command;

use Binovo\ElkarBackupBundle\Lib\Globals;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteJobBackupsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('elkarbackup:delete_job_backups')
              ->addArgument('client', InputArgument::REQUIRED, 'Client id')
              ->addArgument('job'   , InputArgument::REQUIRED, 'Job id')
              ->setDescription('Deletes all the backups of a job identified by its id');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('BnvWebLogger');
        $context = array('source' => 'DeleteJobBackupsCommand');
        $doctrine = $this->getContainer()->get('doctrine');
        $manager = $doctrine->getManager();
        $jobId = $input->getArgument('job');
        $job = $doctrine->getRepository('BinovoElkarBackupBundle:Job')->find($jobId);
        //TODO: BUSCAR JOB
        
        $backupsDir = Globals::getSnapshotRoot($input->getArgument('client'), $job);
        $manager->remove($job);
        $manager->flush();
        if (Globals::delTree($backupsDir)) {
            $logger->info('Directory deleted: ' . $backupsDir, array('source' => 'DeleteJobBackupsCommand'));

            return 0;
        } else {
            $logger->err('Error deleting directory: ' . $backupsDir, array('source' => 'DeleteJobBackupsCommand'));

            return 1;
        }
    }
}
