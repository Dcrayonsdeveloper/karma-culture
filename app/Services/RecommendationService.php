<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\UserActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    public function recentlyViewed(?int $userId, ?string $sessionId = null, int $limit = 10): Collection
    {
        if (! $userId && ! $sessionId) {
            return new Collection();
        }

        $query = ProductView::query();

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $productIds = $query->orderByDesc('created_at')
            ->distinct('product_id')
            ->limit($limit)
            ->pluck('product_id');

        if ($productIds->isEmpty()) {
            return new Collection();
        }

        return Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->with(['images' => fn ($q) => $q->orderBy('position')->limit(1)])
            ->get()
            ->sortBy(fn ($p) => $productIds->search($p->id))
            ->values();
    }

    public function popularProducts(int $limit = 10): Collection
    {
        return Cache::remember('recommendations.popular', 900, function () use ($limit) {
            return Product::where('is_active', true)
                ->whereNull('deleted_at')
                ->orderByDesc('sales_count')
                ->orderByDesc('rating')
                ->with(['images' => fn ($q) => $q->orderBy('position')->limit(1)])
                ->limit($limit)
                ->get();
        });
    }

    public function similarProducts(int $productId, int $limit = 6): Collection
    {
        $product = Product::find($productId);

        if (! $product) {
            return new Collection();
        }

        return Cache::remember("recommendations.similar.{$productId}", 1800, function () use ($product, $productId, $limit) {
            return Product::where('is_active', true)
                ->whereNull('deleted_at')
                ->where('id', '!=', $productId)
                ->where(function ($q) use ($product) {
                    $q->where('category_id', $product->category_id)
                      ->orWhere('brand_id', $product->brand_id);
                })
                ->orderByRaw("CASE WHEN category_id = ? AND brand_id = ? THEN 0 WHEN category_id = ? THEN 1 ELSE 2 END", [
                    $product->category_id, $product->brand_id, $product->category_id,
                ])
                ->orderByDesc('sales_count')
                ->with(['images' => fn ($q) => $q->orderBy('position')->limit(1)])
                ->limit($limit)
                ->get();
        });
    }

    public function frequentlyBoughtTogether(int $productId, int $limit = 4): Collection
    {
        return Cache::remember("recommendations.fbt.{$productId}", 1800, function () use ($productId, $limit) {
            $coProductIds = DB::table('order_items as oi1')
                ->join('order_items as oi2', function ($join) use ($productId) {
                    $join->on('oi1.order_id', '=', 'oi2.order_id')
                         ->where('oi2.product_id', '!=', $productId);
                })
                ->where('oi1.product_id', $productId)
                ->select('oi2.product_id', DB::raw('COUNT(*) as frequency'))
                ->groupBy('oi2.product_id')
                ->orderByDesc('frequency')
                ->limit($limit)
                ->pluck('oi2.product_id');

            if ($coProductIds->isEmpty()) {
                return new Collection();
            }

            return Product::whereIn('id', $coProductIds)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->with(['images' => fn ($q) => $q->orderBy('position')->limit(1)])
                ->get();
        });
    }

    public function personalizedForUser(int $userId, int $limit = 12): Collection
    {
        $recentlyViewed = $this->recentlyViewed($userId, null, 4);
        $popular = $this->popularProducts(4);

        // Get categories user has browsed
        $viewedCategoryIds = ProductView::where('user_id', $userId)
            ->join('products', 'product_views.product_id', '=', 'products.id')
            ->distinct()
            ->limit(5)
            ->pluck('products.category_id');

        $categoryBased = new Collection();
        if ($viewedCategoryIds->isNotEmpty()) {
            $excludeIds = $recentlyViewed->pluck('id')->merge($popular->pluck('id'));
            $categoryBased = Product::whereIn('category_id', $viewedCategoryIds)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->whereNotIn('id', $excludeIds)
                ->orderByDesc('sales_count')
                ->with(['images' => fn ($q) => $q->orderBy('position')->limit(1)])
                ->limit(4)
                ->get();
        }

        return $recentlyViewed
            ->merge($categoryBased)
            ->merge($popular)
            ->unique('id')
            ->take($limit)
            ->values();
    }

    public function clearCacheForProduct(int $productId): void
    {
        Cache::forget("recommendations.similar.{$productId}");
        Cache::forget("recommendations.fbt.{$productId}");
        Cache::forget('recommendations.popular');
    }
}
