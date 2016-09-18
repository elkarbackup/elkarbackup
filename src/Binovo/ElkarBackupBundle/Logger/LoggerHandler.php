<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Logger;

use Binovo\ElkarBackupBundle\Entity\Job;
use Binovo\ElkarBackupBundle\Entity\LogRecord;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This handler writes the log to the Database using the LogRecord
 * entity. It is important to call flush after any of the log
 * generating calls, otherwise the log entries will NOT be saved.
 */
class LoggerHandler extends AbstractProcessingHandler implements ContainerAwareInterface
{
    private $container;
    private $messages;
    private $isRecordingMessage;

    public function __construct($level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->messages = array();
        $this->isRecordingMessages = false;
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
                                   !empty($record['extra']['user_id'])   ? $record['extra']['user_id']   : null,
                                   isset($record['extra']['user_name']) ? $record['extra']['user_name'] : null);
        $em->persist($logRecord);
        if ($this->isRecordingMessages) {
            $this->messages[] = $logRecord;
        }
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function clearMessages()
    {
        $this->messages = array();
    }

    public function startRecordingMessages()
    {
        $this->isRecordingMessages = true;
    }

    public function stopRecordingMessages()
    {
        $this->isRecordingMessages = false;
    }
}
