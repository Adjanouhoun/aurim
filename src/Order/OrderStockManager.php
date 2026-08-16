<?php

namespace App\Order;

use App\Entity\CustomerOrder;
use App\Entity\Inventory;
use App\Entity\Warehouse;
use App\Inventory\StockMovementRecorder;
use Doctrine\ORM\EntityManagerInterface;

final class OrderStockManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StockMovementRecorder $movementRecorder,
    ) {}

    public function synchronize(CustomerOrder $order): void
    {
        if ('reserved' !== $order->getInventoryStatus()) {
            return;
        }

        if (in_array($order->getStatus(), ['cancelled', 'payment_failed', 'refunded'], true)) {
            $this->release($order);
            return;
        }

        if ('shipped' === $order->getStatus()) {
            $this->commit($order);
        }
    }

    private function release(CustomerOrder $order): void
    {
        foreach ($this->inventories($order) as [$inventory, $quantity]) {
            $previousReserved = $inventory->getQuantityReserved();
            $inventory->setQuantityReserved(max(0, $previousReserved - $quantity));
            $this->movementRecorder->record(
                $inventory,
                'order_released',
                0,
                $inventory->getQuantityReserved() - $previousReserved,
                'Libération du stock réservé après annulation ou échec de la commande.',
                $order->getReference(),
            );
        }
        $order->setInventoryStatus('released');
    }

    private function commit(CustomerOrder $order): void
    {
        foreach ($this->inventories($order) as [$inventory, $quantity]) {
            $previousOnHand = $inventory->getQuantityOnHand();
            $previousReserved = $inventory->getQuantityReserved();
            $inventory
                ->setQuantityReserved(max(0, $previousReserved - $quantity))
                ->setQuantityOnHand(max(0, $previousOnHand - $quantity));
            $this->movementRecorder->record(
                $inventory,
                'order_committed',
                $inventory->getQuantityOnHand() - $previousOnHand,
                $inventory->getQuantityReserved() - $previousReserved,
                'Déduction définitive du stock lors de l’expédition ou de la mise à disposition.',
                $order->getReference(),
            );
        }
        $order->setInventoryStatus('committed');
    }

    /** @return list<array{Inventory, int}> */
    private function inventories(CustomerOrder $order): array
    {
        $warehouse = $this->entityManager->getRepository(Warehouse::class)->findOneBy([
            'market' => $order->getMarket(),
            'central' => false,
            'active' => true,
        ]);
        if (!$warehouse instanceof Warehouse) {
            return [];
        }

        $result = [];
        foreach ($order->getItems() as $item) {
            $inventory = $this->entityManager->getRepository(Inventory::class)->findOneBy([
                'product' => $item->getProduct(),
                'warehouse' => $warehouse,
            ]);
            if ($inventory instanceof Inventory) {
                $result[] = [$inventory, $item->getQuantity()];
            }
        }

        return $result;
    }
}
