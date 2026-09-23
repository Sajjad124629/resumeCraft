<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class InertiaSubscriber implements EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Always add Vary: X-Inertia so the browser cache treats HTML and JSON separately
        $response->headers->set('Vary', 'X-Inertia', false);

        if ($request->headers->has('X-Inertia')) {
            $response->headers->set('Cache-Control', 'no-cache, private');
        }

        // Inertia protocol: 302 to 303 for PUT/PATCH/DELETE redirects
        if (
            $request->headers->has('X-Inertia')
            && in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'], true)
            && $response->getStatusCode() === 302
        ) {
            $response->setStatusCode(303);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }
}
