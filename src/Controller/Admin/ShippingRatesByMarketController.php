<?php

namespace App\Controller\Admin;

use App\Entity\Market;
use App\Entity\ShippingRate;
use App\Security\AdminMarketAccess;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ShippingRatesByMarketController extends AbstractController
{
    #[AdminRoute('/tarifs-livraison-par-pays', name: 'shipping_rates_by_market', options: ['methods' => ['GET', 'POST']])]
    public function index(Request $request, EntityManagerInterface $entityManager, AdminMarketAccess $marketAccess): Response
    {
        $markets = $entityManager->createQueryBuilder()
            ->select('market')
            ->from(Market::class, 'market')
            ->andWhere('market.countryCode != :internalMarket')
            ->setParameter('internalMarket', 'US')
            ->addOrderBy("CASE WHEN market.countryCode = 'MR' THEN 1 WHEN market.countryCode = 'SN' THEN 2 WHEN market.countryCode = 'ML' THEN 3 WHEN market.countryCode = 'GN' THEN 4 ELSE 5 END", 'ASC')
            ->getQuery()
            ->getResult();
        $markets = $marketAccess->filterMarkets($markets);
        $selectedMarket = $this->selectMarket($markets, strtoupper(trim((string) $request->query->get('pays'))));
        $rates = $selectedMarket instanceof Market
            ? $entityManager->getRepository(ShippingRate::class)->findBy(['market' => $selectedMarket], ['fulfillmentType' => 'ASC', 'city' => 'ASC', 'label' => 'ASC'])
            : [];

        if ($request->isMethod('POST') && $selectedMarket instanceof Market) {
            return $this->save($request, $entityManager, $selectedMarket, $rates);
        }

        return $this->render('admin/shipping_rate/by_market.html.twig', [
            'markets' => $markets,
            'selectedMarket' => $selectedMarket,
            'rates' => $rates,
            'activeRateCount' => count(array_filter($rates, static fn (ShippingRate $rate): bool => $rate->isActive())),
            'deliveryRateCount' => count(array_filter($rates, static fn (ShippingRate $rate): bool => 'delivery' === $rate->getFulfillmentType())),
            'pickupRateCount' => count(array_filter($rates, static fn (ShippingRate $rate): bool => 'pickup' === $rate->getFulfillmentType())),
            'currencyDecimals' => $selectedMarket instanceof Market ? $this->currencyDecimals($selectedMarket->getCurrencyCode()) : 0,
            'currencyDivisor' => $selectedMarket instanceof Market ? 10 ** $this->currencyDecimals($selectedMarket->getCurrencyCode()) : 1,
        ]);
    }

    /** @param list<Market> $markets */
    private function selectMarket(array $markets, string $requestedCode): ?Market
    {
        foreach ($markets as $market) {
            if ($market->getCountryCode() === $requestedCode) {
                return $market;
            }
        }

        return $markets[0] ?? null;
    }

    /** @param list<ShippingRate> $rates */
    private function save(Request $request, EntityManagerInterface $entityManager, Market $market, array $rates): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('shipping-rates-market-'.$market->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Le formulaire a expiré. Rechargez la page et réessayez.');
        }

        $labels = $request->request->all('labels');
        $cities = $request->request->all('cities');
        $types = $request->request->all('types');
        $addresses = $request->request->all('addresses');
        $amounts = $request->request->all('amounts');
        $minimumDays = $request->request->all('minimum_days');
        $maximumDays = $request->request->all('maximum_days');
        $active = $request->request->all('active');

        try {
            foreach ($rates as $rate) {
                $id = (string) $rate->getId();
                $this->applyValues($rate, $market, [
                    'label' => $labels[$id] ?? '',
                    'city' => $cities[$id] ?? '',
                    'type' => $types[$id] ?? '',
                    'address' => $addresses[$id] ?? '',
                    'amount' => $amounts[$id] ?? '',
                    'minimumDays' => $minimumDays[$id] ?? '',
                    'maximumDays' => $maximumDays[$id] ?? '',
                    'active' => isset($active[$id]),
                ]);
            }

            $newValues = $request->request->all('new_rate');
            if ('' !== trim((string) ($newValues['label'] ?? ''))) {
                $newRate = (new ShippingRate())->setMarket($market);
                $this->applyValues($newRate, $market, [
                    'label' => $newValues['label'] ?? '',
                    'city' => $newValues['city'] ?? '',
                    'type' => $newValues['type'] ?? '',
                    'address' => $newValues['address'] ?? '',
                    'amount' => $newValues['amount'] ?? '',
                    'minimumDays' => $newValues['minimumDays'] ?? '',
                    'maximumDays' => $newValues['maximumDays'] ?? '',
                    'active' => isset($newValues['active']),
                ]);
                $rates[] = $newRate;
                $entityManager->persist($newRate);
            }

            $seen = [];
            foreach ($rates as $rate) {
                $key = $rate->getFulfillmentType().'|'.mb_strtolower($rate->getLabel());
                if (isset($seen[$key])) {
                    throw new \DomainException(sprintf('Le tarif « %s » existe déjà pour ce mode.', $rate->getLabel()));
                }
                $seen[$key] = true;
                $entityManager->persist($rate);
            }
            $entityManager->flush();
        } catch (\DomainException $exception) {
            $this->addFlash('danger', $exception->getMessage());

            return $this->redirectToRoute('admin_shipping_rates_by_market', ['pays' => $market->getCountryCode()]);
        }

        $this->addFlash('success', sprintf('Les tarifs de livraison de %s ont été enregistrés.', $market->getName()));

        return $this->redirectToRoute('admin_shipping_rates_by_market', ['pays' => $market->getCountryCode()]);
    }

    /** @param array<string, mixed> $values */
    private function applyValues(ShippingRate $rate, Market $market, array $values): void
    {
        $label = trim((string) $values['label']);
        $city = trim((string) $values['city']);
        $type = (string) $values['type'];
        $address = trim((string) $values['address']);
        $amount = str_replace([' ', ','], ['', '.'], trim((string) $values['amount']));
        $minimumDays = trim((string) $values['minimumDays']);
        $maximumDays = trim((string) $values['maximumDays']);

        if ('' === $label || mb_strlen($label) > 160) {
            throw new \DomainException('Chaque tarif doit avoir un nom de 160 caractères maximum.');
        }
        if ('' === $city || mb_strlen($city) > 120) {
            throw new \DomainException(sprintf('La ville ou zone du tarif « %s » est obligatoire.', $label));
        }
        if (!in_array($type, ['delivery', 'pickup'], true)) {
            throw new \DomainException(sprintf('Le mode du tarif « %s » est invalide.', $label));
        }
        if ('pickup' === $type && '' === $address) {
            throw new \DomainException(sprintf('Indiquez l’adresse du dépôt pour « %s ».', $label));
        }
        if (mb_strlen($address) > 1000) {
            throw new \DomainException(sprintf('L’adresse du tarif « %s » est trop longue.', $label));
        }
        if ('' === $amount || !is_numeric($amount) || (float) $amount < 0) {
            throw new \DomainException(sprintf('Le prix du tarif « %s » est invalide.', $label));
        }
        if (!ctype_digit($minimumDays) || !ctype_digit($maximumDays) || (int) $minimumDays < 1 || (int) $maximumDays < (int) $minimumDays) {
            throw new \DomainException(sprintf('Les délais du tarif « %s » sont invalides.', $label));
        }

        $rate
            ->setLabel($label)
            ->setCity($city)
            ->setFulfillmentType($type)
            ->setAddressLine('' === $address ? null : $address)
            ->setAmountMinor((int) round((float) $amount * (10 ** $this->currencyDecimals($market->getCurrencyCode()))))
            ->setMinimumDays((int) $minimumDays)
            ->setMaximumDays((int) $maximumDays)
            ->setActive((bool) $values['active']);
    }

    private function currencyDecimals(string $currencyCode): int
    {
        return in_array($currencyCode, ['XOF', 'GNF'], true) ? 0 : 2;
    }
}
