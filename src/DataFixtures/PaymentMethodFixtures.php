<?php

namespace App\DataFixtures;

use App\Entity\Market;
use App\Entity\PaymentMethod;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class PaymentMethodFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['payment-methods'];
    }

    public function load(ObjectManager $manager): void
    {
        $marketDefinitions = [
            'MR' => ['Mauritanie', 'MRU'],
            'SN' => ['Sénégal', 'XOF'],
            'ML' => ['Mali', 'XOF'],
            'GN' => ['Guinée', 'GNF'],
        ];

        $methods = [
            ['SN', 'orange_money_sn', 'Orange Money Sénégal', 'mobile_money_manual', 'both'],
            ['SN', 'wave_sn', 'Wave Sénégal', 'mobile_money_manual', 'both'],
            ['ML', 'orange_money_ml', 'Orange Money Mali', 'mobile_money_manual', 'both'],
            ['ML', 'moov_money_ml', 'Moov Money Mali', 'mobile_money_manual', 'both'],
            ['GN', 'orange_money_gn', 'Orange Money Guinée', 'mobile_money_manual', 'both'],
            ['GN', 'mtn_money_gn', 'MTN Mobile Money Guinée', 'mobile_money_manual', 'both'],
            ['MR', 'bankily_mr', 'Bankily Mauritanie', 'mobile_money_manual', 'both'],
            ['MR', 'masrvi_mr', 'Masrvi Mauritanie', 'mobile_money_manual', 'both'],
            ['SN', 'cash_pickup_sn', 'Espèces au dépôt', 'cash', 'pickup'],
            ['ML', 'cash_pickup_ml', 'Espèces au dépôt', 'cash', 'pickup'],
            ['GN', 'cash_pickup_gn', 'Espèces au dépôt', 'cash', 'pickup'],
            ['MR', 'cash_pickup_mr', 'Espèces au dépôt', 'cash', 'pickup'],
        ];

        $markets = [];
        foreach ($marketDefinitions as $countryCode => [$name, $currency]) {
            $market = $manager->getRepository(Market::class)->findOneBy(['countryCode' => $countryCode]);
            if (!$market instanceof Market) {
                $market = (new Market())->setCountryCode($countryCode);
            }
            $market->setName($name)->setCurrencyCode($currency)->setActive(true);
            $manager->persist($market);
            $markets[$countryCode] = $market;
        }

        foreach ($methods as [$countryCode, $code, $name, $type, $scope]) {
            $method = $manager->getRepository(PaymentMethod::class)->findOneBy([
                'market' => $markets[$countryCode],
                'code' => $code,
            ]);
            if (!$method instanceof PaymentMethod && 'cash' === $type) {
                $method = $manager->getRepository(PaymentMethod::class)->findOneBy([
                    'market' => $markets[$countryCode],
                    'type' => 'cash',
                    'fulfillmentScope' => 'pickup',
                ]);
                if ($method instanceof PaymentMethod) {
                    continue;
                }
            }
            if (!$method instanceof PaymentMethod) {
                $method = (new PaymentMethod())
                    ->setMarket($markets[$countryCode])
                    ->setCode($code)
                    ->setActive('cash' === $type);
            }
            $method
                ->setName($name)
                ->setType($type)
                ->setFulfillmentScope($scope)
                ->setInstructions('cash' === $type
                    ? 'Réglez le montant total en espèces lors du retrait de votre commande au dépôt.'
                    : 'Effectuez le transfert, puis attendez la validation manuelle de votre commande par AURIM.');
            $manager->persist($method);
        }

        $manager->flush();
    }
}
