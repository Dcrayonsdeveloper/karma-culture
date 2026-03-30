<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Cache::remember('api.home', 900, function () {
            return [
                'banners' => Banner::where('is_active', true)
                    ->orderBy('position')
                    ->limit(10)
                    ->get(['id', 'title', 'image', 'link', 'position']),

                'categories' => Category::whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('position')
                    ->limit(12)
                    ->get(['id', 'name', 'slug', 'image']),

                'featured' => Product::where('is_active', true)
                    ->where('is_featured', true)
                    ->whereNull('deleted_at')
                    ->with(['images' => fn ($q) => $q->orderBy('position')->limit(1)])
                    ->orderByDesc('sales_count')
                    ->limit(10)
                    ->get(['id', 'name', 'slug', 'price', 'mrp', 'rating', 'review_count']),

                'new_arrivals' => Product::where('is_active', true)
                    ->whereNull('deleted_at')
                    ->with(['images' => fn ($q) => $q->orderBy('position')->limit(1)])
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get(['id', 'name', 'slug', 'price', 'mrp', 'rating', 'created_at']),

                'bestsellers' => Product::where('is_active', true)
                    ->whereNull('deleted_at')
                    ->with(['images' => fn ($q) => $q->orderBy('position')->limit(1)])
                    ->orderByDesc('sales_count')
                    ->limit(10)
                    ->get(['id', 'name', 'slug', 'price', 'mrp', 'rating', 'sales_count']),

                'flash_sales' => FlashSale::where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->with(['products' => fn ($q) => $q->limit(6)])
                    ->limit(3)
                    ->get(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
