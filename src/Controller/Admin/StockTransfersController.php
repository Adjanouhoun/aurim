<?php

namespace App\Controller\Admin;

use App\Entity\Inventory;
use App\Entity\Market;
use App\Entity\Product;
use App\Entity\StockTransfer;
use App\Entity\StockTransferItem;
use App\Entity\Warehouse;
use App\Inventory\StockTransferManager;
use App\Security\AdminMarketAccess;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class StockTransfersController extends AbstractController
{
    #[AdminRoute('/transferts-de-stock', name: 'stock_transfers', options: ['methods' => ['GET', 'POST']])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        StockTransferManager $transferManager,
        AdminMarketAccess $marketAccess,
    ): Response {
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
        $centralWarehouse = $entityManager->getRepository(Warehouse::class)->findOneBy(['central' => true, 'active' => true]);
        $destinationWarehouse = $selectedMarket instanceof Market
            ? $entityManager->getRepository(Warehouse::class)->findOneBy(['market' => $selectedMarket, 'central' => false])
            : null;

        if ($request->isMethod('POST') && $selectedMarket instanceof Market && $destinationWarehouse instanceof Warehouse) {
            $action = (string) $request->request->get('_action');
            if ('create' === $action && $centralWarehouse instanceof Warehouse) {
                return $this->createTransfer($request, $entityManager, $selectedMarket, $centralWarehouse, $destinationWarehouse);
            }

            return $this->changeStatus($request, $entityManager, $transferManager, $selectedMarket, $destinationWarehouse, $action);
        }

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
        foreach ($products as $product) {
            $centralInventory = $centralWarehouse instanceof Warehouse
                ? $entityManager->getRepository(Inventory::class)->findOneBy(['product' => $product, 'warehouse' => $centralWarehouse])
                : null;
            $localInventory = $destinationWarehouse instanceof Warehouse
                ? $entityManager->getRepository(Inventory::class)->findOneBy(['product' => $product, 'warehouse' => $destinationWarehouse])
                : null;
            $rows[] = ['product' => $product, 'centralInventory' => $centralInventory, 'localInventory' => $localInventory];
        }

        $transfers = $destinationWarehouse instanceof Warehouse
            ? $entityManager->createQueryBuilder()
                ->select('stockTransfer', 'item', 'product')
                ->from(StockTransfer::class, 'stockTransfer')
                ->leftJoin('stockTransfer.items', 'item')
                ->leftJoin('item.product', 'product')
                ->andWhere('stockTransfer.destinationWarehouse = :warehouse')
                ->setParameter('warehouse', $destinationWarehouse)
                ->orderBy('stockTransfer.createdAt', 'DESC')
                ->setMaxResults(30)
                ->getQuery()
                ->getResult()
            : [];

        return $this->render('admin/inventory/transfers.html.twig', [
            'markets' => $markets,
            'selectedMarket' => $selectedMarket,
            'centralWarehouse' => $centralWarehouse,
            'destinationWarehouse' => $destinationWarehouse,
            'rows' => $rows,
            'transfers' => $transfers,
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
            if ($market->isActive()) {
                return $market;
            }
        }

        return $markets[0] ?? null;
    }

    private function createTransfer(
        Request $request,
        EntityManagerInterface $entityManager,
        Market $market,
        Warehouse $source,
        Warehouse $destination,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('stock-transfer-create-'.$market->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Le formulaire a expiré. Rechargez la page et réessayez.');
        }
        if (!$market->isActive() || !$destination->isActive()) {
            $this->addFlash('danger', 'Activez le marché et son entrepôt avant de préparer un transfert.');

            return $this->redirectToRoute('admin_stock_transfers', ['pays' => $market->getCountryCode()]);
        }

        $notes = trim((string) $request->request->get('notes'));
        if (mb_strlen($notes) > 1000) {
            $this->addFlash('danger', 'La note du transfert ne doit pas dépasser 1 000 caractères.');

            return $this->redirectToRoute('admin_stock_transfers', ['pays' => $market->getCountryCode()]);
        }

        $quantities = $request->request->all('quantities');
        $products = $entityManager->getRepository(Product::class)->findBy(['active' => true]);
        $transfer = (new StockTransfer())
            ->setReference('TRF-'.date('ymd').'-'.$market->getCountryCode().'-'.strtoupper(bin2hex(random_bytes(4))))
            ->setSourceWarehouse($source)
            ->setDestinationWarehouse($destination)
            ->setNotes($notes);

        foreach ($products as $product) {
            $value = trim((string) ($quantities[(string) $product->getId()] ?? '0'));
            if ('' === $value || '0' === $value) {
                continue;
            }
            if (!ctype_digit($value)) {
                $this->addFlash('danger', sprintf('La quantité de « %s » doit être un nombre entier positif.', $product->getName()));

                return $this->redirectToRoute('admin_stock_transfers', ['pays' => $market->getCountryCode()]);
            }
            $quantity = (int) $value;
            if ($quantity < 1) {
                continue;
            }
            $inventory = $entityManager->getRepository(Inventory::class)->findOneBy(['product' => $product, 'warehouse' => $source]);
            if (!$inventory instanceof Inventory || $inventory->getAvailableQuantity() < $quantity) {
                $this->addFlash('danger', sprintf('Le stock central disponible est insuffisant pour « %s ».', $product->getName()));

                return $this->redirectToRoute('admin_stock_transfers', ['pays' => $market->getCountryCode()]);
            }
            $transfer->addItem((new StockTransferItem())->setProduct($product)->setQuantity($quantity));
        }

        if ($transfer->getItems()->isEmpty()) {
            $this->addFlash('danger', 'Indiquez au moins une quantité à transférer.');

            return $this->redirectToRoute('admin_stock_transfers', ['pays' => $market->getCountryCode()]);
        }

        $entityManager->persist($transfer);
        $entityManager->flush();
        $this->addFlash('success', sprintf('Le transfert %s est prêt. Confirmez son expédition lorsque le colis quitte les États-Unis.', $transfer->getReference()));

        return $this->redirectToRoute('admin_stock_transfers', ['pays' => $market->getCountryCode()]);
    }

    private function changeStatus(
        Request $request,
        EntityManagerInterface $entityManager,
        StockTransferManager $transferManager,
        Market $market,
        Warehouse $destination,
        string $action,
    ): RedirectResponse {
        $transfer = $entityManager->getRepository(StockTransfer::class)->find($request->request->getInt('transfer_id'));
        if (!$transfer instanceof StockTransfer || $transfer->getDestinationWarehouse()->getId() !== $destination->getId()) {
            throw $this->createNotFoundException('Transfert introuvable pour ce pays.');
        }
        if (!in_array($action, ['ship', 'receive', 'cancel'], true)) {
            throw $this->createNotFoundException('Action de transfert inconnue.');
        }
        if (!$this->isCsrfTokenValid('stock-transfer-'.$action.'-'.$transfer->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Le formulaire a expiré. Rechargez la page et réessayez.');
        }

        try {
            if ('ship' === $action) {
                $transferManager->ship($transfer);
            } elseif ('receive' === $action) {
                $transferManager->receive($transfer);
            } else {
                $transferManager->cancel($transfer);
            }
            $message = match ($action) {
                'ship' => sprintf('Le transfert %s est maintenant en transit. Le stock central a été déduit.', $transfer->getReference()),
                'receive' => sprintf('Le transfert %s a été réceptionné. Le stock local a été crédité.', $transfer->getReference()),
                'cancel' => sprintf('Le transfert %s a été annulé sans impact sur les stocks.', $transfer->getReference()),
            };
            $this->addFlash('success', $message);
        } catch (\DomainException $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_stock_transfers', ['pays' => $market->getCountryCode()]);
    }
}
