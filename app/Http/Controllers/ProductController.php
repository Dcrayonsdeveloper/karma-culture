<?php

namespace App\Http\Controllers;

use App\Models\BackInStockSubscription;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductView;
use App\Services\RecommendationService;
use App\Services\ReviewSchemaService;
use App\Rules\ValidationRules as V;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    /** The only orderings the listing understands. */
    private const SORTS = ['newest', 'price_asc', 'price_desc', 'rating', 'bestselling', 'name'];

    /**
     * How many values one multi-select filter may carry.
     *
     * A shopper ticks a handful of sizes. A URL carrying five thousand of them
     * is someone making the page build a five-thousand-clause WHERE, so the
     * list is cut rather than the request refused.
     */
    private const MAX_FILTER_VALUES = 50;

    public function index(Request $request): View
    {
        // The Shop It Your Way tiles store price_min/price_max/shade, so accept
        // those names as well as the form's own. Renaming the stored strings
        // instead would break any tile an admin has already set up.
        $request->merge(array_filter([
            'min_price' => $request->input('min_price', $request->input('price_min')),
            'max_price' => $request->input('max_price', $request->input('price_max')),
            'colour'    => $request->input('colour', $request->filled('shade') ? (array) $request->input('shade') : null),
            'size'      => $request->filled('size') ? (array) $request->input('size') : null,
        ], fn ($v) => $v !== null && $v !== ''));

        $filters = $this->filters($request);

        $query = Product::query()
            ->where('is_active', true)
            ->with(['category', 'brand', 'primaryImage']);

        // Category filter. Products are filed on the deepest category, so a
        // parent has to match its descendants too - otherwise picking MEN or
        // WOMEN returns nothing while their sub-categories return everything.
        if ($filters['category'] !== null) {
            $scope = Category::where('slug', $filters['category'])->first();

            if ($scope) {
                $query->whereIn('category_id', $scope->getAllDescendantIds());
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Subcategory filter
        if ($filters['subcategory'] !== []) {
            $subIds = Category::whereIn('slug', $filters['subcategory'])->pluck('id');
            if ($subIds->isNotEmpty()) {
                $query->whereIn('category_id', $subIds);
            }
        }

        // Price filter
        // Sizes live on the variants, colours on the product's Colours list, so
        // each needs its own lookup rather than a column on products.
        if ($filters['size'] !== []) {
            $sizes = $filters['size'];
            $query->whereHas('variants', function ($q) use ($sizes) {
                $q->where('is_active', true)
                  ->where('stock_quantity', '>', 0)
                  ->whereSizeIn($sizes);
            });
        }

        if ($filters['colour'] !== []) {
            $colours = $filters['colour'];
            $query->where(function ($q) use ($colours) {
                foreach ($colours as $colour) {
                    // Matches the name inside the Colours JSON, and the legacy
                    // colour stored on a variant for older products.
                    //
                    // The value is a bound parameter, so this was never an
                    // injection - but % and _ are LIKE wildcards, and a colour
                    // of "%" quietly matched every product on the site.
                    $needle = '%"'.$this->escapeLike($colour).'"%';
                    $q->orWhere('attributes', 'like', $needle)
                      ->orWhereHas('variants', fn ($vq) => $vq->where('attributes', 'like', $needle));
                }
            });
        }

        if ($filters['min_price'] !== null) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if ($filters['max_price'] !== null) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Rating filter
        if ($filters['rating'] !== null) {
            $query->where('rating', '>=', $filters['rating']);
        }

        // In stock filter
        if ($filters['in_stock']) {
            $query->where('stock_quantity', '>', 0);
        }

        // On sale filter (price less than mrp)
        if ($filters['on_sale']) {
            $query->whereNotNull('mrp')->whereColumn('price', '<', 'mrp');
        }

        // Sorting
        $sortBy = $filters['sort'];
        match ($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderBy('rating', 'desc'),
            'bestselling' => $query->orderBy('sales_count', 'desc'),
            'name' => $query->orderBy('name', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $products = $query->paginate(24)->withQueryString();

        // Get categories and subcategories for filters
        $categories = Category::whereNull('parent_id')->where('is_active', true)->get();
        $subcategories = Category::whereNotNull('parent_id')->where('is_active', true)->orderBy('name')->get();

        return view('products.index', compact('products', 'categories', 'subcategories'));
    }

    /**
     * The shop filters, validated and normalised.
     *
     * A listing URL is public, shareable and crawled, so a malformed parameter
     * degrades to "no filter" rather than to a 422 the crawler indexes. The
     * check is still a real boundary: before this, `?min_price[]=1` reached
     * `where('price', '>=', [...])` and 500'd the page, and `?rating=abc`
     * reached the comparison unchecked.
     *
     * Scalars go through the validator. The multi-value chips (size, colour,
     * subcategory) are normalised by hand instead, because Validator::valid()
     * keeps an array whole when only one of its elements fails `size.*` - it
     * would have handed the bad element straight back.
     *
     * @return array{category: ?string, subcategory: array<int, string>, size: array<int, string>, colour: array<int, string>, min_price: ?float, max_price: ?float, rating: ?int, in_stock: bool, on_sale: bool, sort: string}
     */
    private function filters(Request $request): array
    {
        $scalarKeys = ['category', 'min_price', 'max_price', 'rating', 'in_stock', 'on_sale', 'sort'];

        $validator = Validator::make(Arr::only($request->all(), $scalarKeys), [
            'category' => ['nullable', 'string', 'max:120'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'in_stock' => ['nullable', 'boolean'],
            'on_sale' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', Rule::in(self::SORTS)],
        ]);

        $safe = Arr::only($validator->valid(), $scalarKeys);

        $number = function (string $key) use ($safe): ?float {
            $value = $safe[$key] ?? null;

            return ($value === null || $value === '') ? null : (float) $value;
        };

        $category = $safe['category'] ?? null;
        $category = is_string($category) ? trim($category) : null;
        $rating = $safe['rating'] ?? null;
        $sort = $safe['sort'] ?? null;

        return [
            'category' => ($category === null || $category === '') ? null : $category,
            'subcategory' => $this->filterList($request->input('subcategory'), 120),
            'size' => $this->filterList($request->input('size'), 40),
            'colour' => $this->filterList($request->input('colour'), 60),
            'min_price' => $number('min_price'),
            'max_price' => $number('max_price'),
            'rating' => ($rating === null || $rating === '') ? null : (int) $rating,
            'in_stock' => filter_var($safe['in_stock'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'on_sale' => filter_var($safe['on_sale'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'sort' => (is_string($sort) && $sort !== '') ? $sort : 'newest',
        ];
    }

    /**
     * One multi-select filter, reduced to a bounded list of plain strings.
     *
     * Nested arrays (?size[][]=x) and objects are dropped rather than passed to
     * the query builder, which would fatal on them.
     *
     * @return array<int, string>
     */
    private function filterList(mixed $value, int $maxLength): array
    {
        $items = [];

        foreach (Arr::wrap($value) as $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $item = trim((string) $item);

            if ($item === '' || mb_strlen($item) > $maxLength) {
                continue;
            }

            $items[$item] = $item;

            if (count($items) >= self::MAX_FILTER_VALUES) {
                break;
            }
        }

        return array_values($items);
    }

    /** % and _ are LIKE wildcards; a shopper picking a colour means it literally. */
    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category',
            'brand',
            'seller',
            'images',
            'aplusImages',
            'variants',
            'reviews' => fn ($q) => $q->where('is_approved', true)->latest()->take(10),
            'reviews.user',
            'reviews.images',
            'questions' => fn ($q) => $q->where('is_answered', true)->latest()->take(5),
            'questions.answers',
        ]);

        // Record product view
        if (auth()->check()) {
            ProductView::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'product_id' => $product->id,
                ],
                ['viewed_at' => now()]
            );
        }

        // Similar / "You May Also Like" products (Task 13) - use the cached
        // RecommendationService (category+brand ranked), then top up so the
        // section always appears, and normalise relations for the product card.
        $relatedProducts = app(RecommendationService::class)->similarProducts($product->id, 8);

        if ($relatedProducts->count() < 4) {
            $exclude = $relatedProducts->pluck('id')->push($product->id)->all();
            $fill = Product::query()
                ->where('is_active', true)
                ->whereNotIn('id', $exclude)
                ->with(['category', 'primaryImage'])
                ->withCount('images')
                ->orderByDesc('images_count')
                ->inRandomOrder()
                ->take(8 - $relatedProducts->count())
                ->get();
            $relatedProducts = $relatedProducts->concat($fill);
        }

        // Ensure the relations the product card needs are loaded (cached models may be lean).
        $relatedProducts->loadMissing(['category', 'brand', 'primaryImage', 'images']);

        // Breadcrumbs
        $breadcrumbs = [];
        if ($product->category) {
            $breadcrumbs[] = ['label' => $product->category->name, 'url' => route('category.show', $product->category)];
        }
        $breadcrumbs[] = ['label' => $product->name, 'url' => null];

        // JSON-LD structured data for SEO
        $schemaService = app(ReviewSchemaService::class);
        $productSchema = $schemaService->getProductSchema($product);
        $faqSchema = $schemaService->getFaqSchema($product);

        // Frequently bought together (prefer products with images)
        $crossSellProducts = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->whereHas('images')
            ->with(['primaryImage'])
            ->inRandomOrder()
            ->take(3)
            ->get();

        if ($crossSellProducts->isEmpty()) {
            $crossSellProducts = Product::query()
                ->where('is_active', true)
                ->where('id', '!=', $product->id)
                ->where('category_id', $product->category_id)
                ->with(['primaryImage'])
                ->inRandomOrder()
                ->take(3)
                ->get();
        }

        // Compare with similar items (prefer products with images)
        $compareProducts = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->whereHas('images')
            ->with(['brand', 'primaryImage'])
            ->inRandomOrder()
            ->take(4)
            ->get();

        if ($compareProducts->count() < 2) {
            $compareProducts = Product::query()
                ->where('is_active', true)
                ->where('id', '!=', $product->id)
                ->where('category_id', $product->category_id)
                ->with(['brand', 'primaryImage'])
                ->inRandomOrder()
                ->take(4)
                ->get();
        }

        // Active coupons applicable to this product
        $activeCoupons = Coupon::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->whereRaw('(usage_limit IS NULL OR times_used < usage_limit)')
            ->orderByDesc('value')
            ->take(3)
            ->get();

        // Recent-purchase social proof. Real order data first (with buyer city);
        // the view falls back to configurable demo notifications when this is empty.
        $recentPurchases = [];
        try {
            $recentPurchases = OrderItem::where('product_id', $product->id)
                ->whereHas('order', function ($q) {
                    $q->whereNotIn('status', ['cancelled', 'failed', 'pending']);
                })
                ->with('order')
                ->latest()
                ->take(8)
                ->get()
                ->map(fn ($item) => [
                    'minutes' => max(1, (int) $item->created_at->diffInMinutes(now())),
                    'city' => data_get($item->order, 'shipping_address.city')
                        ?? data_get($item->order, 'shipping_address_snapshot.city'),
                ])
                ->filter(fn ($n) => $n['minutes'] <= 60 * 24 * 14) // within 2 weeks
                ->values()
                ->all();
        } catch (\Throwable $e) {
            $recentPurchases = [];
        }

        return view('products.show', compact('product', 'relatedProducts', 'crossSellProducts', 'compareProducts', 'activeCoupons', 'breadcrumbs', 'productSchema', 'faqSchema', 'recentPurchases'));
    }

    public function quickView(Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $product->load(['brand', 'images', 'category']);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'url' => route('product.show', $product),
            'brand' => $product->brand?->name,
            'category' => $product->category?->name,
            'price' => (float) $product->price,
            'mrp' => (float) $product->mrp,
            'discount_percentage' => $product->discount_percentage,
            'short_description' => $product->short_description,
            'rating' => (float) ($product->rating ?? 0),
            'review_count' => (int) ($product->review_count ?? 0),
            'in_stock' => $product->isInStock(),
            'stock_quantity' => $product->stock_quantity,
            'images' => $product->images->pluck('url')->values(),
            'primary_image' => $product->primary_image_url,
        ]);
    }

    public function newArrivals(): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with(['category', 'primaryImage'])
            ->orderBy('created_at', 'desc')
            ->paginate(24);

        return view('products.new-arrivals', compact('products'));
    }

    public function bestsellers(): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with(['category', 'primaryImage'])
            ->orderBy('sales_count', 'desc')
            ->paginate(24);

        return view('products.bestsellers', compact('products'));
    }

    public function askQuestion(Request $request, Product $product): JsonResponse
    {
        // Questions are shown verbatim on the product page once an admin
        // answers them, so the markup guard matters here as much as the length.
        // guest_name / guest_email are validated but deliberately not stored:
        // product_questions has no column for either (see the model's
        // $fillable). They are checked anyway so an unbounded string cannot be
        // posted at the endpoint.
        $validated = $request->validate([
            'question' => V::textarea(max: 1000, min: 10),
            'guest_name' => V::name(required: false),
            'guest_email' => V::email(required: false),
        ], [
            'question.required' => 'Please type your question.',
            'question.min' => 'Please give us a little more detail - at least 10 characters.',
            'question.max' => 'Please keep your question under 1000 characters.',
            'guest_name.min' => 'Please enter your full name.',
            'guest_email.email' => 'Enter a valid email address, like you@example.com.',
        ]);

        ProductQuestion::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'question' => $validated['question'],
        ]);

        return response()->json(['message' => 'Question submitted successfully!']);
    }

    public function notifyBackInStock(Request $request, Product $product): JsonResponse
    {
        // 'email:strict' rather than plain 'email': Laravel's permissive mode
        // accepts "shopper@gmail" (no TLD), and a back-in-stock row keyed to an
        // undeliverable address is a mail bounce nobody ever sees.
        $validated = $request->validate([
            'email' => V::email(),
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address, like you@example.com.',
        ]);

        BackInStockSubscription::updateOrCreate(
            [
                'product_id' => $product->id,
                'email' => $validated['email'],
            ],
            [
                'user_id' => auth()->id(),
                'notified' => false,
                'notified_at' => null,
            ]
        );

        return response()->json(['message' => "We'll notify you when this item is back in stock!"]);
    }
}
