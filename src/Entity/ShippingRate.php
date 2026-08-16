<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_shipping_market_option', columns: ['market_id', 'fulfillment_type', 'label'])]
class ShippingRate
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Market $market;
    #[ORM\Column(length: 120)]
    private string $city;
    #[ORM\Column(length: 20)]
    private string $fulfillmentType = 'delivery';
    #[ORM\Column(length: 160)]
    private string $label;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $addressLine = null;
    #[ORM\Column]
    private int $amountMinor = 0;
    #[ORM\Column]
    private int $minimumDays = 1;
    #[ORM\Column]
    private int $maximumDays = 3;
    #[ORM\Column]
    private bool $active = true;

    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->label.' · '.$this->market->getName(); }
    public function getMarket(): Market { return $this->market; }
    public function setMarket(Market $market): self { $this->market = $market; return $this; }
    public function getCity(): string { return $this->city; }
    public function setCity(string $city): self { $this->city = trim($city); return $this; }
    public function getFulfillmentType(): string { return $this->fulfillmentType; }
    public function setFulfillmentType(string $type): self { $this->fulfillmentType = in_array($type, ['delivery', 'pickup'], true) ? $type : 'delivery'; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): self { $this->label = trim($label); return $this; }
    public function getAddressLine(): ?string { return $this->addressLine; }
    public function setAddressLine(?string $address): self { $this->addressLine = null === $address ? null : trim($address); return $this; }
    public function getAmountMinor(): int { return $this->amountMinor; }
    public function setAmountMinor(int $amount): self { $this->amountMinor = max(0, $amount); return $this; }
    public function getMinimumDays(): int { return $this->minimumDays; }
    public function setMinimumDays(int $days): self { $this->minimumDays = max(1, $days); return $this; }
    public function getMaximumDays(): int { return $this->maximumDays; }
    public function setMaximumDays(int $days): self { $this->maximumDays = max($this->minimumDays, $days); return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }
}
