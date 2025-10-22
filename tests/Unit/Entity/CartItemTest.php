<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Cart;
use App\Entity\CartItem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CartItemTest extends TestCase
{
    private Cart $cart;

    protected function setUp(): void
    {
        $this->cart = new Cart();
    }

    public function testConstructorCreatesCartItemWithRequiredFields(): void
    {
        $item = new CartItem(
            $this->cart,
            123,
            'Laptop',
            999.99,
            2
        );

        $this->assertInstanceOf(Uuid::class, $item->getId());
        $this->assertSame($this->cart, $item->getCart());
        $this->assertSame(123, $item->getProductId());
        $this->assertSame('Laptop', $item->getProductName());
        $this->assertSame(999.99, $item->getPrice());
        $this->assertSame(2, $item->getQuantity());
        $this->assertNull($item->getCategory());
        $this->assertNull($item->getSku());
        $this->assertInstanceOf(\DateTimeImmutable::class, $item->getAddedAt());
        $this->assertNull($item->getUpdatedAt());
    }

    public function testConstructorCreatesCartItemWithOptionalFields(): void
    {
        $item = new CartItem(
            $this->cart,
            123,
            'Laptop',
            999.99,
            2,
            'Electronics',
            'LAP-001'
        );

        $this->assertSame('Electronics', $item->getCategory());
        $this->assertSame('LAP-001', $item->getSku());
    }

    public function testGetSubtotalCalculatesCorrectly(): void
    {
        $item = new CartItem($this->cart, 123, 'Laptop', 999.99, 2);

        $this->assertSame(1999.98, $item->getSubtotal());
    }

    public function testGetSubtotalWithSingleQuantity(): void
    {
        $item = new CartItem($this->cart, 123, 'Mouse', 29.99, 1);

        $this->assertSame(29.99, $item->getSubtotal());
    }

    public function testSetQuantityUpdatesQuantity(): void
    {
        $item = new CartItem($this->cart, 123, 'Laptop', 999.99, 2);

        $item->setQuantity(5);

        $this->assertSame(5, $item->getQuantity());
        $this->assertSame(4999.95, $item->getSubtotal());
    }

    public function testSetQuantityUpdatesUpdatedAt(): void
    {
        $item = new CartItem($this->cart, 123, 'Laptop', 999.99, 2);

        $this->assertNull($item->getUpdatedAt());

        $item->setQuantity(3);

        $this->assertInstanceOf(\DateTimeImmutable::class, $item->getUpdatedAt());
    }

    public function testSetUpdatedAt(): void
    {
        $item = new CartItem($this->cart, 123, 'Laptop', 999.99, 2);
        $updatedAt = new \DateTimeImmutable('2025-10-22 12:00:00');

        $item->setUpdatedAt($updatedAt);

        $this->assertSame($updatedAt, $item->getUpdatedAt());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $item = new CartItem(
            $this->cart,
            123,
            'Laptop',
            999.99,
            2,
            'Electronics',
            'LAP-001'
        );

        $array = $item->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('productId', $array);
        $this->assertArrayHasKey('productName', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('sku', $array);
        $this->assertArrayHasKey('price', $array);
        $this->assertArrayHasKey('quantity', $array);
        $this->assertArrayHasKey('subtotal', $array);
        $this->assertArrayHasKey('addedAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);

        $this->assertIsString($array['id']);
        $this->assertSame(123, $array['productId']);
        $this->assertSame('Laptop', $array['productName']);
        $this->assertSame('Electronics', $array['category']);
        $this->assertSame('LAP-001', $array['sku']);
        $this->assertSame(999.99, $array['price']);
        $this->assertSame(2, $array['quantity']);
        $this->assertSame(1999.98, $array['subtotal']);
    }

    public function testRequiredFieldsReturnsCorrectArray(): void
    {
        $requiredFields = CartItem::requiredFields();

        $this->assertIsArray($requiredFields);
        $this->assertContains('productId', $requiredFields);
        $this->assertContains('productName', $requiredFields);
        $this->assertContains('price', $requiredFields);
        $this->assertContains('quantity', $requiredFields);
        $this->assertCount(4, $requiredFields);
    }
}
