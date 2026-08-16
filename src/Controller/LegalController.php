<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_legal_notice', methods: ['GET'])]
    public function notice(): Response
    {
        return $this->renderPage('notice');
    }

    #[Route('/conditions-generales-de-vente', name: 'app_legal_terms', methods: ['GET'])]
    public function terms(): Response
    {
        return $this->renderPage('terms');
    }

    #[Route('/politique-de-confidentialite', name: 'app_legal_privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->renderPage('privacy');
    }

    #[Route('/politique-des-cookies', name: 'app_legal_cookies', methods: ['GET'])]
    public function cookies(): Response
    {
        return $this->renderPage('cookies');
    }

    private function renderPage(string $page): Response
    {
        return $this->render('legal/page.html.twig', ['legalPage' => $page]);
    }
}
