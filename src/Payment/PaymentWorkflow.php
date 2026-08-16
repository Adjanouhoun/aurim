<?php

namespace App\Payment;

use App\Entity\Payment;
use App\Notification\OrderMailer;
use App\Order\OrderStockManager;
use Doctrine\ORM\EntityManagerInterface;

final class PaymentWorkflow
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderStockManager $stockManager,
        private readonly OrderMailer $orderMailer,
    ) {}

    /** @return array<string, string> */
    public function availableTransitions(Payment $payment): array
    {
        return match ($payment->getStatus()) {
            'pending' => 'mobile_money_manual' === $payment->getMethod()->getType()
                ? ['received' => 'Confirmer le paiement reçu', 'failed' => 'Marquer le paiement comme échoué']
                : ['received' => 'Confirmer l’encaissement en espèces'],
            'received' => ['refunded' => 'Enregistrer un remboursement'],
            default => [],
        };
    }

    public function transition(Payment $payment, string $targetStatus, ?string $externalReference = null): void
    {
        if (!array_key_exists($targetStatus, $this->availableTransitions($payment))) {
            throw new \DomainException('Cette action de paiement n’est pas autorisée.');
        }

        $order = $payment->getCustomerOrder();
        if ('received' === $targetStatus) {
            $payment->setStatus('received')->setExternalReference($externalReference ?: null);
            if (!in_array($order->getStatus(), ['preparing', 'shipped', 'delivered'], true)) {
                $order->setStatus('preparing');
            }
        } elseif ('failed' === $targetStatus) {
            $payment->setStatus('failed');
            $order->setStatus('payment_failed');
        } elseif ('refunded' === $targetStatus) {
            $payment->setStatus('refunded');
            $order->setStatus('refunded');
        }

        $this->stockManager->synchronize($order);
        $this->entityManager->flush();

        if ('received' === $targetStatus && 'mobile_money_manual' === $payment->getMethod()->getType()) {
            $this->orderMailer->sendPaymentReceived($payment);
        } elseif (in_array($targetStatus, ['failed', 'refunded'], true)) {
            $this->orderMailer->sendStatusChanged($order, $order->getStatus());
        }
    }
}
