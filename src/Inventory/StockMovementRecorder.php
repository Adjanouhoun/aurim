<?php

namespace App\Inventory;

use App\Entity\Inventory;
use App\Entity\StockMovement;
use Doctrine\ORM\EntityManagerInterface;

final class StockMovementRecorder
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function record(
        Inventory $inventory,
        string $type,
        int $onHandDelta,
        int $reservedDelta,
        string $description,
        ?string $reference = null,
        ?string $performedBy = null,
    ): ?StockMovement {
        if (0 === $onHandDelta && 0 === $reservedDelta) {
            return null;
        }

        $currentAvailable = $inventory->getAvailableQuantity();
        $previousOnHand = $inventory->getQuantityOnHand() - $onHandDelta;
        $previousReserved = $inventory->getQuantityReserved() - $reservedDelta;
        $previousAvailable = max(0, $previousOnHand - $previousReserved);
        $triggersAlert = $currentAvailable <= $inventory->getLowStockThreshold()
            && $previousAvailable > $inventory->getLowStockThreshold();

        $movement = (new StockMovement())
            ->setProduct($inventory->getProduct())
            ->setWarehouse($inventory->getWarehouse())
            ->setMovementType($type)
            ->setQuantityOnHandDelta($onHandDelta)
            ->setQuantityReservedDelta($reservedDelta)
            ->setBalanceOnHand($inventory->getQuantityOnHand())
            ->setBalanceReserved($inventory->getQuantityReserved())
            ->setReference($reference)
            ->setDescription($description)
            ->setPerformedBy($performedBy)
            ->setTriggersLowStockAlert($triggersAlert);
        $this->entityManager->persist($movement);

        return $movement;
    }
}
