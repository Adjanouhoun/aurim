<?php

namespace App\Order;

use App\Entity\CustomerOrder;
use App\Notification\OrderMailer;
use Doctrine\ORM\EntityManagerInterface;

final class OrderWorkflow
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderStockManager $stockManager,
        private readonly OrderMailer $orderMailer,
    ) {}

    /** @return array<string, string> */
    public function availableTransitions(CustomerOrder $order): array
    {
        return match ($order->getStatus()) {
            'pending_payment' => 'cash' === $order->getPaymentMethodType()
                ? ['preparing' => 'Mettre en préparation', 'cancelled' => 'Annuler la commande']
                : [],
            'paid' => ['preparing' => 'Mettre en préparation', 'cancelled' => 'Annuler la commande'],
            'preparing' => [
                'shipped' => 'pickup' === $order->getFulfillmentType() ? 'Prête au retrait' : 'Marquer comme expédiée',
                'cancelled' => 'Annuler la commande',
            ],
            'shipped' => array_filter([
                'delivered' => 'pickup' === $order->getFulfillmentType() ? 'Commande remise' : 'Marquer comme livrée',
                'delivery_failed' => 'delivery' === $order->getFulfillmentType() ? 'Signaler un échec de livraison' : null,
            ]),
            'delivery_failed' => ['shipped' => 'Relancer la livraison', 'cancelled' => 'Annuler la commande'],
            default => [],
        };
    }

    public function transition(CustomerOrder $order, string $targetStatus): void
    {
        if (!array_key_exists($targetStatus, $this->availableTransitions($order))) {
            throw new \DomainException('Cette action n’est pas autorisée pour le statut actuel de la commande.');
        }

        $order->setStatus($targetStatus);
        $this->stockManager->synchronize($order);
        $this->entityManager->flush();
        $this->orderMailer->sendStatusChanged($order, $targetStatus);
    }
}
