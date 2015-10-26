<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarTahoeBundle\Command;

use Binovo\ElkarBackupBundle\Lib\LoggingCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RenewLeasesCommand extends LoggingCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('tahoe:renew_leases')
              ->setDescription('Renews the lease of every file saved in the tahoe storage.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = array('source' => 'RenewLeasesCommand');

        $tahoeAlias = 'tahoe';

        $command = $tahoeAlias . ' deep-check --add-lease elkarbackup:';
        $commandOutput  = array();
        $status         = 0;
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            $this->err('Error trying to renew tahoe leases: ' . implode("\n", $commandOutput));
            return $status;
        }
        $this->info('Tahoe leases have been renewed');
        return 0;        
    }

    protected function getNameForLogs()
    {
        return 'RenewLeasesCommand';
    }

}
