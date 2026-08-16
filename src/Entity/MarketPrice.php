<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_product_market', columns: ['product_id', 'market_id'])]
class MarketPrice
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'marketPrices')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Market $market;

    #[ORM\Column(nullable: true)]
    private ?int $amountMinor = null;

    #[ORM\Column]
    private bool $published = false;

    public function getId(): ?int { return $this->id; }
    public function getProduct(): Product { return $this->product; }
    public function setProduct(Product $product): self { $this->product = $product; return $this; }
    public function getMarket(): Market { return $this->market; }
    public function setMarket(Market $market): self { $this->market = $market; return $this; }
    public function getAmountMinor(): ?int { return $this->amountMinor; }
    public function setAmountMinor(?int $amount): self { $this->amountMinor = null === $amount ? null : max(0, $amount); return $this; }
    public function isPublished(): bool { return $this->published; }
    public function setPublished(bool $published): self { $this->published = $published; return $this; }
}
