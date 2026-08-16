<?php

namespace App\Controller\Admin;

use App\Entity\Inventory;
use App\Entity\Category;
use App\Entity\CustomerOrder;
use App\Entity\Market;
use App\Entity\MarketPrice;
use App\Entity\Product;
use App\Entity\Warehouse;
use App\Security\AdminMarketAccess;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly AdminMarketAccess $marketAccess,
    ) {}

    public function index(): Response
    {
        $manager = $this->doctrine->getManager();
        $isGlobal = $this->marketAccess->isGlobal();
        $assignedMarket = $this->marketAccess->assignedMarket();
        if (!$isGlobal && !$assignedMarket instanceof Market) {
            throw $this->createAccessDeniedException('Aucun marché n’est attribué à ce compte administrateur.');
        }
        $inventories = $manager->getRepository(Inventory::class)->findAll();
        if (!$isGlobal) {
            $inventories = array_filter(
                $inventories,
                static fn (Inventory $inventory): bool => $inventory->getWarehouse()->getMarket()?->getCountryCode() === $assignedMarket->getCountryCode(),
            );
        }

        $priceQuery = $manager->createQueryBuilder()
            ->select('COUNT(marketPrice.id)')
            ->from(MarketPrice::class, 'marketPrice')
            ->innerJoin('marketPrice.product', 'product')
            ->innerJoin('marketPrice.market', 'market')
            ->andWhere('product.active = :active')
            ->andWhere('market.countryCode != :internalMarket')
            ->setParameter('active', true)
            ->setParameter('internalMarket', 'US')
            ->andWhere('marketPrice.published = :published')
            ->setParameter('published', false);
        if (!$isGlobal) {
            $priceQuery->andWhere('marketPrice.market = :assignedMarket')->setParameter('assignedMarket', $assignedMarket);
        }
        $unpublishedPriceCount = (int) $priceQuery
            ->getQuery()
            ->getSingleScalarResult();

        $productCount = $isGlobal
            ? $manager->getRepository(Product::class)->count(['active' => true])
            : (int) $manager->createQueryBuilder()
                ->select('COUNT(DISTINCT product.id)')
                ->from(MarketPrice::class, 'marketPrice')
                ->innerJoin('marketPrice.product', 'product')
                ->andWhere('marketPrice.market = :assignedMarket')
                ->andWhere('marketPrice.published = :published')
                ->andWhere('product.active = :active')
                ->setParameter('assignedMarket', $assignedMarket)
                ->setParameter('published', true)
                ->setParameter('active', true)
                ->getQuery()
                ->getSingleScalarResult();
        $orderCount = static fn (string $status): int => $manager->getRepository(CustomerOrder::class)->count(
            $isGlobal ? ['status' => $status] : ['status' => $status, 'market' => $assignedMarket],
        );

        return $this->render('admin/dashboard.html.twig', [
            'scopeLabel' => $isGlobal ? 'Tous les marchés' : $assignedMarket?->getName(),
            'productCount' => $productCount,
            'marketCount' => $isGlobal ? $manager->getRepository(Market::class)->count(['active' => true]) : ($assignedMarket?->isActive() ? 1 : 0),
            'warehouseCount' => $isGlobal
                ? $manager->getRepository(Warehouse::class)->count(['active' => true])
                : $manager->getRepository(Warehouse::class)->count(['active' => true, 'market' => $assignedMarket]),
            'unpublishedPriceCount' => $unpublishedPriceCount,
            'lowStockCount' => count(array_filter($inventories, static fn (Inventory $inventory): bool =>
                $inventory->getProduct()->isActive()
                && $inventory->getWarehouse()->isActive()
                && !$inventory->getWarehouse()->isCentral()
                && $inventory->isLowStock()
            )),
            'pendingOrderCount' => $orderCount('pending_payment'),
            'preparingOrderCount' => $orderCount('preparing'),
            'shippingOrderCount' => $orderCount('shipped'),
            'failedOrderCount' => $orderCount('delivery_failed') + $orderCount('payment_failed'),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('AURIM · Administration')
            ->setFaviconPath('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="90">A</text></svg>');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Vue d’ensemble', 'fa fa-chart-line');
        yield MenuItem::section('Marché');
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            yield MenuItem::linkTo(CategoryCrudController::class, 'Catégories', 'fa fa-layer-group');
            yield MenuItem::linkTo(ProductCrudController::class, 'Produits', 'fa fa-jar');
        }
        yield MenuItem::linkToRoute('Prix & stocks par pays', 'fa fa-table-cells', 'admin_catalog_by_market');
        yield MenuItem::linkToRoute('Commandes par pays', 'fa fa-receipt', 'admin_orders_by_market');
        yield MenuItem::linkToRoute('Paiements par pays', 'fa fa-wallet', 'admin_payment_methods_by_market');
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            yield MenuItem::linkTo(MarketPriceCrudController::class, 'Prix par marché', 'fa fa-tags');
            yield MenuItem::linkTo(PaymentCrudController::class, 'Paiements', 'fa fa-money-check');
            yield MenuItem::linkTo(PaymentMethodCrudController::class, 'Moyens de paiement · avancé', 'fa fa-sliders');
        }
        yield MenuItem::section('Logistique');
        yield MenuItem::linkToRoute('Transferts de stock', 'fa fa-arrow-right-arrow-left', 'admin_stock_transfers');
        yield MenuItem::linkToRoute('Journal des stocks', 'fa fa-clock-rotate-left', 'admin_stock_movements');
        yield MenuItem::linkToRoute('Stocks à surveiller', 'fa fa-triangle-exclamation', 'admin_stock_alerts_by_market');
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            yield MenuItem::linkTo(InventoryCrudController::class, 'Stocks · consultation', 'fa fa-boxes-stacked');
            yield MenuItem::linkTo(WarehouseCrudController::class, 'Entrepôts', 'fa fa-warehouse');
            yield MenuItem::linkTo(MarketCrudController::class, 'Marchés', 'fa fa-earth-africa');
            yield MenuItem::linkTo(ShippingRateCrudController::class, 'Tarifs de livraison', 'fa fa-truck');
        }
        yield MenuItem::section();
        yield MenuItem::linkToRoute('Voir la boutique', 'fa fa-arrow-up-right-from-square', 'app_catalog');
        yield MenuItem::linkToLogout('Se déconnecter', 'fa fa-right-from-bracket');
    }
}
