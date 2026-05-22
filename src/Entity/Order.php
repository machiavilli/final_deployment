<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['order:read']],
    denormalizationContext: ['groups' => ['order:write']]
)]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    private ?Customer $customer = null;

    #[ORM\Column(length: 255)]
    #[Groups(['order:read', 'order:write'])]
    private ?string $productName = null;

    #[ORM\Column]
    #[Groups(['order:read', 'order:write'])]
    private ?float $quantity = null;

    #[ORM\Column]
    #[Groups(['order:read', 'order:write'])]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    #[Groups(['order:read', 'order:write'])]
    private ?string $status = null;

    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?\DateTime $orderDate = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $orderNumber = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $paymentStatus = null;

    #[ORM\Column(length: 20, options: ['default' => 'manual'])]
    #[Groups(['order:read'])]
    private string $orderSource = 'manual';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shippingFullName = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $shippingPhone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shippingAddress = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $shippingCity = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $shippingPostalCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $orderNotes = null;

    public function __construct()
    {
        $this->orderDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getOrderDate(): ?\DateTime
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTime $orderDate): static
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(?string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getOrderSource(): string
    {
        return $this->orderSource;
    }

    public function setOrderSource(string $orderSource): static
    {
        $this->orderSource = $orderSource;

        return $this;
    }

    public function isWebsiteOrder(): bool
    {
        return $this->orderSource === 'website';
    }

    public function getShippingFullName(): ?string
    {
        return $this->shippingFullName;
    }

    public function setShippingFullName(?string $shippingFullName): static
    {
        $this->shippingFullName = $shippingFullName;

        return $this;
    }

    public function getShippingPhone(): ?string
    {
        return $this->shippingPhone;
    }

    public function setShippingPhone(?string $shippingPhone): static
    {
        $this->shippingPhone = $shippingPhone;

        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?string $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getShippingCity(): ?string
    {
        return $this->shippingCity;
    }

    public function setShippingCity(?string $shippingCity): static
    {
        $this->shippingCity = $shippingCity;

        return $this;
    }

    public function getShippingPostalCode(): ?string
    {
        return $this->shippingPostalCode;
    }

    public function setShippingPostalCode(?string $shippingPostalCode): static
    {
        $this->shippingPostalCode = $shippingPostalCode;

        return $this;
    }

    public function getOrderNotes(): ?string
    {
        return $this->orderNotes;
    }

    public function setOrderNotes(?string $orderNotes): static
    {
        $this->orderNotes = $orderNotes;

        return $this;
    }

    public function getLineTotal(): float
    {
        return (float) $this->price * (float) $this->quantity;
    }

    public function getPaymentMethodLabel(): string
    {
        return match ($this->paymentMethod) {
            'cod' => 'Cash on Delivery',
            'gcash' => 'GCash',
            'bank' => 'Bank Transfer',
            default => $this->paymentMethod ?? 'N/A',
        };
    }

    public function getPaymentStatusLabel(): string
    {
        return match ($this->paymentStatus) {
            'pending' => 'Pending',
            'awaiting_payment' => 'Awaiting Payment',
            'paid' => 'Paid',
            'failed' => 'Failed',
            default => $this->paymentStatus ?? 'N/A',
        };
    }
}
