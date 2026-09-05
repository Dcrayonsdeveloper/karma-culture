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
use App\Support\ProductFilters;
use App\Support\ProductOptions;
use App\Rules\ValidationRules as V;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * The picks for /products, if the admin has made any.
     *
     * Shared by index() and filtersPanel() so the grid and the filter drawer
     * can never describe different sets of products - the drawer is fetched on
     * every page of the site, so a divergence here shows up everywhere.
     *
     * @return array<int, int>
     */
    private function shopAllPicks(): array
    {
        return Category::pickedProductIds('shop_all');
    }

    public function index(Request $request): View
    {
        // The Shop It Your Way tiles store price_min/price_max/shade, so accept
        // those names as well as the form's own. Renaming the stored strings
        // instead would break any tile an admin has already set up. The mapping
        // lives on ProductFilters because the home page has to run a hanger
        // through the same one to know whether it leads anywhere.
        $request->merge(ProductFilters::tileAliases($request));

        $picked = $this->shopAllPicks();

        $filters = ProductFilters::for(
            $request,
            fn () => $picked === []
                ? Product::query()->where('is_active', true)
                : Product::query()->where('is_active', true)->whereIn('products.id', $picked),
            ['action' => route('shop'), 'reset' => route('shop')],
        );

        return view('products.index', [
            'products' => $filters->results(),
            'filterPanel' => $filters->facets(),
        ]);
    }

    /**
     * The filter sidebar on its own, scoped to the whole shop.
     *
     * The header's Filters button reaches every page now, including the ones
     * with no listing behind them - the home page, the wishlist, a CMS page. A
     * panel costs five facet queries to build, so it is fetched here on the
     * first open instead of being rendered into every page load for a drawer
     * most visits never touch. Its form posts to /shop, which is where the
     * shopper is taken.
     *
     * Filters already in the URL are honoured: arriving from a "Shop It Your
     * Way" hanger and opening the drawer shows that hanger's picks ticked
     * rather than an empty panel.
     */
    public function filtersPanel(Request $request): View
    {
        $request->merge(ProductFilters::tileAliases($request));

        // Same bound as index(), deliberately. See shopAllPicks().
        $picked = $this->shopAllPicks();

        $filters = ProductFilters::for(
            $request,
            fn () => $picked === []
                ? Product::query()->where('is_active', true)
                : Product::query()->where('is_active', true)->whereIn('products.id', $picked),
            ['action' => route('shop'), 'reset' => route('shop')],
        );

        return view('partials.product-filters', [
            'filterPanel' => $filters->facets(),
            // The drawer is one tall scrolling column, same as the listing
            // pages' slide-over, so Apply pins to the bottom rather than sitting
            // below eight expanded sections.
            'kkStickyActions' => true,
        ]);
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
                ->with(['category', 'images'])
                ->withCount('images')
                ->inStockFirst()
                ->orderByDesc('images_count')
                ->inRandomOrder()
                ->take(8 - $relatedProducts->count())
                ->get();
            $relatedProducts = $relatedProducts->concat($fill);
        }

        // Both halves sort sold-out last on their own, but concat can still
        // drop an available top-up behind a sold-out recommendation, so the
        // joined row is re-sorted once. sortBy is stable, so the ranking the
        // recommender produced survives inside each block.
        $relatedProducts = $relatedProducts
            ->sortBy(fn ($p) => $p->isInStock() ? 0 : 1)
            ->values();

        // Ensure the relations the product card needs are loaded (cached models may be lean).
        $relatedProducts->loadMissing(['category', 'brand', 'images']);

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
            ->with(['images'])
            ->inStockFirst()
            ->inRandomOrder()
            ->take(3)
            ->get();

        if ($crossSellProducts->isEmpty()) {
            $crossSellProducts = Product::query()
                ->where('is_active', true)
                ->where('id', '!=', $product->id)
                ->where('category_id', $product->category_id)
                ->with(['images'])
                ->inStockFirst()
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
            ->with(['brand', 'images'])
            ->inStockFirst()
            ->inRandomOrder()
            ->take(4)
            ->get();

        if ($compareProducts->count() < 2) {
            $compareProducts = Product::query()
                ->where('is_active', true)
                ->where('id', '!=', $product->id)
                ->where('category_id', $product->category_id)
                ->with(['brand', 'images'])
                ->inStockFirst()
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

        $product->load(['brand', 'images', 'category', 'variants']);

        // The same list the product page renders and the same one /cart/add
        // validates against, so the quick-add popup on a listing card can only
        // ever offer a size and colour the cart will accept.
        $options = ProductOptions::for($product);
        $hex = $options->colourHex();

        // Per-size price and stock, so picking a size in the popup updates the
        // price the way it does on the product page instead of quoting the base
        // price and charging the row's.
        $sizes = $options->sizes->map(function (string $label) use ($options, $product) {
            $variant = $options->rows->firstWhere('id', $options->sizeVariants[$label] ?? null);

            return [
                'label' => $label,
                'variant_id' => $variant?->id,
                'price' => (float) ($variant?->price > 0 ? $variant->price : $product->price),
                'mrp' => (float) ($variant?->mrp > 0 ? $variant->mrp : $product->mrp),
                // A product with no size rows falls back to the free-text Size
                // attribute, and those sizes carry no stock of their own.
                'stock' => (int) ($variant ? $variant->stock_quantity : $product->stock_quantity),
            ];
        })->values();

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
            'sizes' => $sizes,
            'colours' => $options->colours
                ->map(fn (array $c) => ['name' => $c['name'], 'hex' => $hex[$c['name']] ?? null])
                ->values(),
        ]);
    }

    public function newArrivals(Request $request): View
    {
        // Same sidebar as the shop - these are a listing with an opening sort, not a
        // different kind of page, and offering no filters at all left a shopper on
        // /new-arrivals unable to narrow to their size.
        return $this->curated($request, 'products.new-arrivals', 'new-arrivals', 'newest', [
            'title' => 'No new arrivals yet',
            'text' => 'Check back soon for new products.',
        ]);
    }

    public function bestsellers(Request $request): View
    {
        return $this->curated($request, 'products.bestsellers', 'bestsellers', 'bestselling', [
            'title' => 'No bestsellers yet',
            'text' => 'Check back soon for popular products.',
        ]);
    }

    /**
     * A whole-catalogue listing that only differs by the ordering it opens on.
     *
     * Sold-out products still sort to the back whatever the shopper picks - it
     * stays the tie-break inside each block.
     */
    private function curated(Request $request, string $view, string $routeName, string $defaultSort, array $empty): View
    {
        // Hand-picked wins over computed, per page. Tick products into the
        // "New In" or "Bestsellers" collection on the product form and this
        // page shows those instead of the whole catalogue in date or sales
        // order; untick them all and it goes back to computing itself.
        $handles = ['new-arrivals' => 'new_in', 'bestsellers' => 'bestsellers'];
        $picked = isset($handles[$routeName])
            ? Category::pickedProductIds($handles[$routeName])
            : [];

        $filters = ProductFilters::for(
            $request,
            fn () => $picked === []
                ? Product::query()->where('is_active', true)
                : Product::query()->where('is_active', true)->whereIn('products.id', $picked),
            [
                'action' => route($routeName),
                'reset' => route($routeName),
                'default_sort' => $defaultSort,
            ],
        );

        $products = $filters
            ->sort($filters->query()->with(['category', 'brand', 'images'])->inStockFirst())
            ->paginate(24)
            ->withQueryString();

        return view($view, [
            'products' => $products,
            'filterPanel' => $filters->facets([
                'empty' => $empty + ['url' => route('shop'), 'label' => 'Browse all products'],
            ]),
        ]);
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
