<?php

namespace App\Controller\Admin;

use App\Entity\CustomerOrder;
use App\Entity\Payment;
use App\Order\OrderWorkflow;
use App\Payment\PaymentWorkflow;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/commandes')]
final class OrderWorkflowController extends AbstractController
{
    #[Route('/{id}', name: 'admin_order_workflow', methods: ['GET'])]
    public function show(CustomerOrder $order, OrderWorkflow $workflow, PaymentWorkflow $paymentWorkflow, EntityManagerInterface $entityManager): Response
    {
        $payment = $entityManager->getRepository(Payment::class)->findOneBy(['customerOrder' => $order]);

        return $this->render('admin/order/workflow.html.twig', [
            'order' => $order,
            'payment' => $payment,
            'paymentTransitions' => $payment instanceof Payment ? $paymentWorkflow->availableTransitions($payment) : [],
            'transitions' => $workflow->availableTransitions($order),
        ]);
    }

    #[Route('/{id}/statut/{status}', name: 'admin_order_transition', methods: ['POST'])]
    public function transition(CustomerOrder $order, string $status, Request $request, OrderWorkflow $workflow): Response
    {
        if (!$this->isCsrfTokenValid('order-transition-'.$order->getId().'-'.$status, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        try {
            $workflow->transition($order, $status);
            $this->addFlash('success', 'Le statut de la commande a été mis à jour et le client a été informé.');
        } catch (\DomainException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_order_workflow', ['id' => $order->getId()]);
    }
}
