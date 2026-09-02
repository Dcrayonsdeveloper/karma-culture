<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\SearchLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * The longest search phrase we accept.
     *
     * search_logs.query is a varchar(255), so an unbounded phrase was not just
     * untidy - it reached the INSERT below and blew up the page with a
     * "Data too long for column 'query'" on a strict MySQL. 100 characters is
     * far longer than any real product search.
     */
    private const MAX_QUERY = 100;

    /** The only orderings the listing understands. */
    private const SORTS = ['relevance', 'price_asc', 'price_desc', 'rating', 'newest'];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $query = $filters['q'];

        if ($query === '') {
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

        // Apply filters. Every value here has been through filters() above, so
        // none of them can arrive as an array (which the query builder would
        // choke on) or as an oversized string.
        if ($filters['category'] !== null) {
            $productsQuery->whereHas('category', fn ($q) => $q->where('slug', $filters['category']));
        }

        if ($filters['brand'] !== null) {
            $productsQuery->whereHas('brand', fn ($q) => $q->where('slug', $filters['brand']));
        }

        if ($filters['min_price'] !== null) {
            $productsQuery->where('price', '>=', $filters['min_price']);
        }

        if ($filters['max_price'] !== null) {
            $productsQuery->where('price', '<=', $filters['max_price']);
        }

        // Sorting
        $sortBy = $filters['sort'];
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
     * The query string, validated and normalised.
     *
     * A search URL is public, shareable and crawled: a malformed parameter has
     * to degrade to "no filter", not to a 422 or a redirect a crawler will
     * follow. The validator is still the boundary - anything it rejects is
     * dropped here and never reaches the query builder or the search_logs
     * insert. Everything this returns is a scalar of a known shape.
     *
     * @return array{q: string, category: ?string, brand: ?string, min_price: ?float, max_price: ?float, sort: string}
     */
    private function filters(Request $request): array
    {
        $keys = ['q', 'category', 'brand', 'min_price', 'max_price', 'sort'];

        $validator = Validator::make(Arr::only($request->query(), $keys), [
            'q' => ['nullable', 'string', 'max:'.self::MAX_QUERY],
            'category' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'sort' => ['nullable', 'string', Rule::in(self::SORTS)],
        ]);

        $safe = Arr::only($validator->valid(), $keys);

        $string = function (?string $key) use ($safe): ?string {
            $value = $safe[$key] ?? null;
            if (! is_string($value)) {
                return null;
            }
            $value = trim($value);

            return $value === '' ? null : $value;
        };

        return [
            'q' => $string('q') ?? '',
            'category' => $string('category'),
            'brand' => $string('brand'),
            'min_price' => isset($safe['min_price']) && $safe['min_price'] !== '' && $safe['min_price'] !== null
                ? (float) $safe['min_price']
                : null,
            'max_price' => isset($safe['max_price']) && $safe['max_price'] !== '' && $safe['max_price'] !== null
                ? (float) $safe['max_price']
                : null,
            'sort' => $string('sort') ?? 'relevance',
        ];
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
        // Same boundary as the full search page: `q` arrives from an XHR the
        // customer's browser makes on every keystroke, so it is exactly as
        // untrusted as the address bar. A non-string (?q[]=x) used to reach
        // strlen() and 500 the endpoint.
        $query = $this->filters($request)['q'];

        if (mb_strlen($query) < 2) {
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
                'image' => $category->image_src ?? asset_v('images/no-product-image.svg'),
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
                'image' => $brand->logo_src ?? asset_v('images/no-product-image.svg'),
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
