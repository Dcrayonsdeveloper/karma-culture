<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    private const PREFIX = 'fvk_';

    public function getProduct(int $productId, callable $callback, int $ttl = 1800)
    {
        return Cache::remember(self::PREFIX . "product.{$productId}", $ttl, $callback);
    }

    public function getProductList(string $key, callable $callback, int $ttl = 300)
    {
        return Cache::remember(self::PREFIX . "products.{$key}", $ttl, $callback);
    }

    public function getCategoryTree(callable $callback, int $ttl = 3600)
    {
        return Cache::remember(self::PREFIX . 'category_tree', $ttl, $callback);
    }

    public function getHomepageSections(callable $callback, int $ttl = 900)
    {
        return Cache::remember(self::PREFIX . 'homepage_sections', $ttl, $callback);
    }

    public function getSearchResults(string $query, array $filters, callable $callback, int $ttl = 300)
    {
        $key = self::PREFIX . 'search.' . md5($query . json_encode($filters));
        return Cache::remember($key, $ttl, $callback);
    }

    public function invalidateProduct(int $productId): void
    {
        Cache::forget(self::PREFIX . "product.{$productId}");
        Cache::forget('recommendations.similar.' . $productId);
        Cache::forget('recommendations.fbt.' . $productId);
        Cache::forget('recommendations.popular');
    }

    public function invalidateCategory(): void
    {
        Cache::forget(self::PREFIX . 'category_tree');
    }

    public function invalidateHomepage(): void
    {
        Cache::forget(self::PREFIX . 'homepage_sections');
    }

    public function invalidateAll(): void
    {
        $patterns = [
            self::PREFIX . 'product.',
            self::PREFIX . 'products.',
            self::PREFIX . 'category_tree',
            self::PREFIX . 'homepage_sections',
            self::PREFIX . 'search.',
            'recommendations.',
        ];

        // For database/file cache, we can't pattern-delete.
        // Use tagged caching if available, otherwise clear all.
        Cache::flush();
    }

    public function warmProducts(array $productIds): void
    {
        foreach ($productIds as $id) {
            Cache::forget(self::PREFIX . "product.{$id}");
        }
    }
}
