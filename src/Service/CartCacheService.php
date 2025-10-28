<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CartCacheService
{
    private const CACHE_KEY_CART = 'cart_';
    private const CACHE_KEY_CART_LIST = 'cart_list_';
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private readonly CacheInterface $cartResponsesCache,
    ) {
    }

    /**
     * Get cached cart response or compute it
     *
     * @param string $cartId Cart UUID
     * @param callable $callback Function to compute the value if not cached
     * @return mixed Cached or computed value
     */
    public function getCart(string $cartId, callable $callback): mixed
    {
        $key = $this->getCacheKey($cartId);

        return $this->cartResponsesCache->get($key, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(self::CACHE_TTL);
            return $callback();
        });
    }

    /**
     * Get cached cart list response or compute it
     *
     * @param int $page Page number
     * @param int $limit Items per page
     * @param callable $callback Function to compute the value if not cached
     * @return mixed Cached or computed value
     */
    public function getCartList(int $page, int $limit, callable $callback): mixed
    {
        $key = $this->getListCacheKey($page, $limit);

        return $this->cartResponsesCache->get($key, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(self::CACHE_TTL);
            return $callback();
        });
    }

    /**
     * Invalidate cache for a specific cart
     *
     * @param string $cartId Cart UUID
     */
    public function invalidateCart(string $cartId): void
    {
        $key = $this->getCacheKey($cartId);
        $this->cartResponsesCache->delete($key);

        // Also invalidate all list pages since they contain this cart
        $this->invalidateAllLists();
    }

    /**
     * Invalidate all cart list caches
     * Called when a cart is created, modified, or deleted
     */
    public function invalidateAllLists(): void
    {
        // Delete all keys matching cart_list_* pattern
        // Note: This is a simplified approach. In production, consider using cache tags
        // or a more sophisticated invalidation strategy for better performance
        $this->cartResponsesCache->delete(self::CACHE_KEY_CART_LIST . '*');
    }

    /**
     * Generate cache key for individual cart
     *
     * @param string $cartId Cart UUID
     * @return string Cache key
     */
    private function getCacheKey(string $cartId): string
    {
        return self::CACHE_KEY_CART . $cartId;
    }

    /**
     * Generate cache key for cart list
     *
     * @param int $page Page number
     * @param int $limit Items per page
     * @return string Cache key
     */
    private function getListCacheKey(int $page, int $limit): string
    {
        return self::CACHE_KEY_CART_LIST . $page . '_' . $limit;
    }
}
