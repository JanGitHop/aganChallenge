<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Cart;
use App\Entity\CartItem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CartTest extends TestCase
{
    public function testConstructorCreatesCartWithDefaults(): void
    {
        $cart = new Cart();

        $this->assertInstanceOf(Uuid::class, $cart->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $cart->getCreatedAt());
        $this->assertNull($cart->getUpdatedAt());
        $this->assertCount(0, $cart->getItems());
        $this->assertSame(0.0, $cart->getTotal());
    }

    public function testAddItemAddsItemToCart(): void
    {
        $cart = new Cart();
        $item = new CartItem($cart, 123, 'Laptop', 999.99, 2);

        $cart->addItem($item);

        $this->assertCount(1, $cart->getItems());
        $this->assertTrue($cart->getItems()->contains($item));
    }

    public function testAddItemDoesNotAddDuplicateItem(): void
    {
        $cart = new Cart();
        $item = new CartItem($cart, 123, 'Laptop', 999.99, 2);

        $cart->addItem($item);
        $cart->addItem($item);

        $this->assertCount(1, $cart->getItems());
    }

    public function testRemoveItemRemovesItemFromCart(): void
    {
        $cart = new Cart();
        $item = new CartItem($cart, 123, 'Laptop', 999.99, 2);

        $cart->addItem($item);
        $this->assertCount(1, $cart->getItems());

        $cart->removeItem($item);
        $this->assertCount(0, $cart->getItems());
    }

    public function testGetTotalCalculatesCorrectTotal(): void
    {
        $cart = new Cart();
        $item1 = new CartItem($cart, 123, 'Laptop', 999.99, 2);
        $item2 = new CartItem($cart, 456, 'Mouse', 29.99, 1);

        $cart->addItem($item1);
        $cart->addItem($item2);

        // (999.99 * 2) + (29.99 * 1) = 1999.98 + 29.99 = 2029.97
        $this->assertSame(2029.97, $cart->getTotal());
    }

    public function testGetTotalReturnsZeroForEmptyCart(): void
    {
        $cart = new Cart();

        $this->assertSame(0.0, $cart->getTotal());
    }

    public function testGetItemReturnsCorrectItem(): void
    {
        $cart = new Cart();
        $item = new CartItem($cart, 123, 'Laptop', 999.99, 2);
        $cart->addItem($item);

        $itemId = (string) $item->getId();
        $foundItem = $cart->getItem($itemId);

        $this->assertNotNull($foundItem);
        $this->assertSame($item, $foundItem);
    }

    public function testGetItemReturnsNullWhenItemNotFound(): void
    {
        $cart = new Cart();
        $item = new CartItem($cart, 123, 'Laptop', 999.99, 2);
        $cart->addItem($item);

        $foundItem = $cart->getItem('non-existent-id');

        $this->assertNull($foundItem);
    }

    public function testSetUpdatedAt(): void
    {
        $cart = new Cart();
        $updatedAt = new \DateTimeImmutable('2025-10-22 12:00:00');

        $cart->setUpdatedAt($updatedAt);

        $this->assertSame($updatedAt, $cart->getUpdatedAt());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $cart = new Cart();
        $item = new CartItem($cart, 123, 'Laptop', 999.99, 2);
        $cart->addItem($item);

        $array = $cart->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('items', $array);
        $this->assertArrayHasKey('total', $array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);

        $this->assertIsString($array['id']);
        $this->assertIsArray($array['items']);
        $this->assertCount(1, $array['items']);
        $this->assertSame(1999.98, $array['total']);
    }
}
