<?php

namespace App\Store;

use App\Entity\MarketPrice;
use App\Entity\Inventory;
use App\Entity\Product;
use App\Entity\Warehouse;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class Cart
{
    private const SESSION_KEY = 'aurim_cart';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProductRepository $products,
        private readonly EntityManagerInterface $entityManager,
        private readonly StoreContext $storeContext,
    ) {}

    public function add(Product $product, int $quantity = 1): void
    {
        $items = $this->rawItems();
        $id = (string) $product->getId();
        $items[$id] = min(20, ($items[$id] ?? 0) + max(1, $quantity));
        $this->save($items);
    }

    public function update(Product $product, int $quantity): void
    {
        $items = $this->rawItems();
        $id = (string) $product->getId();
        if ($quantity <= 0) {
            unset($items[$id]);
        } else {
            $items[$id] = min(20, $quantity);
        }
        $this->save($items);
    }

    public function remove(Product $product): void
    {
        $this->update($product, 0);
    }

    /** @return list<array{product: Product, quantity: int, price: ?MarketPrice, lineTotal: ?int, availableQuantity: int, stockSufficient: bool}> */
    public function getLines(): array
    {
        $market = $this->storeContext->getSelectedMarket();
        $warehouse = null;
        if (null !== $market) {
            $warehouse = $this->entityManager->getRepository(Warehouse::class)->findOneBy([
                'market' => $market,
                'central' => false,
                'active' => true,
            ]);
        }
        $lines = [];
        foreach ($this->rawItems() as $id => $quantity) {
            $product = $this->products->find((int) $id);
            if (!$product instanceof Product || !$product->isActive()) {
                continue;
            }
            $price = null;
            if (null !== $market) {
                $price = $this->entityManager->getRepository(MarketPrice::class)->findOneBy([
                    'product' => $product,
                    'market' => $market,
                    'published' => true,
                ]);
            }
            $amount = $price?->getAmountMinor();
            $inventory = $warehouse instanceof Warehouse
                ? $this->entityManager->getRepository(Inventory::class)->findOneBy(['product' => $product, 'warehouse' => $warehouse])
                : null;
            $availableQuantity = $inventory instanceof Inventory ? $inventory->getAvailableQuantity() : 0;
            $lines[] = [
                'product' => $product,
                'quantity' => $quantity,
                'price' => $price,
                'lineTotal' => null === $amount ? null : $amount * $quantity,
                'availableQuantity' => $availableQuantity,
                'stockSufficient' => $availableQuantity >= $quantity,
            ];
        }

        return $lines;
    }

    public function hasSufficientStock(): bool
    {
        $lines = $this->getLines();
        if ([] === $lines) {
            return false;
        }
        foreach ($lines as $line) {
            if (!$line['stockSufficient']) {
                return false;
            }
        }

        return true;
    }

    public function getCount(): int
    {
        return array_sum($this->rawItems());
    }

    public function getTotal(): ?int
    {
        $total = 0;
        foreach ($this->getLines() as $line) {
            if (null === $line['lineTotal']) {
                return null;
            }
            $total += $line['lineTotal'];
        }

        return $total;
    }

    public function clear(): void
    {
        $this->requestStack->getSession()->remove(self::SESSION_KEY);
    }

    /** @return array<string, int> */
    private function rawItems(): array
    {
        $items = $this->requestStack->getSession()->get(self::SESSION_KEY, []);
        return is_array($items) ? array_map('intval', $items) : [];
    }

    /** @param array<string, int> $items */
    private function save(array $items): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $items);
    }
}
