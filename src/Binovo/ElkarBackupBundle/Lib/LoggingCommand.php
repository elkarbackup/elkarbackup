<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Lib;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * This class can be used as base class for those commands which need
 * to log "things" in the elkarbackup application.
 */
abstract class LoggingCommand extends ContainerAwareCommand
{
    abstract protected function getNameForLogs();

    protected function generateClientRoute($id)
    {
        return $this->getUrlPrefix() . $this->getContainer()->get('router')->generate('editClient', array('id' => $id));
    }

    protected function generateJobRoute($idJob, $idClient)
    {
        return $this->getUrlPrefix() . $this->getContainer()->get('router')->generate('editJob',
                                                                                      array('idClient' => $idClient,
                                                                                            'idJob'    => $idJob));
    }

    protected function getUrlPrefix()
    {
        return $this->getContainer()->getParameter('url_prefix');
    }

    /**
     * Flushes doctrine in order to store log messages
     * permanently. Notices that any other pending work units will be
     * flushed too.
     */
    protected function flush()
    {
        $this->getContainer()->get('doctrine')->getManager()->flush();
    }

    protected function err($msg, $translatorParams = array(), $context = array())
    {
        $logger = $this->getContainer()->get('BnvWebLogger');
        $translator = $this->getContainer()->get('translator');
        $context = array_merge(array('source' => $this->getNameForLogs()), $context);
        $logger->err($translator->trans($msg, $translatorParams, 'BinovoElkarBackup'), $context);
    }

    protected function info($msg, $translatorParams = array(), $context = array())
    {
        $logger = $this->getContainer()->get('BnvWebLogger');
        $translator = $this->getContainer()->get('translator');
        $context = array_merge(array('source' => $this->getNameForLogs()), $context);
        $logger->info($translator->trans($msg, $translatorParams, 'BinovoElkarBackup'), $context);
    }

    protected function warn($msg, $translatorParams = array(), $context = array())
    {
        $logger = $this->getContainer()->get('BnvWebLogger');
        $translator = $this->getContainer()->get('translator');
        $context = array_merge(array('source' => $this->getNameForLogs()), $context);
        $logger->warn($translator->trans($msg, $translatorParams, 'BinovoElkarBackup'), $context);
    }
}
