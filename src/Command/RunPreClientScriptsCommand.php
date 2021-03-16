<?php
namespace App\Command;

use App\Lib\BaseScriptsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Lib\LoggingCommand;

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
            return self::ERR_CODE_INPUT_ARG;
        }
        $client = $container
            ->get('doctrine')
            ->getRepository('App:Client')
            ->find($clientId);
        if (null == $client) {
            $this->err('Client not found');
            return self::ERR_CODE_ENTITY_NOT_FOUND;
        }
        $model = $this->prepareClientModel($client, self::TYPE_PRE);
        $result = $this->runClientScripts($model);
        $manager->flush();
        
        return $result;
    }
    
    protected function getNameForLogs()
    {
        return 'RunPreClientScriptsCommand';
    }
}