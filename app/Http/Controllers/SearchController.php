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
                $q->where(fn ($w) => $this->matchTerms($w, 'name', $query))
                  ->orWhere(fn ($w) => $this->matchTerms($w, 'description', $query))
                  ->orWhere(fn ($w) => $this->matchTerms($w, 'sku', $query))
                  ->orWhereHas('category', fn ($cq) => $this->matchTerms($cq, 'name', $query))
                  ->orWhereHas('brand', fn ($bq) => $this->matchTerms($bq, 'name', $query));
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

    /**
     * Match a phrase against a column, tolerating word order and punctuation.
     *
     * A single LIKE on the whole phrase only matched a literal substring, so
     * "T shirt" and "tshirt" both found nothing while "T-Shirt" products
     * existed. Two rules run together:
     *   - every word must appear somewhere in the value, in any order
     *   - or the value matches once spacing and punctuation are stripped
     */
    private function matchTerms($builder, string $column, string $query)
    {
        // The column is chosen in this class, never by the request, but keep the
        // raw fragment below provably safe.
        if (! preg_match('/^[a-z_]+$/', $column)) {
            return $builder;
        }

        $terms = preg_split('/\s+/', trim($query), -1, PREG_SPLIT_NO_EMPTY);
        $squashed = preg_replace('/[^\p{L}\p{N}]+/u', '', $query);

        return $builder->where(function ($q) use ($terms, $squashed, $column) {
            $q->where(function ($all) use ($terms, $column) {
                foreach ($terms as $term) {
                    $all->where($column, 'like', '%'.$this->escapeLike($term).'%');
                }
            });

            if ($squashed !== '') {
                $q->orWhereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(LOWER(`{$column}`), '-', ''), ' ', ''), '.', ''), '_', '') LIKE ?",
                    ['%'.mb_strtolower($this->escapeLike($squashed)).'%']
                );
            }
        });
    }

    /** % and _ are LIKE wildcards; a shopper typing them means them literally. */
    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
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
            ->where(fn ($w) => $this->matchTerms($w, 'name', $query))
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
            ->where(fn ($w) => $this->matchTerms($w, 'name', $query))
            ->take(3)
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'url' => route('category.show', $category),
                'image' => $category->image_src ?? asset('images/no-product-image.svg'),
                'type' => 'category',
            ]);

        // Brand suggestions
        $brands = Brand::query()
            ->where('is_active', true)
            ->where(fn ($w) => $this->matchTerms($w, 'name', $query))
            ->take(3)
            ->get()
            ->map(fn ($brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'url' => route('brands.show', $brand),
                'image' => $brand->logo_src ?? asset('images/no-product-image.svg'),
                'type' => 'brand',
            ]);

        // Start from a base collection. ->map() only downgrades an Eloquent
        // collection to a base one when it can see a non-model item, so a query
        // that matched nothing stays an Eloquent collection - and Eloquent's
        // merge() calls getKey() on every item, which throws on these arrays.
        // That made any search with no product hits but a category hit return a
        // 500 instead of results ("kurta" being one).
        return response()->json([
            'suggestions' => collect()
                ->merge($products)
                ->merge($categories)
                ->merge($brands)
                ->values(),
        ]);
    }
}
