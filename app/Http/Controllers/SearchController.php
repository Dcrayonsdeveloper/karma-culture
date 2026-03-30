<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\SearchLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return view('search.index', [
                'products' => collect(),
                'query' => '',
                'categories' => collect(),
                'brands' => collect(),
            ]);
        }

        // Log search
        if ($query) {
            SearchLog::create([
                'user_id'       => auth()->id(),
                'session_id'    => $request->session()->getId(),
                'query'         => $query,
                'results_count' => 0, // Will be updated after search
            ]);
        }

        $productsQuery = Product::query()
            ->where('is_active', true)
            ->with(['category', 'brand', 'primaryImage']);

        // Full-text search using Scout if configured, otherwise basic search
        if (config('scout.driver')) {
            $productIds = Product::search($query)->keys();
            $productsQuery->whereIn('id', $productIds);
        } else {
            $productsQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$query}%"))
                  ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'like', "%{$query}%"));
            });
        }

        // Apply filters
        if ($request->filled('category')) {
            $productsQuery->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('brand')) {
            $productsQuery->whereHas('brand', fn ($q) => $q->where('slug', $request->brand));
        }

        if ($request->filled('min_price')) {
            $productsQuery->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $productsQuery->where('price', '<=', $request->max_price);
        }

        // Sorting
        $sortBy = $request->get('sort', 'relevance');
        match ($sortBy) {
            'price_asc' => $productsQuery->orderBy('price', 'asc'),
            'price_desc' => $productsQuery->orderBy('price', 'desc'),
            'rating' => $productsQuery->orderBy('rating', 'desc'),
            'newest' => $productsQuery->orderBy('created_at', 'desc'),
            default => $productsQuery->orderBy('sales_count', 'desc'),
        };

        $products = $productsQuery->paginate(24)->withQueryString();

        // Update search log with results count
        if ($query) {
            SearchLog::where('query', $query)
                ->where('created_at', '>=', now()->subMinute())
                ->latest()
                ->first()
                ?->update(['results_count' => $products->total()]);
        }

        // Get available filters
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->whereHas('products', fn ($q) => $q->where('is_active', true))
            ->get();

        $brands = Brand::where('is_active', true)
            ->whereHas('products', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        return view('search.index', compact('products', 'query', 'categories', 'brands'));
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        // Product suggestions
        $products = Product::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->with(['category', 'primaryImage'])
            ->orderBy('sales_count', 'desc')
            ->take(5)
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'url' => route('product.show', $product),
                'image' => $product->primary_image_url,
                'price' => (float) $product->price,
                'category' => $product->category?->name,
                'type' => 'product',
            ]);

        // Category suggestions
        $categories = Category::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->take(3)
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'url' => route('category.show', $category),
                'image' => $category->image_url,
                'type' => 'category',
            ]);

        // Brand suggestions
        $brands = Brand::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->take(3)
            ->get()
            ->map(fn ($brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'url' => route('brands.show', $brand),
                'image' => $brand->logo_url,
                'type' => 'brand',
            ]);

        return response()->json([
            'suggestions' => $products->merge($categories)->merge($brands),
        ]);
    }
}
