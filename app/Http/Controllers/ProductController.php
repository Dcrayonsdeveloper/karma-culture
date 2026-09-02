<?php

namespace App\Http\Controllers;

use App\Models\BackInStockSubscription;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductVariant;
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

        // The category tree in one query. The sidebar needs a subtree per row, and
        // Category::getAllDescendantIds() walks the children relation, lazy-loading a
        // query per level - so asking it per row cost dozens of round trips.
        $tree = Category::query()->get(['id', 'parent_id', 'name', 'slug', 'is_active']);
        $childrenByParent = $tree->groupBy('parent_id');
        $descendantIds = function (int $id) use (&$descendantIds, $childrenByParent): array {
            $ids = [$id];
            foreach ($childrenByParent->get($id, collect()) as $child) {
                $ids = array_merge($ids, $descendantIds($child->id));
            }

            return $ids;
        };

        // Category filter. Products are filed on the deepest category, so a parent has
        // to match its descendants too - otherwise picking MEN or WOMEN returns nothing
        // while their sub-categories return everything. A slug that resolves to nothing
        // matches nothing, rather than quietly dropping the filter and handing back the
        // whole shop.
        $categoryIds = null;
        if ($filters['category'] !== null) {
            $scope = $tree->firstWhere('slug', $filters['category']);
            $categoryIds = $scope ? $descendantIds($scope->id) : [];
        }

        $subcategoryIds = null;
        if ($filters['subcategory'] !== []) {
            $subcategoryIds = $tree->whereIn('slug', $filters['subcategory'])
                ->flatMap(fn ($c) => $descendantIds($c->id))
                ->unique()
                ->values()
                ->all();
        }

        /**
         * The one query behind both the grid and the sidebar.
         *
         * $except names the dimensions to leave out, which is what makes the sidebar
         * live: the Size list is built from everything matching the OTHER filters, so
         * picking a category immediately reshapes the sizes, colours and brands on
         * offer. A facet never applies its own filter, because that is the only way two
         * sizes can both stay pickable - filtering to XL first would leave XL as the
         * only size the list could ever show.
         */
        $filtered = function (array $except = []) use ($filters, $categoryIds, $subcategoryIds) {
            $query = Product::query()->where('is_active', true);

            if ($categoryIds !== null && ! in_array('category', $except, true)) {
                $query->whereIn('category_id', $categoryIds ?: [0]);
            }

            if ($subcategoryIds !== null && ! in_array('subcategory', $except, true)) {
                $query->whereIn('category_id', $subcategoryIds ?: [0]);
            }

            // Sizes live on the variants, colours on the product's Colours list, so each
            // needs its own lookup rather than a column on products. Stock is
            // deliberately not part of the size match: a sold-out size still belongs to
            // the product, and "In Stock Only" is the control for hiding it.
            if ($filters['size'] !== [] && ! in_array('size', $except, true)) {
                $sizes = $filters['size'];
                $query->whereHas('variants', fn ($q) => $q->where('is_active', true)->whereSizeIn($sizes));
            }

            if ($filters['colour'] !== [] && ! in_array('colour', $except, true)) {
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

            // Brand filter. Slugs, not ids, so the URL stays readable and shareable.
            if ($filters['brand'] !== [] && ! in_array('brand', $except, true)) {
                $brandSlugs = $filters['brand'];
                $query->whereHas('brand', fn ($q) => $q->whereIn('slug', $brandSlugs));
            }

            if (! in_array('price', $except, true)) {
                if ($filters['min_price'] !== null) {
                    $query->where('price', '>=', $filters['min_price']);
                }
                if ($filters['max_price'] !== null) {
                    $query->where('price', '<=', $filters['max_price']);
                }
            }

            if ($filters['rating'] !== null && ! in_array('rating', $except, true)) {
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

            return $query;
        };

        $query = $filtered()->with(['category', 'brand', 'primaryImage']);

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

        // How many matching products sit on each category, in ONE grouped query per
        // facet. The sidebar then totals a subtree in memory - a count query per row
        // would be forty round trips on a forty-category shop.
        $countsBy = fn (array $except) => $filtered($except)
            ->select('category_id')
            ->selectRaw('count(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        $totalUnder = function (int $id, $counts) use ($descendantIds): int {
            $total = 0;
            foreach ($descendantIds($id) as $categoryId) {
                $total += (int) ($counts[$categoryId] ?? 0);
            }

            return $total;
        };

        // The category radio list keeps every top-level category, so the shape of the
        // catalogue stays visible, but carries the count it would return under the
        // shopper's other filters. Sub-category ticks are left out of that count: they
        // belong to the category being replaced, so counting them would show 0 beside
        // every other category.
        $categoryCounts = $countsBy(['category', 'subcategory']);
        $categories = $tree->whereNull('parent_id')
            ->where('is_active', true)
            ->each(fn ($c) => $c->setAttribute('products_total', $totalUnder($c->id, $categoryCounts)))
            ->values();

        // Sub-categories, on the other hand, are narrowed: the list used to offer every
        // sub-category in the shop, so a shopper inside MEN was invited to tick Sarees
        // and got nothing back. A ticked one is always kept, or it could never be
        // unticked once another filter had emptied it.
        $subcategoryCounts = $countsBy(['subcategory']);
        $subcategories = $tree->where('is_active', true)
            ->filter(fn ($c) => $c->parent_id !== null)
            ->when($categoryIds !== null, fn ($rows) => $rows->whereIn('id', $categoryIds ?: [0]))
            ->each(fn ($c) => $c->setAttribute('products_total', $totalUnder($c->id, $subcategoryCounts)))
            ->filter(fn ($c) => $c->products_total > 0 || in_array($c->slug, $filters['subcategory'], true))
            ->sortBy('name')
            ->values();

        // Sizes carried by the products the shopper is currently looking at. The list
        // used to be every size in the shop, cached globally, so narrowing to a single
        // polo still offered UK 7 to UK 11 and picking one returned nothing.
        $filterSizes = ProductVariant::query()
            ->where('is_active', true)
            ->whereIn('product_id', $filtered(['size'])->select('id'))
            ->pluck('name')
            ->map(fn ($n) => ProductVariant::sizeLabel($n))
            ->filter()
            ->merge($filters['size'])
            ->unique()
            ->sortBy(fn ($s) => ProductVariant::sizeRank($s))
            ->values();

        // Colours get the same treatment, read off the product's own Colours list. The
        // ticked ones are concatenated last so a real swatch hex wins over the hex-less
        // placeholder when unique() collapses the pair.
        $filterColours = $filtered(['colour'])
            ->pluck('attributes')
            ->flatMap(fn ($a) => collect(data_get($a, 'Colours', []))
                ->map(fn ($c) => is_array($c)
                    ? ['name' => trim((string) ($c['name'] ?? '')), 'hex' => $c['hex'] ?? null]
                    : ['name' => trim((string) $c), 'hex' => null]))
            ->filter(fn ($c) => $c['name'] !== '')
            ->concat(collect($filters['colour'])->map(fn ($n) => ['name' => $n, 'hex' => null]))
            ->unique('name')
            ->sortBy('name')
            ->values();

        // Only brands that actually carry a matching product. The table holds 26 rows
        // left over from the demo seed, and offering "Canon" on a kidswear shop returns
        // nothing. The view has always dereferenced $brands for the active filter chip
        // and the meta description, so omitting it 500'd /shop?brand=x.
        $brands = Brand::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereIn('id', $filtered(['brand'])->select('brand_id'))
                ->orWhereIn('slug', $filters['brand']))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('products.index', compact('products', 'categories', 'subcategories', 'brands', 'filterSizes', 'filterColours'));
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
     * @return array{category: ?string, subcategory: array<int, string>, size: array<int, string>, colour: array<int, string>, brand: array<int, string>, min_price: ?float, max_price: ?float, rating: ?int, in_stock: bool, on_sale: bool, sort: string}
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
            'brand' => $this->filterList($request->input('brand'), 120),
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

        // Record product view. Guests included — they are most of the traffic,
        // and counting only signed-in customers left the Analytics report
        // reporting on a small self-selected slice of the site.
        ProductView::record($product);

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
            // Four fills the offers grid on the product page; the view filters
            // out any whose minimum spend this product cannot reach.
            ->take(4)
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
