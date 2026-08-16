<?php

namespace App\Controller\Admin;

use App\Entity\CustomerOrder;
use App\Entity\Market;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class OrdersByMarketController extends AbstractController
{
    private const ORDERS_PER_PAGE = 25;

    #[AdminRoute('/commandes', name: 'orders_by_market', options: ['methods' => ['GET']])]
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

        $counts = [];
        foreach ($markets as $market) {
            $counts[$market->getCountryCode()] = $entityManager->getRepository(CustomerOrder::class)->count(['market' => $market]);
        }

        $requestedCode = strtoupper(trim((string) $request->query->get('pays')));
        $selectedMarket = null;
        foreach ($markets as $market) {
            if ($market->getCountryCode() === $requestedCode) {
                $selectedMarket = $market;
                break;
            }
        }
        if (!$selectedMarket instanceof Market) {
            foreach ($markets as $market) {
                if (($counts[$market->getCountryCode()] ?? 0) > 0) {
                    $selectedMarket = $market;
                    break;
                }
            }
            $selectedMarket ??= $markets[0] ?? null;
        }

        $orders = [];
        $view = 'delivered' === $request->query->get('vue') ? 'delivered' : 'other';
        $deliveredCount = 0;
        $otherCount = 0;
        $page = max(1, $request->query->getInt('page', 1));
        $pageCount = 1;
        if ($selectedMarket instanceof Market) {
            $deliveredCount = $entityManager->getRepository(CustomerOrder::class)->count(['market' => $selectedMarket, 'status' => 'delivered']);
            $otherCount = ($counts[$selectedMarket->getCountryCode()] ?? 0) - $deliveredCount;
            $viewCount = 'delivered' === $view ? $deliveredCount : $otherCount;
            $pageCount = max(1, (int) ceil($viewCount / self::ORDERS_PER_PAGE));
            $page = min($page, $pageCount);
            $queryBuilder = $entityManager->createQueryBuilder()
                ->select('customerOrder', 'payment')
                ->from(CustomerOrder::class, 'customerOrder')
                ->leftJoin('customerOrder.payment', 'payment')
                ->andWhere('customerOrder.market = :market')
                ->setParameter('market', $selectedMarket)
                ->orderBy('customerOrder.createdAt', 'DESC');
            if ('delivered' === $view) {
                $queryBuilder->andWhere('customerOrder.status = :delivered')->setParameter('delivered', 'delivered');
            } else {
                $queryBuilder->andWhere('customerOrder.status != :delivered')->setParameter('delivered', 'delivered');
            }
            $query = $queryBuilder
                ->setFirstResult(($page - 1) * self::ORDERS_PER_PAGE)
                ->setMaxResults(self::ORDERS_PER_PAGE)
                ->getQuery();
            $orders = $query->getResult();
        }

        return $this->render('admin/order/by_market.html.twig', [
            'markets' => $markets,
            'selectedMarket' => $selectedMarket,
            'counts' => $counts,
            'orders' => $orders,
            'view' => $view,
            'deliveredCount' => $deliveredCount,
            'otherCount' => $otherCount,
            'page' => $page,
            'pageCount' => $pageCount,
        ]);
    }
}
