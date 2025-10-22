<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CartControllerTest extends WebTestCase
{
    private static ?string $cartId = null;
    private static ?string $itemId = null;

    public function testCreateCart(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/carts');

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('createdAt', $data);

        $this->assertIsArray($data['items']);
        $this->assertCount(0, $data['items']);
        $this->assertEquals(0, $data['total']);

        self::$cartId = $data['id'];
    }

    public function testGetCart(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/carts');
        $cartId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('GET', '/api/carts/' . $cartId);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertSame($cartId, $data['id']);
    }

    public function testGetCartNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/carts/550e8400-e29b-41d4-a716-446655440000');

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('message', $data['error']);
        $this->assertArrayHasKey('code', $data['error']);
        $this->assertSame('Cart not found', $data['error']['message']);
        $this->assertSame('CART_NOT_FOUND', $data['error']['code']);
    }

    public function testListCarts(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/carts');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
    }

    public function testListCartsWithExpandParameter(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/carts');
        $cartId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('GET', '/api/carts?expand=items');

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        if (count($data) > 0) {
            $this->assertArrayHasKey('items', $data[0]);
        }
    }

    public function testAddItemToCart(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/carts');
        $cartId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('POST', '/api/carts/' . $cartId . '/items', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'productId' => 123,
            'productName' => 'Laptop',
            'price' => 999.99,
            'quantity' => 2,
            'category' => 'Electronics',
            'sku' => 'LAP-001',
        ]));

        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('items', $data);
        $this->assertCount(1, $data['items']);
        $this->assertSame(1999.98, $data['total']);

        $item = $data['items'][0];
        $this->assertArrayHasKey('id', $item);
        $this->assertSame(123, $item['productId']);
        $this->assertSame('Laptop', $item['productName']);
        $this->assertSame(999.99, $item['price']);
        $this->assertSame(2, $item['quantity']);
        $this->assertSame('Electronics', $item['category']);
        $this->assertSame('LAP-001', $item['sku']);
        $this->assertSame(1999.98, $item['subtotal']);

        self::$cartId = $cartId;
        self::$itemId = $item['id'];
    }

    public function testAddItemToCartWithMissingFields(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/carts');
        $cartId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('POST', '/api/carts/' . $cartId . '/items', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'productId' => 123,
        ]));

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('message', $data['error']);
        $this->assertArrayHasKey('code', $data['error']);
    }

    public function testAddItemToCartWithInvalidTypes(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/carts');
        $cartId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('POST', '/api/carts/' . $cartId . '/items', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'productId' => 'invalid',
            'productName' => 'Laptop',
            'price' => 999.99,
            'quantity' => 2,
        ]));

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertSame('VALIDATION_ERROR', $data['error']['code']);
    }

    public function testAddItemToCartWithInvalidValues(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/carts');
        $cartId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('POST', '/api/carts/' . $cartId . '/items', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'productId' => 123,
            'productName' => 'Laptop',
            'price' => -10.00,
            'quantity' => 0,
        ]));

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertSame('VALIDATION_ERROR', $data['error']['code']);
        $this->assertArrayHasKey('details', $data['error']);
    }

    public function testAddItemToNonExistentCart(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/carts/550e8400-e29b-41d4-a716-446655440000/items', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'productId' => 123,
            'productName' => 'Laptop',
            'price' => 999.99,
            'quantity' => 2,
        ]));

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertSame('CART_NOT_FOUND', $data['error']['code']);
    }

    public function testUpdateItemQuantity(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/carts');
        $cartId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('POST', '/api/carts/' . $cartId . '/items', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'productId' => 123,
            'productName' => 'Laptop',
            'price' => 999.99,
            'quantity' => 2,
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);
        $itemId = $data['items'][0]['id'];

        $client->request('PATCH', '/api/carts/' . $cartId . '/items/' . $itemId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'quantity' => 5,
        ]));

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(5, $data['quantity']);
        $this->assertSame(4999.95, $data['subtotal']);
        $this->assertArrayHasKey('updatedAt', $data);
        $this->assertNotNull($data['updatedAt']);
    }

    public function testUpdateItemWithMissingQuantity(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/carts');
        $cartId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('POST', '/api/carts/' . $cartId . '/items', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'productId' => 123,
            'productName' => 'Laptop',
            'price' => 999.99,
            'quantity' => 2,
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);
        $itemId = $data['items'][0]['id'];

        $client->request('PATCH', '/api/carts/' . $cartId . '/items/' . $itemId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertSame('QUANTITY_REQUIRED', $data['error']['code']);
    }

    public function testUpdateNonExistentItem(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/carts');
        $cartId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('PATCH', '/api/carts/' . $cartId . '/items/550e8400-e29b-41d4-a716-446655440000', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'quantity' => 5,
        ]));

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertSame('ITEM_NOT_FOUND', $data['error']['code']);
    }

    public function testRemoveItemFromCart(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/carts');
        $cartId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('POST', '/api/carts/' . $cartId . '/items', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'productId' => 123,
            'productName' => 'Laptop',
            'price' => 999.99,
            'quantity' => 2,
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);
        $itemId = $data['items'][0]['id'];

        $client->request('DELETE', '/api/carts/' . $cartId . '/items/' . $itemId);

        $this->assertResponseStatusCodeSame(204);
        $this->assertEmpty($client->getResponse()->getContent());
    }

    public function testRemoveNonExistentItem(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/carts');
        $cartId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('DELETE', '/api/carts/' . $cartId . '/items/550e8400-e29b-41d4-a716-446655440000');

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertSame('ITEM_NOT_FOUND', $data['error']['code']);
    }
}
