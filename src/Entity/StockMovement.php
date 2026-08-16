<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'stock_movement')]
class StockMovement
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Product $product;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Warehouse $warehouse;

    #[ORM\Column(length: 40)]
    private string $movementType;

    #[ORM\Column]
    private int $quantityOnHandDelta = 0;

    #[ORM\Column]
    private int $quantityReservedDelta = 0;

    #[ORM\Column]
    private int $balanceOnHand;

    #[ORM\Column]
    private int $balanceReserved;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    private string $description;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $performedBy = null;

    #[ORM\Column]
    private bool $triggersLowStockAlert = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getProduct(): Product { return $this->product; }
    public function setProduct(Product $product): self { $this->product = $product; return $this; }
    public function getWarehouse(): Warehouse { return $this->warehouse; }
    public function setWarehouse(Warehouse $warehouse): self { $this->warehouse = $warehouse; return $this; }
    public function getMovementType(): string { return $this->movementType; }
    public function setMovementType(string $type): self { $this->movementType = $type; return $this; }
    public function getQuantityOnHandDelta(): int { return $this->quantityOnHandDelta; }
    public function setQuantityOnHandDelta(int $quantity): self { $this->quantityOnHandDelta = $quantity; return $this; }
    public function getQuantityReservedDelta(): int { return $this->quantityReservedDelta; }
    public function setQuantityReservedDelta(int $quantity): self { $this->quantityReservedDelta = $quantity; return $this; }
    public function getBalanceOnHand(): int { return $this->balanceOnHand; }
    public function setBalanceOnHand(int $quantity): self { $this->balanceOnHand = $quantity; return $this; }
    public function getBalanceReserved(): int { return $this->balanceReserved; }
    public function setBalanceReserved(int $quantity): self { $this->balanceReserved = $quantity; return $this; }
    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $reference): self { $reference = trim((string) $reference); $this->reference = '' === $reference ? null : $reference; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }
    public function getPerformedBy(): ?string { return $this->performedBy; }
    public function setPerformedBy(?string $performedBy): self { $performedBy = trim((string) $performedBy); $this->performedBy = '' === $performedBy ? null : $performedBy; return $this; }
    public function triggersLowStockAlert(): bool { return $this->triggersLowStockAlert; }
    public function setTriggersLowStockAlert(bool $triggers): self { $this->triggersLowStockAlert = $triggers; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
