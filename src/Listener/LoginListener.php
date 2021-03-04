<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace App\Listener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Custom login listener.
 */
class LoginListener
{
    private $container;
    private $security;
	/**
	 * Constructor
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container, Security $security)
	{
        $this->container = $container;
        $this->security = $security;
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
        $username = $this->security->getToken()->getUser()->getUsername();
        $msg = $trans->trans('User %username% logged in.', array('%username%' => $username), 'BinovoElkarBackup');
        $logger->info($msg, array('source' => 'Authentication'));

        $user = $this->security->getToken()->getUser();
        $locale = $user->getLanguage();
        $request = $event->getRequest();
        $request->getSession()->set('_locale', $locale);
	}
}
