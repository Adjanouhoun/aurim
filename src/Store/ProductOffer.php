<?php

namespace App\Store;

use App\Entity\Inventory;
use App\Entity\MarketPrice;
use App\Entity\Product;
use App\Entity\Warehouse;
use Doctrine\ORM\EntityManagerInterface;

final class ProductOffer
{
    public function __construct(
        private readonly StoreContext $storeContext,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function getPrice(Product $product): ?MarketPrice
    {
        $market = $this->storeContext->getSelectedMarket();
        if (null === $market) {
            return null;
        }

        $price = $this->entityManager->getRepository(MarketPrice::class)->findOneBy([
            'product' => $product,
            'market' => $market,
            'published' => true,
        ]);

        return $price instanceof MarketPrice && null !== $price->getAmountMinor() ? $price : null;
    }

    public function getAvailableQuantity(Product $product): int
    {
        $market = $this->storeContext->getSelectedMarket();
        if (null === $market) {
            return 0;
        }
        $warehouse = $this->entityManager->getRepository(Warehouse::class)->findOneBy([
            'market' => $market,
            'central' => false,
            'active' => true,
        ]);
        if (!$warehouse instanceof Warehouse) {
            return 0;
        }
        $inventory = $this->entityManager->getRepository(Inventory::class)->findOneBy(['product' => $product, 'warehouse' => $warehouse]);

        return $inventory instanceof Inventory ? $inventory->getAvailableQuantity() : 0;
    }

    public function isPurchasable(Product $product, int $quantity = 1): bool
    {
        return null !== $this->getPrice($product) && $this->getAvailableQuantity($product) >= max(1, $quantity);
    }
}
