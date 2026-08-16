<?php

namespace App\Controller;

use App\Entity\Market;
use App\Store\StoreContext;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StoreController extends AbstractController
{
    #[Route('/choisir-pays', name: 'app_market_select', methods: ['POST'])]
    public function selectMarket(Request $request, StoreContext $context, ManagerRegistry $doctrine): Response
    {
        if (!$this->isCsrfTokenValid('select-market', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $market = $doctrine->getRepository(Market::class)->findOneBy([
            'countryCode' => strtoupper((string) $request->request->get('market')),
            'active' => true,
        ]);
        if ($market instanceof Market && in_array($market, $context->getMarkets(), true)) {
            $context->select($market);
        }

        return $this->redirect((string) ($request->headers->get('referer') ?: $this->generateUrl('app_catalog')));
    }
}
