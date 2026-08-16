<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'customer_order')]
class CustomerOrder
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 30, unique: true)]
    private string $reference;
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Market $market;
    #[ORM\Column(length: 30)]
    private string $status = 'pending_payment';
    #[ORM\Column(length: 20)]
    private string $inventoryStatus = 'reserved';
    #[ORM\Column(length: 160)]
    private string $customerName;
    #[ORM\Column(length: 180)]
    private string $email;
    #[ORM\Column(length: 40)]
    private string $phone;
    #[ORM\Column(type: Types::TEXT)]
    private string $addressLine;
    #[ORM\Column(length: 120)]
    private string $city;
    #[ORM\Column(length: 20)]
    private string $fulfillmentType;
    #[ORM\Column(length: 160)]
    private string $fulfillmentLabel;
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $fulfillmentAddress = null;
    #[ORM\Column(length: 160)]
    private string $paymentMethodName;
    #[ORM\Column(length: 30)]
    private string $paymentMethodType;
    #[ORM\Column(length: 3)]
    private string $currencyCode;
    #[ORM\Column]
    private int $subtotalMinor;
    #[ORM\Column]
    private int $shippingMinor;
    #[ORM\Column]
    private int $totalMinor;
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;
    #[ORM\OneToOne(mappedBy: 'customerOrder', targetEntity: Payment::class)]
    private ?Payment $payment = null;
    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'customerOrder', cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }
    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->reference; }
    public function getReference(): string { return $this->reference; }
    public function setReference(string $reference): self { $this->reference = $reference; return $this; }
    public function getMarket(): Market { return $this->market; }
    public function setMarket(Market $market): self { $this->market = $market; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getInventoryStatus(): string { return $this->inventoryStatus; }
    public function setInventoryStatus(string $status): self { $this->inventoryStatus = $status; return $this; }
    public function getCustomerName(): string { return $this->customerName; }
    public function setCustomerName(string $name): self { $this->customerName = trim($name); return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = mb_strtolower(trim($email)); return $this; }
    public function getPhone(): string { return $this->phone; }
    public function setPhone(string $phone): self { $this->phone = trim($phone); return $this; }
    public function getAddressLine(): string { return $this->addressLine; }
    public function setAddressLine(string $address): self { $this->addressLine = trim($address); return $this; }
    public function getCity(): string { return $this->city; }
    public function setCity(string $city): self { $this->city = $city; return $this; }
    public function getFulfillmentType(): string { return $this->fulfillmentType; }
    public function setFulfillmentType(string $type): self { $this->fulfillmentType = $type; return $this; }
    public function getFulfillmentLabel(): string { return $this->fulfillmentLabel; }
    public function setFulfillmentLabel(string $label): self { $this->fulfillmentLabel = $label; return $this; }
    public function getFulfillmentAddress(): ?string { return $this->fulfillmentAddress; }
    public function setFulfillmentAddress(?string $address): self { $this->fulfillmentAddress = $address; return $this; }
    public function getPaymentMethodName(): string { return $this->paymentMethodName; }
    public function setPaymentMethodName(string $name): self { $this->paymentMethodName = $name; return $this; }
    public function getPaymentMethodType(): string { return $this->paymentMethodType; }
    public function setPaymentMethodType(string $type): self { $this->paymentMethodType = $type; return $this; }
    public function getCurrencyCode(): string { return $this->currencyCode; }
    public function setCurrencyCode(string $code): self { $this->currencyCode = $code; return $this; }
    public function getSubtotalMinor(): int { return $this->subtotalMinor; }
    public function setSubtotalMinor(int $amount): self { $this->subtotalMinor = $amount; return $this; }
    public function getShippingMinor(): int { return $this->shippingMinor; }
    public function setShippingMinor(int $amount): self { $this->shippingMinor = $amount; return $this; }
    public function getTotalMinor(): int { return $this->totalMinor; }
    public function setTotalMinor(int $amount): self { $this->totalMinor = $amount; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getPayment(): ?Payment { return $this->payment; }
    public function setPayment(?Payment $payment): self { $this->payment = $payment; return $this; }
    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection { return $this->items; }
    public function addItem(OrderItem $item): self { if (!$this->items->contains($item)) { $this->items->add($item); $item->setCustomerOrder($this); } return $this; }
}
