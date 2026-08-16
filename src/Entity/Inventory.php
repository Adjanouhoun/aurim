<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_product_warehouse', columns: ['product_id', 'warehouse_id'])]
class Inventory
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inventories')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Warehouse $warehouse;

    #[ORM\Column]
    private int $quantityOnHand = 0;

    #[ORM\Column]
    private int $quantityReserved = 0;

    #[ORM\Column]
    private int $lowStockThreshold = 5;

    public function getId(): ?int { return $this->id; }
    public function getProduct(): Product { return $this->product; }
    public function setProduct(Product $product): self { $this->product = $product; return $this; }
    public function getWarehouse(): Warehouse { return $this->warehouse; }
    public function setWarehouse(Warehouse $warehouse): self { $this->warehouse = $warehouse; return $this; }
    public function getQuantityOnHand(): int { return $this->quantityOnHand; }
    public function setQuantityOnHand(int $quantity): self { $this->quantityOnHand = max(0, $quantity); return $this; }
    public function getQuantityReserved(): int { return $this->quantityReserved; }
    public function setQuantityReserved(int $quantity): self { $this->quantityReserved = max(0, $quantity); return $this; }
    public function getLowStockThreshold(): int { return $this->lowStockThreshold; }
    public function setLowStockThreshold(int $quantity): self { $this->lowStockThreshold = max(0, $quantity); return $this; }
    public function getAvailableQuantity(): int { return max(0, $this->quantityOnHand - $this->quantityReserved); }
    public function isLowStock(): bool { return $this->getAvailableQuantity() <= $this->lowStockThreshold; }
}
