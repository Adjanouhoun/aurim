<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'product')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160, unique: true)]
    private string $slug;

    #[ORM\Column(length: 40, unique: true)]
    #[Assert\NotBlank]
    private string $sku;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    private string $type;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Category $category = null;

    #[ORM\Column(length: 40)]
    #[Assert\NotBlank]
    private string $size;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $shortDescription;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $description;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 1, minMessage: 'Ajoutez au moins un bénéfice visible sur la fiche produit.')]
    private array $benefits = [];

    /** @var list<array{name: string, text: string}> */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 1, minMessage: 'Ajoutez au moins un actif ou ingrédient visible sur la fiche produit.')]
    private array $ingredients = [];

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $usageInstructions;

    #[ORM\Column(length: 255)]
    private string $imagePath = '';

    private ?UploadedFile $imageFile = null;

    #[ORM\Column(length: 16)]
    private string $imagePosition = 'center';

    #[ORM\Column]
    private bool $active = true;

    /** @var Collection<int, Inventory> */
    #[ORM\OneToMany(targetEntity: Inventory::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $inventories;

    /** @var Collection<int, MarketPrice> */
    #[ORM\OneToMany(targetEntity: MarketPrice::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $marketPrices;

    public function __construct()
    {
        $this->inventories = new ArrayCollection();
        $this->marketPrices = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->name; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): self { $this->slug = $slug; return $this; }
    public function getSku(): string { return $this->sku; }
    public function setSku(string $sku): self { $this->sku = strtoupper(trim($sku)); return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): self { $this->category = $category; return $this; }
    public function getSize(): string { return $this->size; }
    public function setSize(string $size): self { $this->size = $size; return $this; }
    public function getShortDescription(): string { return $this->shortDescription; }
    public function setShortDescription(string $value): self { $this->shortDescription = $value; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $value): self { $this->description = $value; return $this; }
    /** @return list<string> */
    public function getBenefits(): array { return $this->benefits; }
    /** @param list<string> $benefits */
    public function setBenefits(array $benefits): self { $this->benefits = $benefits; return $this; }
    /** @return list<array{name: string, text: string}> */
    public function getIngredients(): array { return $this->ingredients; }
    /** @param list<array{name: string, text: string}> $ingredients */
    public function setIngredients(array $ingredients): self { $this->ingredients = $ingredients; return $this; }
    public function getUsageInstructions(): string { return $this->usageInstructions; }
    public function setUsageInstructions(string $value): self { $this->usageInstructions = $value; return $this; }
    public function getImagePath(): string { return $this->imagePath; }
    public function setImagePath(string $path): self { $this->imagePath = $path; return $this; }
    public function getImageFile(): ?UploadedFile { return $this->imageFile; }
    public function setImageFile(?UploadedFile $file): self { $this->imageFile = $file; return $this; }
    public function getImagePosition(): string { return $this->imagePosition; }
    public function setImagePosition(string $position): self { $this->imagePosition = $position; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }
    /** @return Collection<int, Inventory> */
    public function getInventories(): Collection { return $this->inventories; }
    /** @return Collection<int, MarketPrice> */
    public function getMarketPrices(): Collection { return $this->marketPrices; }
}
