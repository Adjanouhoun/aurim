<?php

namespace App\Controller\Admin;

use App\Entity\Inventory;
use App\Entity\Market;
use App\Entity\StockMovement;
use App\Entity\Warehouse;
use App\Security\AdminMarketAccess;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class StockMovementsController extends AbstractController
{
    private const PER_PAGE = 50;

    private const TYPE_LABELS = [
        'opening_balance' => 'Solde initial',
        'manual_adjustment' => 'Correction manuelle',
        'order_reserved' => 'Réservation commande',
        'order_released' => 'Réservation libérée',
        'order_committed' => 'Commande déduite',
        'transfer_shipped' => 'Transfert expédié',
        'transfer_received' => 'Transfert réceptionné',
    ];

    private const FILTERS = [
        'all' => [],
        'orders' => ['order_reserved', 'order_released', 'order_committed'],
        'transfers' => ['transfer_shipped', 'transfer_received'],
        'adjustments' => ['opening_balance', 'manual_adjustment'],
    ];

    #[AdminRoute('/mouvements-de-stock', name: 'stock_movements', options: ['methods' => ['GET']])]
    public function index(Request $request, EntityManagerInterface $entityManager, AdminMarketAccess $marketAccess): Response
    {
        $markets = $entityManager->createQueryBuilder()
            ->select('market')
            ->from(Market::class, 'market')
            ->addOrderBy("CASE WHEN market.countryCode = 'MR' THEN 1 WHEN market.countryCode = 'SN' THEN 2 WHEN market.countryCode = 'ML' THEN 3 WHEN market.countryCode = 'GN' THEN 4 WHEN market.countryCode = 'US' THEN 5 ELSE 6 END", 'ASC')
            ->getQuery()
            ->getResult();
        $markets = $marketAccess->filterMarkets($markets);
        $selectedMarket = $this->selectMarket($markets, strtoupper(trim((string) $request->query->get('pays'))));
        $warehouse = $selectedMarket instanceof Market
            ? $entityManager->getRepository(Warehouse::class)->findOneBy(['market' => $selectedMarket])
            : null;
        $filter = (string) $request->query->get('filtre', 'all');
        if (!array_key_exists($filter, self::FILTERS)) {
            $filter = 'all';
        }

        $movements = [];
        $movementCount = 0;
        $page = max(1, $request->query->getInt('page', 1));
        $pageCount = 1;
        $physicalTotal = 0;
        $reservedTotal = 0;

        if ($warehouse instanceof Warehouse) {
            foreach ($entityManager->getRepository(Inventory::class)->findBy(['warehouse' => $warehouse]) as $inventory) {
                $physicalTotal += $inventory->getQuantityOnHand();
                $reservedTotal += $inventory->getQuantityReserved();
            }

            $countQuery = $entityManager->createQueryBuilder()
                ->select('COUNT(stockMovement.id)')
                ->from(StockMovement::class, 'stockMovement')
                ->andWhere('stockMovement.warehouse = :warehouse')
                ->setParameter('warehouse', $warehouse);
            if ([] !== self::FILTERS[$filter]) {
                $countQuery->andWhere('stockMovement.movementType IN (:types)')->setParameter('types', self::FILTERS[$filter]);
            }
            $movementCount = (int) $countQuery->getQuery()->getSingleScalarResult();
            $pageCount = max(1, (int) ceil($movementCount / self::PER_PAGE));
            $page = min($page, $pageCount);

            $query = $entityManager->createQueryBuilder()
                ->select('stockMovement', 'product')
                ->from(StockMovement::class, 'stockMovement')
                ->innerJoin('stockMovement.product', 'product')
                ->andWhere('stockMovement.warehouse = :warehouse')
                ->setParameter('warehouse', $warehouse)
                ->orderBy('stockMovement.createdAt', 'DESC')
                ->addOrderBy('stockMovement.id', 'DESC')
                ->setFirstResult(($page - 1) * self::PER_PAGE)
                ->setMaxResults(self::PER_PAGE);
            if ([] !== self::FILTERS[$filter]) {
                $query->andWhere('stockMovement.movementType IN (:types)')->setParameter('types', self::FILTERS[$filter]);
            }
            $movements = $query->getQuery()->getResult();
        }

        return $this->render('admin/inventory/movements.html.twig', [
            'markets' => $markets,
            'selectedMarket' => $selectedMarket,
            'warehouse' => $warehouse,
            'movements' => $movements,
            'movementCount' => $movementCount,
            'physicalTotal' => $physicalTotal,
            'reservedTotal' => $reservedTotal,
            'availableTotal' => max(0, $physicalTotal - $reservedTotal),
            'typeLabels' => self::TYPE_LABELS,
            'filter' => $filter,
            'page' => $page,
            'pageCount' => $pageCount,
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
        foreach ($markets as $market) {
            if ($market->isActive() && 'US' !== $market->getCountryCode()) {
                return $market;
            }
        }

        return $markets[0] ?? null;
    }
}
