<?php
namespace App\Service;

use App\Service\TranslatorService;
use Symfony\Bridge\Monolog\Logger;

class LoggerService
{
    private $logger;
    private $translator;
    
    public function __construct(Logger $logger, TranslatorService $translator)
    {
        $this->logger     = $logger;
        $this->translator = $translator;
    }

    public function debug($msg, $translatorParams = array(), $context = array())
    {
        $context = array_merge(array('source' => 'DefaultController'), $context);
        $this->logger->debug(
            $this->translator->trans($msg, $translatorParams, 'BinovoElkarBackup'),
            $context
            );
    }

    public function err($msg, $translatorParams = array(), $context = array())
    {
        $context = array_merge(array('source' => 'DefaultController'), $context);
        $this->logger->error(
            $this->translator->trans($msg, $translatorParams, 'BinovoElkarBackup'),
            $context
            );
    }

    public function info($msg, $translatorParams = array(), $context = array())
    {
        $context = array_merge(array('source' => 'DefaultController'), $context);
        $this->logger->info(
            $this->translator->trans($msg, $translatorParams, 'BinovoElkarBackup'),
            $context
            );
    }

    public function warn($msg, $translatorParams = array(), $context = array())
    {
        $context = array_merge(array('source' => 'DefaultController'), $context);
        $this->logger->warning(
            $this->translator->trans($msg, $translatorParams, 'BinovoElkarBackup'),
            $context
            );
    }
}

