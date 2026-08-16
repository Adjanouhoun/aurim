<?php

namespace App\Controller\Admin;

use App\Entity\Payment;
use App\Payment\PaymentWorkflow;
use App\Security\AdminMarketAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PaymentWorkflowController extends AbstractController
{
    #[Route('/admin/paiements/{id}/statut/{status}', name: 'admin_payment_transition', methods: ['POST'])]
    public function transition(Payment $payment, string $status, Request $request, PaymentWorkflow $workflow, AdminMarketAccess $marketAccess): Response
    {
        $marketAccess->denyUnlessGranted($payment->getCustomerOrder()->getMarket());
        if (!$this->isCsrfTokenValid('payment-transition-'.$payment->getId().'-'.$status, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        try {
            $workflow->transition($payment, $status, trim((string) $request->request->get('external_reference')));
            $this->addFlash('success', 'Le paiement et la commande ont été mis à jour.');
        } catch (\DomainException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_order_workflow', ['id' => $payment->getCustomerOrder()->getId()]);
    }
}
