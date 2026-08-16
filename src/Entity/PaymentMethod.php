<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_payment_market_code', columns: ['market_id', 'code'])]
class PaymentMethod
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Market $market;
    #[ORM\Column(length: 60)]
    private string $code;
    #[ORM\Column(length: 160)]
    private string $name;
    #[ORM\Column(length: 160, nullable: true)]
    private ?string $nameEn = null;
    #[ORM\Column(length: 160, nullable: true)]
    private ?string $nameAr = null;
    #[ORM\Column(length: 30)]
    private string $type = 'cash';
    #[ORM\Column(length: 20)]
    private string $fulfillmentScope = 'both';
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $instructions = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $instructionsEn = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $instructionsAr = null;
    #[ORM\Column(length: 80, nullable: true)]
    private ?string $recipientAccount = null;
    #[ORM\Column(length: 160, nullable: true)]
    private ?string $accountHolder = null;
    #[ORM\Column]
    private bool $active = true;

    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->name.' · '.$this->market->getName(); }
    public function getMarket(): Market { return $this->market; }
    public function setMarket(Market $market): self { $this->market = $market; return $this; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): self { $this->code = strtolower(trim($code)); return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = trim($name); return $this; }
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
    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function getFulfillmentScope(): string { return $this->fulfillmentScope; }
    public function setFulfillmentScope(string $scope): self { $this->fulfillmentScope = $scope; return $this; }
    public function getInstructions(): ?string { return $this->instructions; }
    public function setInstructions(?string $instructions): self { $this->instructions = null === $instructions ? null : trim($instructions); return $this; }
    public function getInstructionsEn(): ?string { return $this->instructionsEn; }
    public function setInstructionsEn(?string $instructions): self { $this->instructionsEn = $this->clean($instructions); return $this; }
    public function getInstructionsAr(): ?string { return $this->instructionsAr; }
    public function setInstructionsAr(?string $instructions): self { $this->instructionsAr = $this->clean($instructions); return $this; }
    public function getLocalizedInstructions(string $locale): ?string
    {
        return match ($locale) {
            'en' => $this->instructionsEn ?: $this->instructions,
            'ar' => $this->instructionsAr ?: $this->instructions,
            default => $this->instructions,
        };
    }
    public function getRecipientAccount(): ?string { return $this->recipientAccount; }
    public function setRecipientAccount(?string $account): self { $this->recipientAccount = null === $account ? null : trim($account); return $this; }
    public function getAccountHolder(): ?string { return $this->accountHolder; }
    public function setAccountHolder(?string $holder): self { $this->accountHolder = null === $holder ? null : trim($holder); return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }
    public function supportsFulfillment(string $type): bool { return 'both' === $this->fulfillmentScope || $type === $this->fulfillmentScope; }
    public function isReadyForCheckout(): bool
    {
        return $this->active
            && ('mobile_money_manual' !== $this->type || null !== $this->recipientAccount && '' !== $this->recipientAccount);
    }

    private function clean(?string $value): ?string
    {
        $value = null === $value ? null : trim($value);

        return '' === $value ? null : $value;
    }
}
