<?php
namespace Binovo\ElkarBackupBundle\Command;

use Binovo\ElkarBackupBundle\Lib\BaseScriptsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunPostJobScriptsCommand extends BaseScriptsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('elkarbackup:run_post_job_scripts')
            ->setDescription('Runs specified job post scripts.')
            ->addArgument('job', InputArgument::REQUIRED, 'The ID of the job.');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $manager = $container->get('doctrine')->getManager();
        
        $jobId = $input->getArgument('job');
        $job = $container
            ->get('doctrine')
            ->getRepository('BinovoElkarBackupBundle:Job')
            ->find($jobId);
        $stats = array(); //Los stats tienen variables aquí
        $stats['ELKARBACKUP_JOB_RUN_SIZE'] = 125764;
        $stats['ELKARBACKUP_JOB_STARTTIME'] = 1523353565;
        $stats['ELKARBACKUP_JOB_ENDTIME'] = 1523353565;
        $model = $this->prepareJobModel($job, 'POST', $stats);
        $result = $this->runJobScripts($model);
        $manager->flush();
        
        return $result;
    }
    
    protected function getNameForLogs()
    {
        return 'RunPostJobScriptsCommand';
    }
}