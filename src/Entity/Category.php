<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_category')]
class Category
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $nameEn = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $nameAr = null;

    #[ORM\Column(length: 140, unique: true)]
    private string $slug;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $active = true;

    /** @var Collection<int, Product> */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category')]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->name; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getNameEn(): ?string { return $this->nameEn; }
    public function setNameEn(?string $name): self { $this->nameEn = $this->clean($name); return $this; }
    public function getNameAr(): ?string { return $this->nameAr; }
    public function setNameAr(?string $name): self { $this->nameAr = $this->clean($name); return $this; }
    public function getLocalizedName(string $locale): string
    {
        return match ($locale) {
            'en' => $this->nameEn ?: $this->name,
            'ar' => $this->nameAr ?: $this->name,
            default => $this->name,
        };
    }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): self { $this->slug = $slug; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }
    /** @return Collection<int, Product> */
    public function getProducts(): Collection { return $this->products; }
    public function getActiveProductCount(): int
    {
        return $this->products->filter(static fn (Product $product): bool => $product->isActive())->count();
    }

    private function clean(?string $value): ?string
    {
        $value = null === $value ? null : trim($value);

        return '' === $value ? null : $value;
    }
}
