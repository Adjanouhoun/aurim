<?php

namespace App\Store;

use App\Entity\Market;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class StoreContext
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /** @return list<Market> */
    public function getMarkets(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('DISTINCT market')
            ->from(Market::class, 'market')
            ->innerJoin('market.warehouse', 'warehouse')
            ->andWhere('market.active = :active')
            ->andWhere('warehouse.active = :active')
            ->andWhere('warehouse.central = :local')
            ->setParameter('active', true)
            ->setParameter('local', false)
            ->orderBy('market.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getSelectedMarket(): ?Market
    {
        $code = $this->requestStack->getSession()->get('store_market_code');
        if (!is_string($code)) {
            return null;
        }

        foreach ($this->getMarkets() as $market) {
            if ($market->getCountryCode() === $code) {
                return $market;
            }
        }

        $this->requestStack->getSession()->remove('store_market_code');
        return null;
    }

    public function select(Market $market): void
    {
        if (!in_array($market, $this->getMarkets(), true)) {
            return;
        }
        $this->requestStack->getSession()->set('store_market_code', $market->getCountryCode());
    }
}
