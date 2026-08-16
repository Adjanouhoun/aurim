<?php

namespace App\Controller\Admin;

use App\Entity\Inventory;
use App\Entity\Market;
use App\Entity\Product;
use App\Entity\Warehouse;
use App\Security\AdminMarketAccess;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class StockConsultationByMarketController extends AbstractController
{
    #[AdminRoute('/stocks-par-pays', name: 'stock_consultation_by_market', options: ['methods' => ['GET']])]
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
        $warehouse = $selectedMarket instanceof Market
            ? $entityManager->getRepository(Warehouse::class)->findOneBy(['market' => $selectedMarket, 'central' => false])
            : null;

        $products = $entityManager->createQueryBuilder()
            ->select('product', 'category')
            ->from(Product::class, 'product')
            ->innerJoin('product.category', 'category')
            ->andWhere('product.active = :active')
            ->andWhere('category.active = :active')
            ->setParameter('active', true)
            ->orderBy('category.position', 'ASC')
            ->addOrderBy('product.name', 'ASC')
            ->getQuery()
            ->getResult();

        $rows = [];
        $physicalTotal = 0;
        $reservedTotal = 0;
        $availableTotal = 0;
        $lowStockCount = 0;
        if ($warehouse instanceof Warehouse) {
            foreach ($products as $product) {
                $inventory = $entityManager->getRepository(Inventory::class)->findOneBy([
                    'product' => $product,
                    'warehouse' => $warehouse,
                ]);
                $physical = $inventory instanceof Inventory ? $inventory->getQuantityOnHand() : 0;
                $reserved = $inventory instanceof Inventory ? $inventory->getQuantityReserved() : 0;
                $available = $inventory instanceof Inventory ? $inventory->getAvailableQuantity() : 0;
                $threshold = $inventory instanceof Inventory ? $inventory->getLowStockThreshold() : 5;
                $isLowStock = $available <= $threshold;
                $physicalTotal += $physical;
                $reservedTotal += $reserved;
                $availableTotal += $available;
                $lowStockCount += $isLowStock ? 1 : 0;
                $rows[] = compact('product', 'inventory', 'physical', 'reserved', 'available', 'threshold', 'isLowStock');
            }
        }

        return $this->render('admin/inventory/consultation_by_market.html.twig', [
            'markets' => $markets,
            'selectedMarket' => $selectedMarket,
            'warehouse' => $warehouse,
            'rows' => $rows,
            'physicalTotal' => $physicalTotal,
            'reservedTotal' => $reservedTotal,
            'availableTotal' => $availableTotal,
            'lowStockCount' => $lowStockCount,
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
}
