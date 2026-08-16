<?php

namespace App\DataFixtures;

use App\Entity\Market;
use App\Entity\ShippingRate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class DemoShippingRateFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    /**
     * Les montants sont exprimés dans l'unité minimale de la devise du marché.
     *
     * @var list<array{string, string, string, string, ?string, int, int, int}>
     */
    private const RATES = [
        ['MR', 'pickup', 'Dépôt AURIM Nouakchott', 'Nouakchott', 'Cité plage Atoi 435', 0, 1, 3],
        ['MR', 'delivery', 'Livraison à domicile Nouakchott', 'Nouakchott', null, 15000, 1, 3],
        ['MR', 'delivery', 'Livraison à domicile Nouadhibou', 'Nouadhibou', null, 30000, 2, 5],

        ['SN', 'pickup', 'Dépôt AURIM Dakar', 'Dakar', 'Adresse à confirmer — Dakar', 0, 1, 2],
        ['SN', 'delivery', 'Livraison à domicile Dakar', 'Dakar', null, 2500, 1, 3],
        ['SN', 'delivery', 'Livraison à domicile Thiès', 'Thiès', null, 3500, 2, 4],
        ['SN', 'delivery', 'Livraison à domicile Saint-Louis', 'Saint-Louis', null, 4500, 3, 6],

        ['ML', 'pickup', 'Dépôt AURIM Bamako', 'Bamako', 'Adresse à confirmer — Bamako', 0, 1, 2],
        ['ML', 'delivery', 'Livraison à domicile Bamako', 'Bamako', null, 2500, 1, 3],
        ['ML', 'delivery', 'Livraison à domicile Sikasso', 'Sikasso', null, 4500, 2, 5],
        ['ML', 'delivery', 'Livraison à domicile Kayes', 'Kayes', null, 5000, 3, 6],

        ['GN', 'pickup', 'Dépôt AURIM Conakry', 'Conakry', 'Adresse à confirmer — Conakry', 0, 1, 2],
        ['GN', 'delivery', 'Livraison à domicile Conakry', 'Conakry', null, 30000, 1, 3],
        ['GN', 'delivery', 'Livraison à domicile Kindia', 'Kindia', null, 50000, 2, 5],
        ['GN', 'delivery', 'Livraison à domicile Kankan', 'Kankan', null, 75000, 3, 7],
    ];

    public static function getGroups(): array
    {
        return ['demo-shipping'];
    }

    public function getDependencies(): array
    {
        return [CatalogFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $markets = [];
        foreach (['MR', 'SN', 'ML', 'GN'] as $countryCode) {
            $market = $manager->getRepository(Market::class)->findOneBy(['countryCode' => $countryCode]);
            if (!$market instanceof Market) {
                throw new \LogicException(sprintf('Le marché %s doit être chargé avant les tarifs de livraison.', $countryCode));
            }
            $markets[$countryCode] = $market;

            foreach ($manager->getRepository(ShippingRate::class)->findBy(['market' => $market]) as $existingRate) {
                $existingRate->setActive(false);
            }
        }

        foreach (self::RATES as [$countryCode, $type, $label, $city, $address, $amountMinor, $minimumDays, $maximumDays]) {
            $market = $markets[$countryCode];
            $rate = $manager->getRepository(ShippingRate::class)->findOneBy([
                'market' => $market,
                'fulfillmentType' => $type,
                'label' => $label,
            ]) ?? (new ShippingRate())
                ->setMarket($market)
                ->setFulfillmentType($type)
                ->setLabel($label);

            $rate
                ->setCity($city)
                ->setAddressLine($address)
                ->setAmountMinor($amountMinor)
                ->setMinimumDays($minimumDays)
                ->setMaximumDays($maximumDays)
                ->setActive(true);
            $manager->persist($rate);
        }

        $manager->flush();
    }
}
