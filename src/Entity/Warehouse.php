<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'warehouse')]
class Warehouse
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40, unique: true)]
    private string $code;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\OneToOne(inversedBy: 'warehouse')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Market $market;

    #[ORM\Column]
    private bool $central = false;

    #[ORM\Column]
    private bool $active = true;

    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return sprintf('%s · %s', $this->name, $this->market->getName()); }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): self { $this->code = strtoupper($code); return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getMarket(): Market { return $this->market; }
    public function setMarket(Market $market): self { $this->market = $market; return $this; }
    public function isCentral(): bool { return $this->central; }
    public function setCentral(bool $central): self { $this->central = $central; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }
}
