<?php
namespace Binovo\ElkarBackupBundle\Command;

use Binovo\ElkarBackupBundle\Lib\BaseScriptsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Binovo\ElkarBackupBundle\Lib\LoggingCommand;

class RunPreJobScriptsCommand extends BaseScriptsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('elkarbackup:run_pre_job_scripts')
            ->setDescription('Runs specified job pre scripts.')
            ->addArgument('job', InputArgument::REQUIRED, 'The ID of the job.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $manager = $container->get('doctrine')->getManager();
        
        $jobId = $input->getArgument('job');
        if (! ctype_digit($jobId)) {
            $this->err('Input argument not valid');
            return self::ERR_CODE_INPUT_ARG;
        }
        $job = $container
            ->get('doctrine')
            ->getRepository('BinovoElkarBackupBundle:Job')
            ->find($jobId);
        if (null == $job) {
            $this->err('Job not found');
            return self::ERR_CODE_ENTITY_NOT_FOUND;
        }
        $model = $this->prepareJobModel($job, 'PRE');
        $result = $this->runJobScripts($model);
        $manager->flush();
        
        return $result;
    }
    
    protected function getNameForLogs()
    {
        return 'RunPreJobScriptsCommand';
    }
}