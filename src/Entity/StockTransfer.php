<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'stock_transfer')]
class StockTransfer
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40, unique: true)]
    private string $reference;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Warehouse $sourceWarehouse;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Warehouse $destinationWarehouse;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $shippedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $receivedAt = null;

    /** @var Collection<int, StockTransferItem> */
    #[ORM\OneToMany(targetEntity: StockTransferItem::class, mappedBy: 'stockTransfer', cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->reference; }
    public function getReference(): string { return $this->reference; }
    public function setReference(string $reference): self { $this->reference = strtoupper(trim($reference)); return $this; }
    public function getSourceWarehouse(): Warehouse { return $this->sourceWarehouse; }
    public function setSourceWarehouse(Warehouse $warehouse): self { $this->sourceWarehouse = $warehouse; return $this; }
    public function getDestinationWarehouse(): Warehouse { return $this->destinationWarehouse; }
    public function setDestinationWarehouse(Warehouse $warehouse): self { $this->destinationWarehouse = $warehouse; return $this; }
    public function getStatus(): string { return $this->status; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): self { $notes = trim((string) $notes); $this->notes = '' === $notes ? null : $notes; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getShippedAt(): ?\DateTimeImmutable { return $this->shippedAt; }
    public function getReceivedAt(): ?\DateTimeImmutable { return $this->receivedAt; }
    /** @return Collection<int, StockTransferItem> */
    public function getItems(): Collection { return $this->items; }
    public function addItem(StockTransferItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setStockTransfer($this);
        }

        return $this;
    }
    public function getTotalQuantity(): int
    {
        return array_sum($this->items->map(static fn (StockTransferItem $item): int => $item->getQuantity())->toArray());
    }
    public function markInTransit(): self
    {
        if (self::STATUS_DRAFT !== $this->status) {
            throw new \DomainException('Seul un transfert en préparation peut être expédié.');
        }
        $this->status = self::STATUS_IN_TRANSIT;
        $this->shippedAt = new \DateTimeImmutable();

        return $this;
    }
    public function markReceived(): self
    {
        if (self::STATUS_IN_TRANSIT !== $this->status) {
            throw new \DomainException('Seul un transfert en transit peut être réceptionné.');
        }
        $this->status = self::STATUS_RECEIVED;
        $this->receivedAt = new \DateTimeImmutable();

        return $this;
    }
    public function cancel(): self
    {
        if (self::STATUS_DRAFT !== $this->status) {
            throw new \DomainException('Seul un transfert en préparation peut être annulé.');
        }
        $this->status = self::STATUS_CANCELLED;

        return $this;
    }
}
