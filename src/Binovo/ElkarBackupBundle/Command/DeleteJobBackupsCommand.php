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
              ->addArgument('job'   , InputArgument::OPTIONAL, 'Job id')
              ->setDescription('Deletes all the backups of a job identified by its id');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('BnvWebLogger');
        $context = array('source' => 'DeleteJobBackupsCommand');
        $doctrine = $this->getContainer()->get('doctrine');
        $manager = $doctrine->getManager();
        $jobId = $input->getArgument('job');
        $clientId = $input->getArgument('client');
        $backupLocations = $doctrine->getRepository('BinovoElkarBackupBundle:BackupLocation')->findAll();
        $allOk = 0;
        foreach ($backupLocations as $location) {
            $backupDir = $location->getEffectiveDir();
            if (null == $jobId) {
                $removeDir = sprintf('%s/%04d', $backupDir, $clientId);
            } else {
                $removeDir = sprintf(
                    '%s/%04d/%04d',
                    $backupDir,
                    $clientId,
                    $jobId
                );
            }
            
            if (is_dir($removeDir)) {
                if (Globals::delTree($removeDir)) {
                    $logger->info('Directory deleted: ' . $removeDir, array('source' => 'DeleteJobBackupsCommand'));
                } else {
                    $logger->error('Error deleting directory: ' . $removeDir, array('source' => 'DeleteJobBackupsCommand'));
                    $allOk = 1;
                }
            } else {
                $logger->info('Directory does not exist: ' . $removeDir, array('source' => 'DeleteJobBackupsCommand'));
            }

            
        }
        $manager->flush();
        return $allOk;

    }
}