<?php

namespace App\Controller\Admin;

use App\Entity\Inventory;
use App\Entity\Market;
use App\Entity\Product;
use App\Entity\Warehouse;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class StockAlertsByMarketController extends AbstractController
{
    #[AdminRoute('/stocks-a-surveiller', name: 'stock_alerts_by_market', options: ['methods' => ['GET']])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $markets = $entityManager->createQueryBuilder()
            ->select('market')
            ->from(Market::class, 'market')
            ->andWhere('market.countryCode != :internalMarket')
            ->setParameter('internalMarket', 'US')
            ->addOrderBy("CASE WHEN market.countryCode = 'MR' THEN 1 WHEN market.countryCode = 'SN' THEN 2 WHEN market.countryCode = 'ML' THEN 3 WHEN market.countryCode = 'GN' THEN 4 ELSE 5 END", 'ASC')
            ->getQuery()
            ->getResult();

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
        $outOfStockCount = 0;
        $lowStockCount = 0;
        $reservedTotal = 0;

        if ($warehouse instanceof Warehouse) {
            foreach ($products as $product) {
                $inventory = $entityManager->getRepository(Inventory::class)->findOneBy([
                    'product' => $product,
                    'warehouse' => $warehouse,
                ]);
                $available = $inventory instanceof Inventory ? $inventory->getAvailableQuantity() : 0;
                $threshold = $inventory instanceof Inventory ? $inventory->getLowStockThreshold() : 5;
                if ($available > $threshold) {
                    continue;
                }

                if (0 === $available) {
                    ++$outOfStockCount;
                } else {
                    ++$lowStockCount;
                }
                $reservedTotal += $inventory instanceof Inventory ? $inventory->getQuantityReserved() : 0;
                $rows[] = [
                    'product' => $product,
                    'inventory' => $inventory,
                    'available' => $available,
                    'threshold' => $threshold,
                    'outOfStock' => 0 === $available,
                ];
            }
        }

        usort($rows, static function (array $left, array $right): int {
            if ($left['outOfStock'] !== $right['outOfStock']) {
                return $left['outOfStock'] ? -1 : 1;
            }
            if ($left['available'] !== $right['available']) {
                return $left['available'] <=> $right['available'];
            }

            return strcmp($left['product']->getName(), $right['product']->getName());
        });

        return $this->render('admin/inventory/alerts_by_market.html.twig', [
            'markets' => $markets,
            'selectedMarket' => $selectedMarket,
            'warehouse' => $warehouse,
            'rows' => $rows,
            'outOfStockCount' => $outOfStockCount,
            'lowStockCount' => $lowStockCount,
            'reservedTotal' => $reservedTotal,
        ]);
    }

    /**
     * @param list<Market> $markets
     */
    private function selectMarket(array $markets, string $requestedCode): ?Market
    {
        foreach ($markets as $market) {
            if ($market->getCountryCode() === $requestedCode) {
                return $market;
            }
        }
        foreach ($markets as $market) {
            if ($market->isActive()) {
                return $market;
            }
        }

        return $markets[0] ?? null;
    }
}
