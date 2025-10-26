<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Cart;
use App\Entity\CartItem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class CartTest extends TestCase
{
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        // Setup Symfony Serializer with necessary normalizers
        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();
        $propertyTypeExtractor = new PropertyInfoExtractor(
            [$reflectionExtractor],
            [$phpDocExtractor, $reflectionExtractor]
        );

        $normalizers = [
            new DateTimeNormalizer(),
            new UidNormalizer(),
            new ArrayDenormalizer(),
            new ObjectNormalizer(
                propertyTypeExtractor: $propertyTypeExtractor,
                defaultContext: [
                    AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => fn($object) => $object->getId(),
                ]
            ),
        ];

        $encoders = [new JsonEncoder()];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

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

    public function testSerializationWithCartReadGroupReturnsCorrectStructure(): void
    {
        $cart = new Cart();
        $item = new CartItem($cart, 123, 'Laptop', 999.99, 2);
        $cart->addItem($item);

        $normalized = $this->serializer->normalize($cart, null, ['groups' => ['cart:read']]);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('items', $normalized);
        $this->assertArrayHasKey('total', $normalized);
        $this->assertArrayHasKey('createdAt', $normalized);
        $this->assertArrayHasKey('updatedAt', $normalized);

        $this->assertIsString($normalized['id']);
        $this->assertIsArray($normalized['items']);
        $this->assertCount(1, $normalized['items']);
        $this->assertSame(1999.98, $normalized['total']);
    }

    public function testSerializationToJsonWithCartReadGroup(): void
    {
        $cart = new Cart();
        $item = new CartItem($cart, 123, 'Laptop', 999.99, 2);
        $cart->addItem($item);

        $json = $this->serializer->serialize($cart, 'json', ['groups' => ['cart:read']]);
        $array = json_decode($json, true);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('items', $array);
        $this->assertArrayHasKey('total', $array);
        $this->assertIsArray($array['items']);
        $this->assertCount(1, $array['items']);

        // Verify item structure
        $serializedItem = $array['items'][0];
        $this->assertArrayHasKey('productId', $serializedItem);
        $this->assertSame(123, $serializedItem['productId']);
        $this->assertSame('Laptop', $serializedItem['productName']);
    }
}
