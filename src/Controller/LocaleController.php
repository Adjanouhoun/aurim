<?php

namespace App\Controller;

use App\EventSubscriber\LocaleSubscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleController extends AbstractController
{
    #[Route('/langue/{locale}', name: 'app_locale_switch', requirements: ['locale' => 'fr|en|ar'], methods: ['GET'])]
    public function switch(string $locale, Request $request): Response
    {
        if (!in_array($locale, LocaleSubscriber::SUPPORTED_LOCALES, true)) {
            throw $this->createNotFoundException();
        }

        $request->getSession()->set('aurim_locale', $locale);
        $target = (string) $request->query->get('returnTo', '/');
        if (!str_starts_with($target, '/') || str_starts_with($target, '//')) {
            $target = '/';
        }

        return $this->redirect($target);
    }
}
