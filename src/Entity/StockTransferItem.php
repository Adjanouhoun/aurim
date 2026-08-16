<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'stock_transfer_item')]
#[ORM\UniqueConstraint(name: 'uniq_transfer_product', columns: ['stock_transfer_id', 'product_id'])]
class StockTransferItem
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StockTransfer $stockTransfer;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Product $product;

    #[ORM\Column]
    private int $quantity;

    public function getId(): ?int { return $this->id; }
    public function getStockTransfer(): StockTransfer { return $this->stockTransfer; }
    public function setStockTransfer(StockTransfer $transfer): self { $this->stockTransfer = $transfer; return $this; }
    public function getProduct(): Product { return $this->product; }
    public function setProduct(Product $product): self { $this->product = $product; return $this; }
    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): self
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La quantité transférée doit être positive.');
        }
        $this->quantity = $quantity;

        return $this;
    }
}
