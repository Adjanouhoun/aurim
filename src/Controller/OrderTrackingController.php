<?php

namespace App\Controller;

use App\Entity\CustomerOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderTrackingController extends AbstractController
{
    #[Route('/suivi-commande', name: 'app_order_tracking', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = null;
        $error = null;
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('order-tracking', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }
            $reference = strtoupper(trim((string) $request->request->get('reference')));
            $email = mb_strtolower(trim((string) $request->request->get('email')));
            $order = $entityManager->getRepository(CustomerOrder::class)->findOneBy(['reference' => $reference, 'email' => $email]);
            if (!$order instanceof CustomerOrder) {
                $error = 'Aucune commande ne correspond à cette référence et cette adresse e-mail.';
            }
        }

        return $this->render('order/tracking.html.twig', ['order' => $order, 'error' => $error]);
    }
}
