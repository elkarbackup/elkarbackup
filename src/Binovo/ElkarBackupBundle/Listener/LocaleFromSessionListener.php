<?php

namespace Binovo\ElkarBackupBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Listener to set the locale from the session.
 */
class LocaleFromSessionListener
{
    private $defaultLocale;
    private $container = null;

    public function __construct($defaultLocale = 'en')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function setContainer($c)
    {
        $this->container = $c;
    }

	/**
	 * Set the locale from the session. If not set in the session use
	 * the preferred locale according to the http headers.
	 *
	 * @param  Event $event
	 */
	public function onKernelRequest(GetResponseEvent $event)
	{
        $request = $event->getRequest();
        $sessionLocale = $request->getSession()->get('_locale');
        if ($sessionLocale) {
            $request->setLocale($sessionLocale);

            return;
        }
        $supportedLocales = $this->container->getParameter('supported_locales');
        foreach ($request->getLanguages() as $acceptedLocale) {
            if (false !== array_search($acceptedLocale, $supportedLocales)) {
                $request->setLocale($acceptedLocale);
                $request->getSession()->set('_locale', $acceptedLocale);

                return;
            }
        }
        $request->setLocale($this->defaultLocale);
        $request->getSession()->set('_locale', $this->defaultLocale);
	}

    static public function getSubscribedEvents()
    {
        return array(
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => array(array('onKernelRequest', 17)),
            );
    }
}