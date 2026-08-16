<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'market')]
class Market
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 2, unique: true)]
    private string $countryCode;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 3)]
    private string $currencyCode;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\OneToOne(mappedBy: 'market', targetEntity: Warehouse::class)]
    private ?Warehouse $warehouse = null;

    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->name; }
    public function getCountryCode(): string { return $this->countryCode; }
    public function setCountryCode(string $code): self { $this->countryCode = strtoupper($code); return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getCurrencyCode(): string { return $this->currencyCode; }
    public function setCurrencyCode(string $code): self { $this->currencyCode = strtoupper($code); return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }
    public function getWarehouse(): ?Warehouse { return $this->warehouse; }
    public function setWarehouse(?Warehouse $warehouse): self
    {
        $this->warehouse = $warehouse;
        if (null !== $warehouse && $warehouse->getMarket() !== $this) {
            $warehouse->setMarket($this);
        }
        return $this;
    }
}
