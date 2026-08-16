<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    public const SUPPORTED_LOCALES = ['fr', 'en', 'ar'];

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !$request->hasPreviousSession()) {
            return;
        }

        if (str_starts_with($request->getPathInfo(), '/admin')) {
            $request->setLocale('fr');

            return;
        }

        $locale = (string) $request->getSession()->get('aurim_locale', 'fr');
        if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $request->setLocale($locale);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 20]];
    }
}
