<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CartItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
class CartItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['cart:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Cart::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private Cart $cart;

    #[ORM\Column]
    #[Groups(['cart:read'])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private int $productId;

    #[ORM\Column(length: 255)]
    #[Groups(['cart:read'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    private string $productName;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['cart:read'])]
    #[Assert\Length(max: 255)]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['cart:read'])]
    #[Assert\Length(max: 255)]
    private ?string $sku = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['cart:read'])]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private float $price;

    #[ORM\Column]
    #[Groups(['cart:read'])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private int $quantity;

    #[ORM\Column]
    #[Groups(['cart:read'])]
    private \DateTimeImmutable $addedAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['cart:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(
        Cart $cart,
        int $productId,
        string $productName,
        float $price,
        int $quantity,
        ?string $category = null,
        ?string $sku = null,
    ) {
        $this->id = Uuid::v4();
        $this->cart = $cart;
        $this->productId = $productId;
        $this->productName = $productName;
        $this->price = $price;
        $this->quantity = $quantity;
        $this->category = $category;
        $this->sku = $sku;
        $this->addedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getAddedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[Groups(['cart:read'])]
    public function getSubtotal(): float
    {
        return $this->price * $this->quantity;
    }
}
