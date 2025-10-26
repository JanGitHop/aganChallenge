<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class AddCartItemDto
{
    #[Assert\NotNull(message: 'ProductId required')]
    #[Assert\Positive(message: 'Product ID must be a positive integer')]
    public ?int $productId = null;

    #[Assert\NotBlank(message: 'ProductName required')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Product name must be at least {{ limit }} character long',
        maxMessage: 'Product name cannot be longer than {{ limit }} characters'
    )]
    public ?string $productName = null;

    #[Assert\NotNull(message: 'Price required')]
    #[Assert\PositiveOrZero(message: 'Price cannot be negative')]
    public ?float $price = null;

    #[Assert\NotNull(message: 'Quantity required')]
    #[Assert\Positive(message: 'Quantity must be greater than 0')]
    public ?int $quantity = null;

    #[Assert\Length(
        max: 255,
        maxMessage: 'Category cannot be longer than {{ limit }} characters'
    )]
    public ?string $category = null;

    #[Assert\Length(
        max: 255,
        maxMessage: 'SKU cannot be longer than {{ limit }} characters'
    )]
    public ?string $sku = null;
}
