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

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $nameEn = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $nameAr = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    private string $type;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $typeEn = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $typeAr = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Category $category = null;

    #[ORM\Column(length: 40)]
    #[Assert\NotBlank]
    private string $size;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $shortDescription;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortDescriptionEn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortDescriptionAr = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $description;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionEn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionAr = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 1, minMessage: 'Ajoutez au moins un bénéfice visible sur la fiche produit.')]
    private array $benefits = [];

    /** @var list<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $benefitsEn = null;

    /** @var list<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $benefitsAr = null;

    /** @var list<array{name: string, text: string}> */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 1, minMessage: 'Ajoutez au moins un actif ou ingrédient visible sur la fiche produit.')]
    private array $ingredients = [];

    /** @var list<array{name: string, text: string}>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $ingredientsEn = null;

    /** @var list<array{name: string, text: string}>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $ingredientsAr = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $usageInstructions;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $usageInstructionsEn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $usageInstructionsAr = null;

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
    public function getNameEn(): ?string { return $this->nameEn; }
    public function setNameEn(?string $name): self { $this->nameEn = $this->clean($name); return $this; }
    public function getNameAr(): ?string { return $this->nameAr; }
    public function setNameAr(?string $name): self { $this->nameAr = $this->clean($name); return $this; }
    public function getLocalizedName(string $locale): string { return $this->localizedText($locale, $this->name, $this->nameEn, $this->nameAr); }
    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function getTypeEn(): ?string { return $this->typeEn; }
    public function setTypeEn(?string $type): self { $this->typeEn = $this->clean($type); return $this; }
    public function getTypeAr(): ?string { return $this->typeAr; }
    public function setTypeAr(?string $type): self { $this->typeAr = $this->clean($type); return $this; }
    public function getLocalizedType(string $locale): string { return $this->localizedText($locale, $this->type, $this->typeEn, $this->typeAr); }
    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): self { $this->category = $category; return $this; }
    public function getSize(): string { return $this->size; }
    public function setSize(string $size): self { $this->size = $size; return $this; }
    public function getShortDescription(): string { return $this->shortDescription; }
    public function setShortDescription(string $value): self { $this->shortDescription = $value; return $this; }
    public function getShortDescriptionEn(): ?string { return $this->shortDescriptionEn; }
    public function setShortDescriptionEn(?string $value): self { $this->shortDescriptionEn = $this->clean($value); return $this; }
    public function getShortDescriptionAr(): ?string { return $this->shortDescriptionAr; }
    public function setShortDescriptionAr(?string $value): self { $this->shortDescriptionAr = $this->clean($value); return $this; }
    public function getLocalizedShortDescription(string $locale): string { return $this->localizedText($locale, $this->shortDescription, $this->shortDescriptionEn, $this->shortDescriptionAr); }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $value): self { $this->description = $value; return $this; }
    public function getDescriptionEn(): ?string { return $this->descriptionEn; }
    public function setDescriptionEn(?string $value): self { $this->descriptionEn = $this->clean($value); return $this; }
    public function getDescriptionAr(): ?string { return $this->descriptionAr; }
    public function setDescriptionAr(?string $value): self { $this->descriptionAr = $this->clean($value); return $this; }
    public function getLocalizedDescription(string $locale): string { return $this->localizedText($locale, $this->description, $this->descriptionEn, $this->descriptionAr); }
    /** @return list<string> */
    public function getBenefits(): array { return $this->benefits; }
    /** @param list<string> $benefits */
    public function setBenefits(array $benefits): self { $this->benefits = $benefits; return $this; }
    /** @return list<string>|null */
    public function getBenefitsEn(): ?array { return $this->benefitsEn; }
    /** @param list<string>|null $benefits */
    public function setBenefitsEn(?array $benefits): self { $this->benefitsEn = $benefits ?: null; return $this; }
    /** @return list<string>|null */
    public function getBenefitsAr(): ?array { return $this->benefitsAr; }
    /** @param list<string>|null $benefits */
    public function setBenefitsAr(?array $benefits): self { $this->benefitsAr = $benefits ?: null; return $this; }
    /** @return list<string> */
    public function getLocalizedBenefits(string $locale): array
    {
        return match ($locale) {
            'en' => $this->benefitsEn ?: $this->benefits,
            'ar' => $this->benefitsAr ?: $this->benefits,
            default => $this->benefits,
        };
    }
    /** @return list<array{name: string, text: string}> */
    public function getIngredients(): array { return $this->ingredients; }
    /** @param list<array{name: string, text: string}> $ingredients */
    public function setIngredients(array $ingredients): self { $this->ingredients = $ingredients; return $this; }
    /** @return list<array{name: string, text: string}>|null */
    public function getIngredientsEn(): ?array { return $this->ingredientsEn; }
    /** @param list<array{name: string, text: string}>|null $ingredients */
    public function setIngredientsEn(?array $ingredients): self { $this->ingredientsEn = $ingredients ?: null; return $this; }
    /** @return list<array{name: string, text: string}>|null */
    public function getIngredientsAr(): ?array { return $this->ingredientsAr; }
    /** @param list<array{name: string, text: string}>|null $ingredients */
    public function setIngredientsAr(?array $ingredients): self { $this->ingredientsAr = $ingredients ?: null; return $this; }
    /** @return list<array{name: string, text: string}> */
    public function getLocalizedIngredients(string $locale): array
    {
        return match ($locale) {
            'en' => $this->ingredientsEn ?: $this->ingredients,
            'ar' => $this->ingredientsAr ?: $this->ingredients,
            default => $this->ingredients,
        };
    }
    public function getUsageInstructions(): string { return $this->usageInstructions; }
    public function setUsageInstructions(string $value): self { $this->usageInstructions = $value; return $this; }
    public function getUsageInstructionsEn(): ?string { return $this->usageInstructionsEn; }
    public function setUsageInstructionsEn(?string $value): self { $this->usageInstructionsEn = $this->clean($value); return $this; }
    public function getUsageInstructionsAr(): ?string { return $this->usageInstructionsAr; }
    public function setUsageInstructionsAr(?string $value): self { $this->usageInstructionsAr = $this->clean($value); return $this; }
    public function getLocalizedUsageInstructions(string $locale): string { return $this->localizedText($locale, $this->usageInstructions, $this->usageInstructionsEn, $this->usageInstructionsAr); }
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

    private function localizedText(string $locale, string $french, ?string $english, ?string $arabic): string
    {
        return match ($locale) {
            'en' => $english ?: $french,
            'ar' => $arabic ?: $french,
            default => $french,
        };
    }

    private function clean(?string $value): ?string
    {
        $value = null === $value ? null : trim($value);

        return '' === $value ? null : $value;
    }
}
