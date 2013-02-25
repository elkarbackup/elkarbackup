<?php

namespace Binovo\ElkarBackupBundle\Listener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom login listener.
 */
class LoginListener
{
    private $container;

	/**
	 * Constructor
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container)
	{
        $this->container = $container;
	}

	/**
	 * Add the login event to the log record.
	 *
	 * @param  Event $event
	 */
	public function onSecurityInteractiveLogin(Event $event)
	{
        $logger   = $this->container->get('BnvWebLogger');
        $trans    = $this->container->get('translator');
        $username = $this->container->get('security.context')->getToken()->getUser()->getUsername();
        $msg = $trans->trans('User %username% logged in.', array('%username%' => $username), 'BinovoElkarBackup');
        $logger->info($msg, array('source' => 'Authentication'));
	}
}