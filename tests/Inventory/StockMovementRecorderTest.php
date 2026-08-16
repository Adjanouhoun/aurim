<?php

namespace App\Tests\Inventory;

use App\Entity\Inventory;
use App\Entity\Product;
use App\Entity\StockMovement;
use App\Entity\Warehouse;
use App\Inventory\StockMovementRecorder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class StockMovementRecorderTest extends TestCase
{
    public function testItRecordsDeltasAndResultingBalances(): void
    {
        $inventory = (new Inventory())
            ->setProduct(new Product())
            ->setWarehouse(new Warehouse())
            ->setQuantityOnHand(9)
            ->setQuantityReserved(2)
            ->setLowStockThreshold(7);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(StockMovement::class));

        $movement = (new StockMovementRecorder($entityManager))->record(
            $inventory,
            'order_committed',
            -2,
            0,
            'Commande expédiée.',
            'AUR-TEST',
        );

        self::assertInstanceOf(StockMovement::class, $movement);
        self::assertSame(-2, $movement->getQuantityOnHandDelta());
        self::assertSame(0, $movement->getQuantityReservedDelta());
        self::assertSame(9, $movement->getBalanceOnHand());
        self::assertSame(2, $movement->getBalanceReserved());
        self::assertSame('AUR-TEST', $movement->getReference());
        self::assertTrue($movement->triggersLowStockAlert());
    }

    public function testItIgnoresAnUnchangedStock(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $inventory = (new Inventory())->setProduct(new Product())->setWarehouse(new Warehouse());

        self::assertNull((new StockMovementRecorder($entityManager))->record($inventory, 'manual_adjustment', 0, 0, 'Sans changement.'));
    }
}
