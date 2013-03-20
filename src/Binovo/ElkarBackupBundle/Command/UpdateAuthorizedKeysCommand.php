<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Command;

use Binovo\ElkarBackupBundle\Lib\LoggingCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAuthorizedKeysCommand extends LoggingCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('elkarbackup:update_authorized_keys')
              ->addArgument('content', InputArgument::REQUIRED, 'Content of the file')
              ->setDescription('Updates the $HOME/.ssh/authorized_keys file with the provided contents.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = array('source' => 'UpdateAuthorizedKeysCommand');
        $content = $input->getArgument('content');
        $filename = getenv('HOME') . '/.ssh/authorized_keys';
        if (file_put_contents($filename, $content)) {
            $this->info('Authorized_keys file (%filename%) updated.', array('%filename%' => $filename));

            return 0;
        } else {
            $logger->err('Error updating authorized_keys file (%filename%)', array('%filename%' => $filename));

            return 1;
        }
    }

    protected function getNameForLogs()
    {
        return 'UpdateAuthorizedKeysCommand';
    }

}
