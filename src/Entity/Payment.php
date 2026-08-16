<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Payment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\OneToOne(inversedBy: 'payment')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CustomerOrder $customerOrder;
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private PaymentMethod $method;
    #[ORM\Column(length: 30)]
    private string $status = 'pending';
    #[ORM\Column]
    private int $amountMinor;
    #[ORM\Column(length: 3)]
    private string $currencyCode;
    #[ORM\Column(length: 160, nullable: true)]
    private ?string $externalReference = null;
    #[ORM\Column(length: 40)]
    private string $payerPhone;
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->createdAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getCustomerOrder(): CustomerOrder { return $this->customerOrder; }
    public function setCustomerOrder(CustomerOrder $order): self { $this->customerOrder = $order; $order->setPayment($this); return $this; }
    public function getMethod(): PaymentMethod { return $this->method; }
    public function setMethod(PaymentMethod $method): self { $this->method = $method; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getAmountMinor(): int { return $this->amountMinor; }
    public function setAmountMinor(int $amount): self { $this->amountMinor = $amount; return $this; }
    public function getCurrencyCode(): string { return $this->currencyCode; }
    public function setCurrencyCode(string $code): self { $this->currencyCode = $code; return $this; }
    public function getExternalReference(): ?string { return $this->externalReference; }
    public function setExternalReference(?string $reference): self { $this->externalReference = $reference; return $this; }
    public function getPayerPhone(): string { return $this->payerPhone; }
    public function setPayerPhone(string $phone): self { $this->payerPhone = trim($phone); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
