<?php

namespace Binovo\Tknika\BackupsBundle\Command;

use Binovo\Tknika\BackupsBundle\Lib\LoggingCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateKeyPairCommand extends LoggingCommand
{

    protected function getNameForLogs()
    {
        return 'GenerateKeyPairCommand';
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('tknikabackups:generate_keypair')
             ->setDescription('Generates the ssh keypair for the user running the backup jobs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command       = 'rm -f $HOME/.ssh/id_rsa $HOME/.ssh/id_rsa.pub && ssh-keygen -t rsa -N "" -C "Web requested key for tknikabackups." -f "$HOME/.ssh/id_rsa"';
        $commandOutput = array();
        $status        = 0;
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            $this->err('Command %command% failed. Diagnostic information follows: %output%',
                       array('%command%' => $command,
                             '%output%'  => "\n" . implode("\n", $commandOutput)));

        } else {
            $this->info('Command %command% succeeded with output: %output%',
                        array('%command%' => $command,
                              '%output%'  => implode("\n", $commandOutput)));
        }
        $this->flush();

        return $status;
    }
}
