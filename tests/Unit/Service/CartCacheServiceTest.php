<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\CartCacheService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CartCacheServiceTest extends TestCase
{
    private const CART_ID = '550e8400-e29b-41d4-a716-446655440000';

    private CacheInterface $cache;
    private CartCacheService $cacheService;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->cacheService = new CartCacheService($this->cache);
    }

    public function testGetCartCallsCacheWithCorrectKey(): void
    {
        $expectedResult = ['cart' => 'data'];

        $this->cache->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('cart_' . self::CART_ID),
                $this->isCallable()
            )
            ->willReturnCallback(function ($key, $callback) use ($expectedResult) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(300);
                return $callback($item);
            });

        $result = $this->cacheService->getCart(self::CART_ID, fn () => $expectedResult);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetCartExecutesCallbackWhenCacheMiss(): void
    {
        $computedValue = ['cart' => 'computed'];

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())->method('expiresAfter')->with(300);
                return $callback($item);
            });

        $callbackExecuted = false;
        $result = $this->cacheService->getCart(self::CART_ID, function () use ($computedValue, &$callbackExecuted) {
            $callbackExecuted = true;
            return $computedValue;
        });

        $this->assertTrue($callbackExecuted);
        $this->assertEquals($computedValue, $result);
    }

    public function testGetCartListCallsCacheWithCorrectKey(): void
    {
        $page = 2;
        $limit = 10;
        $expectedResult = ['carts' => 'list'];

        $this->cache->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('cart_list_2_10'),
                $this->isCallable()
            )
            ->willReturnCallback(function ($key, $callback) use ($expectedResult) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(300);
                return $callback($item);
            });

        $result = $this->cacheService->getCartList($page, $limit, fn () => $expectedResult);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetCartListExecutesCallbackWhenCacheMiss(): void
    {
        $page = 1;
        $limit = 20;
        $computedValue = ['carts' => 'list'];

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())->method('expiresAfter')->with(300);
                return $callback($item);
            });

        $callbackExecuted = false;
        $result = $this->cacheService->getCartList($page, $limit, function () use ($computedValue, &$callbackExecuted) {
            $callbackExecuted = true;
            return $computedValue;
        });

        $this->assertTrue($callbackExecuted);
        $this->assertEquals($computedValue, $result);
    }

    public function testInvalidateCartDeletesSpecificCartCache(): void
    {
        $expectedCalls = [
            'cart_' . self::CART_ID,
            'cart_list_*',
        ];
        $callIndex = 0;

        $this->cache->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(function ($key) use ($expectedCalls, &$callIndex) {
                $this->assertEquals($expectedCalls[$callIndex], $key);
                $callIndex++;
                return true;
            });

        $this->cacheService->invalidateCart(self::CART_ID);
    }

    public function testInvalidateCartAlsoInvalidatesAllLists(): void
    {
        $this->cache->expects($this->exactly(2))
            ->method('delete');

        $this->cacheService->invalidateCart(self::CART_ID);
    }

    public function testInvalidateAllListsDeletesListPattern(): void
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with($this->equalTo('cart_list_*'));

        $this->cacheService->invalidateAllLists();
    }

    public function testCacheTtlIsSetTo300Seconds(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(300);
                return $callback($item);
            });

        $this->cacheService->getCart(self::CART_ID, fn () => ['test']);
    }
}
