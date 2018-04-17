<?php
namespace Binovo\ElkarBackupBundle\Command;

use Binovo\ElkarBackupBundle\Lib\BaseScriptsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Binovo\ElkarBackupBundle\Lib\LoggingCommand;

class RunPreClientScriptsCommand extends BaseScriptsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('elkarbackup:run_pre_client_scripts')
            ->setDescription('Runs specified client pre scripts.')
            ->addArgument('client', InputArgument::REQUIRED, 'The ID of the client.');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $manager = $container->get('doctrine')->getManager();
        
        $clientId = $input->getArgument('client');
        if (! ctype_digit($clientId)) {
            $this->err('Input argument not valid');
            return LoggingCommand::ERR_CODE_INPUT_ARG;
        }
        $client = $container
            ->get('doctrine')
            ->getRepository('BinovoElkarBackupBundle:Client')
            ->find($clientId);
        if (null == $client) {
            $this->err('Client not found');
            return LoggingCommand::ERR_CODE_ENTITY_NOT_FOUND;
        }
        $model = $this->prepareClientModel($client, 'PRE');
        $result = $this->runClientScripts($model);
        $manager->flush();
        
        return $result;
    }
    
    protected function getNameForLogs()
    {
        return 'RunPreClientScriptsCommand';
    }
}