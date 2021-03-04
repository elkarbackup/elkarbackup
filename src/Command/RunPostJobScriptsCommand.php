<?php
namespace App\Command;

use App\Lib\BaseScriptsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Lib\LoggingCommand;

class RunPostJobScriptsCommand extends BaseScriptsCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('elkarbackup:run_post_job_scripts')
            ->setDescription('Runs specified job post scripts.')
            ->addArgument('job', InputArgument::REQUIRED, 'The ID of the job.')
            ->addArgument('status', InputArgument::OPTIONAL, 'The status of the last execution');
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
        
        $status = $input->getArgument('status');
        if (null == $status) {
            $status = self::ERR_CODE_OK;
        }
        if (! ctype_digit($status) && '-1' != $status && '-2' != $status) {
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
        $model = $this->prepareJobModel($job, self::TYPE_POST, $status);
        $result = $this->runJobScripts($model);
        $manager->flush();
        
        return $result;
    }
    
    protected function getNameForLogs()
    {
        return 'RunPostJobScriptsCommand';
    }
}