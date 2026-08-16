<?php

namespace App\Controller\Admin;

use App\Entity\Inventory;
use App\Entity\Market;
use App\Entity\MarketPrice;
use App\Entity\Product;
use App\Entity\Warehouse;
use App\Inventory\StockMovementRecorder;
use App\Security\AdminMarketAccess;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CatalogByMarketController extends AbstractController
{
    #[AdminRoute('/catalogue-par-pays', name: 'catalog_by_market', options: ['methods' => ['GET', 'POST']])]
    public function index(Request $request, EntityManagerInterface $entityManager, StockMovementRecorder $movementRecorder, AdminMarketAccess $marketAccess): Response
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
        $requestedCode = strtoupper(trim((string) $request->query->get('pays')));
        $selectedMarket = $this->selectMarket($markets, $requestedCode);
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

        if ($request->isMethod('POST') && $selectedMarket instanceof Market && $warehouse instanceof Warehouse) {
            return $this->save($request, $entityManager, $movementRecorder, $selectedMarket, $warehouse, $products);
        }

        $rows = [];
        $publishedCount = 0;
        $availableCount = 0;
        if ($selectedMarket instanceof Market && $warehouse instanceof Warehouse) {
            foreach ($products as $product) {
                $price = $entityManager->getRepository(MarketPrice::class)->findOneBy([
                    'product' => $product,
                    'market' => $selectedMarket,
                ]);
                $inventory = $entityManager->getRepository(Inventory::class)->findOneBy([
                    'product' => $product,
                    'warehouse' => $warehouse,
                ]);
                if ($price instanceof MarketPrice && $price->isPublished() && null !== $price->getAmountMinor()) {
                    ++$publishedCount;
                }
                if ($inventory instanceof Inventory && $inventory->getAvailableQuantity() > 0) {
                    ++$availableCount;
                }
                $rows[] = ['product' => $product, 'price' => $price, 'inventory' => $inventory];
            }
        }

        $decimals = $selectedMarket instanceof Market ? $this->currencyDecimals($selectedMarket->getCurrencyCode()) : 0;

        return $this->render('admin/catalog/by_market.html.twig', [
            'markets' => $markets,
            'selectedMarket' => $selectedMarket,
            'warehouse' => $warehouse,
            'rows' => $rows,
            'publishedCount' => $publishedCount,
            'availableCount' => $availableCount,
            'currencyDecimals' => $decimals,
            'currencyDivisor' => 10 ** $decimals,
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

    /**
     * @param list<Product> $products
     */
    private function save(
        Request $request,
        EntityManagerInterface $entityManager,
        StockMovementRecorder $movementRecorder,
        Market $market,
        Warehouse $warehouse,
        array $products,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('catalog-market-'.$market->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Le formulaire a expiré. Rechargez la page et réessayez.');
        }

        $prices = $request->request->all('prices');
        $stocks = $request->request->all('stocks');
        $thresholds = $request->request->all('thresholds');
        $published = $request->request->all('published');
        $multiplier = 10 ** $this->currencyDecimals($market->getCurrencyCode());

        foreach ($products as $product) {
            $id = (string) $product->getId();
            $priceValue = str_replace([' ', ','], ['', '.'], trim((string) ($prices[$id] ?? '')));
            $stockValue = trim((string) ($stocks[$id] ?? '0'));
            $thresholdValue = trim((string) ($thresholds[$id] ?? '5'));

            if ('' !== $priceValue && (!is_numeric($priceValue) || (float) $priceValue < 0)) {
                $this->addFlash('danger', sprintf('Le prix de « %s » est invalide.', $product->getName()));

                return $this->redirectToRoute('admin_catalog_by_market', ['pays' => $market->getCountryCode()]);
            }
            if (!ctype_digit($stockValue) || !ctype_digit($thresholdValue)) {
                $this->addFlash('danger', sprintf('Le stock de « %s » doit être un nombre entier positif.', $product->getName()));

                return $this->redirectToRoute('admin_catalog_by_market', ['pays' => $market->getCountryCode()]);
            }

            $price = $entityManager->getRepository(MarketPrice::class)->findOneBy([
                'product' => $product,
                'market' => $market,
            ]) ?? (new MarketPrice())->setProduct($product)->setMarket($market);
            $amountMinor = '' === $priceValue ? null : (int) round((float) $priceValue * $multiplier);
            $price
                ->setAmountMinor($amountMinor)
                ->setPublished(null !== $amountMinor && isset($published[$id]));
            $entityManager->persist($price);

            $inventory = $entityManager->getRepository(Inventory::class)->findOneBy([
                'product' => $product,
                'warehouse' => $warehouse,
            ]) ?? (new Inventory())->setProduct($product)->setWarehouse($warehouse);
            $previousQuantity = $inventory->getQuantityOnHand();
            $newQuantity = (int) $stockValue;
            $inventory
                ->setQuantityOnHand($newQuantity)
                ->setLowStockThreshold((int) $thresholdValue);
            $entityManager->persist($inventory);
            $movementRecorder->record(
                $inventory,
                'manual_adjustment',
                $newQuantity - $previousQuantity,
                0,
                'Correction manuelle depuis la page « Prix et stocks par pays ».',
                null,
                $this->getUser()?->getUserIdentifier(),
            );
        }

        $entityManager->flush();
        $this->addFlash('success', sprintf('Les prix et le stock du marché %s ont été enregistrés.', $market->getName()));

        return $this->redirectToRoute('admin_catalog_by_market', ['pays' => $market->getCountryCode()]);
    }

    private function currencyDecimals(string $currencyCode): int
    {
        return in_array($currencyCode, ['XOF', 'GNF'], true) ? 0 : 2;
    }
}
