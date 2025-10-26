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

final class CartItemTest extends TestCase
{
    private Cart $cart;
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        $this->cart = new Cart();

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

    public function testSerializationWithCartReadGroupReturnsCorrectStructure(): void
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

        $normalized = $this->serializer->normalize($item, null, ['groups' => ['cart:read']]);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('productId', $normalized);
        $this->assertArrayHasKey('productName', $normalized);
        $this->assertArrayHasKey('category', $normalized);
        $this->assertArrayHasKey('sku', $normalized);
        $this->assertArrayHasKey('price', $normalized);
        $this->assertArrayHasKey('quantity', $normalized);
        $this->assertArrayHasKey('subtotal', $normalized);
        $this->assertArrayHasKey('addedAt', $normalized);
        $this->assertArrayHasKey('updatedAt', $normalized);

        $this->assertIsString($normalized['id']);
        $this->assertSame(123, $normalized['productId']);
        $this->assertSame('Laptop', $normalized['productName']);
        $this->assertSame('Electronics', $normalized['category']);
        $this->assertSame('LAP-001', $normalized['sku']);
        $this->assertSame(999.99, $normalized['price']);
        $this->assertSame(2, $normalized['quantity']);
        $this->assertSame(1999.98, $normalized['subtotal']);
    }

    public function testSerializationToJsonWithCartReadGroup(): void
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

        $json = $this->serializer->serialize($item, 'json', ['groups' => ['cart:read']]);
        $array = json_decode($json, true);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('productId', $array);
        $this->assertArrayHasKey('subtotal', $array);
        $this->assertSame(123, $array['productId']);
        $this->assertSame('Laptop', $array['productName']);
        $this->assertSame(1999.98, $array['subtotal']);
    }
}
