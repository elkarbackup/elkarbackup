<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Logger;

use Monolog\Handler\AbstractProcessingHandler;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Binovo\Tknika\TknikaBackupsBundle\Entity\LogRecord;


use Monolog\Logger;

class LoggerHandler extends AbstractProcessingHandler implements ContainerAwareInterface
{
    private $container;
    public function __construct($level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $em = $this->container->get('doctrine')->getManager();
        $logRecord = new LogRecord($record['channel'],
                                   $record['datetime'],
                                   $record['level'],
                                   $record['level_name'],
                                   $record['message'],
                                   isset($record['context']['link'])    ? $record['context']['link']    : null,
                                   isset($record['context']['source'])  ? $record['context']['source']  : null,
                                   isset($record['extra']['user_id'])   ? $record['extra']['user_id']   : null,
                                   isset($record['extra']['user_name']) ? $record['extra']['user_name'] : null);
        $em->persist($logRecord);
        $em->flush();
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
