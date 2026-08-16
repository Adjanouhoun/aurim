<?php

namespace App\DataFixtures;

use App\Entity\Inventory;
use App\Entity\Product;
use App\Entity\Warehouse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class DemoInventoryFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    /**
     * Quantités de démonstration par SKU et par entrepôt.
     *
     * @var array<string, array<string, int>>
     */
    private const QUANTITIES = [
        'TVCNBC150' => ['US-CENTRAL' => 140, 'MR-LOCAL' => 24, 'SN-LOCAL' => 18, 'ML-LOCAL' => 15, 'GN-LOCAL' => 12],
        'TVCNGT120' => ['US-CENTRAL' => 120, 'MR-LOCAL' => 20, 'SN-LOCAL' => 16, 'ML-LOCAL' => 14, 'GN-LOCAL' => 10],
        'TVCNRBS30' => ['US-CENTRAL' => 100, 'MR-LOCAL' => 18, 'SN-LOCAL' => 14, 'ML-LOCAL' => 12, 'GN-LOCAL' => 10],
        'TVCNBC60' => ['US-CENTRAL' => 110, 'MR-LOCAL' => 20, 'SN-LOCAL' => 15, 'ML-LOCAL' => 13, 'GN-LOCAL' => 11],
        'TVCNRBBW300' => ['US-CENTRAL' => 160, 'MR-LOCAL' => 28, 'SN-LOCAL' => 22, 'ML-LOCAL' => 18, 'GN-LOCAL' => 15],
        'TVCNRBL300' => ['US-CENTRAL' => 150, 'MR-LOCAL' => 25, 'SN-LOCAL' => 20, 'ML-LOCAL' => 17, 'GN-LOCAL' => 14],
        'TVCNRBBS450' => ['US-CENTRAL' => 130, 'MR-LOCAL' => 22, 'SN-LOCAL' => 18, 'ML-LOCAL' => 15, 'GN-LOCAL' => 12],
        'TVCNRBB350' => ['US-CENTRAL' => 125, 'MR-LOCAL' => 20, 'SN-LOCAL' => 16, 'ML-LOCAL' => 14, 'GN-LOCAL' => 11],
        'TVCNRBO200' => ['US-CENTRAL' => 115, 'MR-LOCAL' => 18, 'SN-LOCAL' => 15, 'ML-LOCAL' => 12, 'GN-LOCAL' => 10],
    ];

    public static function getGroups(): array
    {
        return ['demo-stocks'];
    }

    public function getDependencies(): array
    {
        return [CatalogFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::QUANTITIES as $sku => $warehouseQuantities) {
            $product = $manager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
            if (!$product instanceof Product) {
                throw new \LogicException(sprintf('Le produit %s doit être chargé avant le stock de démonstration.', $sku));
            }

            foreach ($warehouseQuantities as $warehouseCode => $quantity) {
                $warehouse = $manager->getRepository(Warehouse::class)->findOneBy(['code' => $warehouseCode]);
                if (!$warehouse instanceof Warehouse) {
                    throw new \LogicException(sprintf('L’entrepôt %s doit être chargé avant le stock de démonstration.', $warehouseCode));
                }

                $inventory = $manager->getRepository(Inventory::class)->findOneBy([
                    'product' => $product,
                    'warehouse' => $warehouse,
                ]) ?? (new Inventory())->setProduct($product)->setWarehouse($warehouse);

                $inventory
                    ->setQuantityOnHand(max($quantity, $inventory->getQuantityReserved()))
                    ->setLowStockThreshold($warehouse->isCentral() ? 20 : 5);
                $manager->persist($inventory);
            }
        }

        $manager->flush();
    }
}
