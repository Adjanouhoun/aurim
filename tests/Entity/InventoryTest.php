<?php

namespace App\Tests\Entity;

use App\Entity\Inventory;
use PHPUnit\Framework\TestCase;

final class InventoryTest extends TestCase
{
    public function testAvailableQuantitySubtractsReservationsWithoutBecomingNegative(): void
    {
        $inventory = (new Inventory())
            ->setQuantityOnHand(8)
            ->setQuantityReserved(3);

        self::assertSame(5, $inventory->getAvailableQuantity());

        $inventory->setQuantityReserved(12);
        self::assertSame(0, $inventory->getAvailableQuantity());
    }

    public function testLowStockUsesAvailableQuantityAndConfiguredThreshold(): void
    {
        $inventory = (new Inventory())
            ->setQuantityOnHand(10)
            ->setQuantityReserved(4)
            ->setLowStockThreshold(5);

        self::assertFalse($inventory->isLowStock());

        $inventory->setQuantityReserved(5);
        self::assertTrue($inventory->isLowStock());
    }
}
