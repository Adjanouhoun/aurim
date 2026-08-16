<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class OrderItem
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CustomerOrder $customerOrder;
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;
    #[ORM\Column(length: 200)]
    private string $productName;
    #[ORM\Column]
    private int $unitPriceMinor;
    #[ORM\Column]
    private int $quantity;
    #[ORM\Column]
    private int $totalMinor;

    public function getId(): ?int { return $this->id; }
    public function getCustomerOrder(): CustomerOrder { return $this->customerOrder; }
    public function setCustomerOrder(CustomerOrder $order): self { $this->customerOrder = $order; return $this; }
    public function getProduct(): Product { return $this->product; }
    public function setProduct(Product $product): self { $this->product = $product; return $this; }
    public function getProductName(): string { return $this->productName; }
    public function setProductName(string $name): self { $this->productName = $name; return $this; }
    public function getUnitPriceMinor(): int { return $this->unitPriceMinor; }
    public function setUnitPriceMinor(int $amount): self { $this->unitPriceMinor = $amount; return $this; }
    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): self { $this->quantity = $quantity; return $this; }
    public function getTotalMinor(): int { return $this->totalMinor; }
    public function setTotalMinor(int $amount): self { $this->totalMinor = $amount; return $this; }
}
