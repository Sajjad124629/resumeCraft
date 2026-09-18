<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private string $defaultLocale;

    public function __construct(string $defaultLocale = 'en')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        $sessionLocale = null;
        if ($request->hasSession()) {
            $sessionLocale = $request->getSession()->get('_locale');
        }

        if ($sessionLocale) {
            $request->setLocale($sessionLocale);
        } elseif ($localeCookie = $request->cookies->get('_locale')) {
            $request->setLocale($localeCookie);
            if ($request->hasSession()) {
                $request->getSession()->set('_locale', $localeCookie);
            }
        } else {
            $request->setLocale($this->defaultLocale);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Must be registered before (i.e. with a higher priority than) the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
