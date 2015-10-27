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

class StartCommand extends LoggingCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('tahoe:start')
              ->setDescription('Runs the tahoe node. Just for console purposes');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = array('source' => 'StartCommand');

        $tahoeAlias = 'tahoe';
        $elkarbackupNodePath = '/var/lib/elkarbackup/.tahoe/';

        $command = $tahoeAlias . ' start ' . $elkarbackupNodePath;
        $commandOutput  = array();
        $status         = 0;
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            $this->err('Error starting Tahoe node: ' . implode("\n", $commandOutput));
            return $status;
        }
        $this->info('Tahoe node started: ' . implode("\n", $commandOutput));
        return 0;
    }

    protected function getNameForLogs()
    {
        return 'StartCommand';
    }

}
