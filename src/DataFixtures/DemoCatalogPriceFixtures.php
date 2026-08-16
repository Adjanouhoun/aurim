<?php

namespace App\DataFixtures;

use App\Entity\Market;
use App\Entity\MarketPrice;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class DemoCatalogPriceFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    /**
     * Prix de démonstration exprimés dans l'unité minimale de chaque devise.
     *
     * @var array<string, array<string, int>>
     */
    private const PRICES = [
        'TVCNBC150' => ['MR' => 65000, 'SN' => 11900, 'ML' => 11500, 'GN' => 175000],
        'TVCNGT120' => ['MR' => 55000, 'SN' => 10900, 'ML' => 10500, 'GN' => 160000],
        'TVCNRBS30' => ['MR' => 85000, 'SN' => 15900, 'ML' => 15500, 'GN' => 235000],
        'TVCNBC60' => ['MR' => 75000, 'SN' => 13900, 'ML' => 13500, 'GN' => 205000],
        'TVCNRBBW300' => ['MR' => 70000, 'SN' => 12900, 'ML' => 12500, 'GN' => 190000],
        'TVCNRBL300' => ['MR' => 80000, 'SN' => 14900, 'ML' => 14500, 'GN' => 220000],
        'TVCNRBBS450' => ['MR' => 90000, 'SN' => 16900, 'ML' => 16500, 'GN' => 250000],
        'TVCNRBB350' => ['MR' => 95000, 'SN' => 17900, 'ML' => 17500, 'GN' => 265000],
        'TVCNRBO200' => ['MR' => 85000, 'SN' => 15900, 'ML' => 15500, 'GN' => 235000],
    ];

    public static function getGroups(): array
    {
        return ['demo-prices'];
    }

    public function getDependencies(): array
    {
        return [CatalogFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::PRICES as $sku => $marketPrices) {
            $product = $manager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
            if (!$product instanceof Product) {
                throw new \LogicException(sprintf('Le produit %s doit être chargé avant les prix de démonstration.', $sku));
            }

            foreach ($marketPrices as $countryCode => $amountMinor) {
                $market = $manager->getRepository(Market::class)->findOneBy(['countryCode' => $countryCode]);
                if (!$market instanceof Market) {
                    throw new \LogicException(sprintf('Le marché %s doit être chargé avant les prix de démonstration.', $countryCode));
                }

                $price = $manager->getRepository(MarketPrice::class)->findOneBy([
                    'product' => $product,
                    'market' => $market,
                ]) ?? (new MarketPrice())->setProduct($product)->setMarket($market);
                $price->setAmountMinor($amountMinor)->setPublished(true);
                $manager->persist($price);
            }
        }

        $manager->flush();
    }
}
