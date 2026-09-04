<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
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

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
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
        // Was a bare abort(404), which serialises to {"message":""} - the same
        // unusable empty body a bare abort(403) produces, so a client had nothing
        // to show for a product that has been unpublished or is still awaiting
        // approval. Deliberately the same answer as a product id that never
        // existed: which of the two it is, is not a caller's business.
        if (!$product->is_active || $product->status !== 'approved') {
            abort(404, 'This product is not available.');
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
