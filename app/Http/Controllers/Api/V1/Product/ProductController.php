<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ProductFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('is_active', true)
            ->where('status', 'approved')
            ->with(['category:id,name,slug', 'brand:id,name,slug']);

        if ($request->has('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->has('brand')) {
            $query->whereHas('brand', fn($q) => $q->where('slug', $request->brand));
        }

        // The same wrong-way-round pair the storefront sidebar corrects: min
        // 1000 with max 0 is `price >= 1000 AND price <= 0`, a range nothing
        // can be in, so the endpoint answered an empty page for what the caller
        // plainly meant as 0-1000. Ordered through the shop's own rule, so both
        // surfaces answer the same question.
        //
        // has() also let a bound that is not a number reach the comparison:
        // `?max_price=` compared price against the empty string, and
        // `?min_price[]=1` handed an array to the query builder and 500'd.
        $bound = function (string $key) use ($request): ?float {
            $value = $request->input($key);

            return is_numeric($value) ? (float) $value : null;
        };

        [$minPrice, $maxPrice] = ProductFilters::orderedRange($bound('min_price'), $bound('max_price'));

        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        // Sold-out products sort to the back of the page, whatever sort was
        // asked for - it stays the tie-break inside each block.
        $query->inStockFirst();

        if ($request->has('sort')) {
            match ($request->sort) {
                'price_low' => $query->orderBy('price', 'asc'),
                'price_high' => $query->orderBy('price', 'desc'),
                'newest' => $query->orderBy('created_at', 'desc'),
                'rating' => $query->orderBy('rating', 'desc'),
                'popular' => $query->orderBy('sales_count', 'desc'),
                default => $query->orderBy('created_at', 'desc'),
            };
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate($request->per_page ?? 20);

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        if (!$product->is_active || $product->status !== 'approved') {
            abort(404);
        }

        $product->load([
            'category:id,name,slug,parent_id',
            'brand:id,name,slug',
            'seller:id,business_name,slug,rating',
            'variants',
            'images',
        ]);

        $product->increment('view_count');

        return response()->json([
            'data' => $product,
        ]);
    }

    public function featured(): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->where('status', 'approved')
            ->where('is_featured', true)
            ->with(['category:id,name,slug', 'brand:id,name,slug'])
            ->inStockFirst()
            ->limit(12)
            ->get();

        return response()->json([
            'data' => $products,
        ]);
    }

    public function bestsellers(): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->where('status', 'approved')
            ->with(['category:id,name,slug', 'brand:id,name,slug'])
            ->inStockFirst()
            ->orderBy('sales_count', 'desc')
            ->limit(12)
            ->get();

        return response()->json([
            'data' => $products,
        ]);
    }

    public function newArrivals(): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->where('status', 'approved')
            ->with(['category:id,name,slug', 'brand:id,name,slug'])
            ->inStockFirst()
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();

        return response()->json([
            'data' => $products,
        ]);
    }

    public function reviews(Product $product): JsonResponse
    {
        $reviews = $product->reviews()
            ->where('is_approved', true)
            ->with('user:id,first_name,last_name')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    public function questions(Product $product): JsonResponse
    {
        $questions = $product->questions()
            ->where('is_published', true)
            ->with(['user:id,first_name,last_name', 'answers'])
            ->latest()
            ->paginate(10);

        return response()->json($questions);
    }
}
