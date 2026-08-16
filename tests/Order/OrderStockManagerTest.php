<?php

namespace App\Tests\Order;

use App\Entity\CustomerOrder;
use App\Entity\Inventory;
use App\Entity\Market;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\Warehouse;
use App\Order\OrderStockManager;
use App\Inventory\StockMovementRecorder;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class OrderStockManagerTest extends TestCase
{
    public function testCancelledOrderReleasesReservedStockOnlyOnce(): void
    {
        [$manager, $order, $inventory] = $this->scenario('cancelled');

        $manager->synchronize($order);
        $manager->synchronize($order);

        self::assertSame(5, $inventory->getQuantityOnHand());
        self::assertSame(0, $inventory->getQuantityReserved());
        self::assertSame('released', $order->getInventoryStatus());
    }

    public function testShippedOrderCommitsReservedStockOnlyOnce(): void
    {
        [$manager, $order, $inventory] = $this->scenario('shipped');

        $manager->synchronize($order);
        $manager->synchronize($order);

        self::assertSame(3, $inventory->getQuantityOnHand());
        self::assertSame(0, $inventory->getQuantityReserved());
        self::assertSame('committed', $order->getInventoryStatus());
    }

    /** @return array{OrderStockManager, CustomerOrder, Inventory} */
    private function scenario(string $orderStatus): array
    {
        $market = (new Market())
            ->setCountryCode('SN')
            ->setName('Sénégal')
            ->setCurrencyCode('XOF');
        $warehouse = (new Warehouse())
            ->setCode('SN-DKR')
            ->setName('Dépôt Dakar')
            ->setMarket($market);
        $product = (new Product())->setName('Beurre corporel AURIM');
        $inventory = (new Inventory())
            ->setProduct($product)
            ->setWarehouse($warehouse)
            ->setQuantityOnHand(5)
            ->setQuantityReserved(2);
        $item = (new OrderItem())
            ->setProduct($product)
            ->setQuantity(2);
        $order = (new CustomerOrder())
            ->setReference('AUR-TEST')
            ->setMarket($market)
            ->setStatus($orderStatus)
            ->addItem($item);

        $warehouseRepository = $this->createMock(EntityRepository::class);
        $warehouseRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['market' => $market, 'central' => false, 'active' => true])
            ->willReturn($warehouse);

        $inventoryRepository = $this->createMock(EntityRepository::class);
        $inventoryRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['product' => $product, 'warehouse' => $warehouse])
            ->willReturn($inventory);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnCallback(
            static fn (string $class): EntityRepository => match ($class) {
                Warehouse::class => $warehouseRepository,
                Inventory::class => $inventoryRepository,
                default => throw new \LogicException('Unexpected repository '.$class),
            },
        );

        return [new OrderStockManager($entityManager, new StockMovementRecorder($entityManager)), $order, $inventory];
    }
}
