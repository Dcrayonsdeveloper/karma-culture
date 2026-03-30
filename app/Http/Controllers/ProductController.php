<?php

namespace App\Http\Controllers;

use App\Models\BackInStockSubscription;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductQuestion;
use App\Models\ProductView;
use App\Services\ReviewSchemaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with(['category', 'brand', 'primaryImage']);

        // Category filter
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Subcategory filter
        if ($request->filled('subcategory')) {
            $subSlugs = (array) $request->subcategory;
            $subIds = Category::whereIn('slug', $subSlugs)->pluck('id');
            if ($subIds->isNotEmpty()) {
                $query->whereIn('category_id', $subIds);
            }
        }

        // Price filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Rating filter
        if ($request->filled('rating')) {
            $query->where('rating', '>=', $request->rating);
        }

        // In stock filter
        if ($request->boolean('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }

        // On sale filter (price less than mrp)
        if ($request->boolean('on_sale')) {
            $query->whereNotNull('mrp')->whereColumn('price', '<', 'mrp');
        }

        // Sorting
        $sortBy = $request->get('sort', 'newest');
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

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category',
            'brand',
            'seller',
            'images',
            'variants.attributeValues.attribute',
            'reviews' => fn ($q) => $q->where('is_approved', true)->latest()->take(10),
            'reviews.user',
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

        // Related products
        $relatedProducts = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->where(function ($query) use ($product) {
                $query->where('category_id', $product->category_id)
                      ->orWhere('brand_id', $product->brand_id);
            })
            ->with(['category', 'primaryImage'])
            ->inRandomOrder()
            ->take(8)
            ->get();

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

        // Frequently bought together (same category, limited to 3)
        $crossSellProducts = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->with(['primaryImage'])
            ->inRandomOrder()
            ->take(3)
            ->get();

        // Compare with similar items (4 products from same category with brand + rating)
        $compareProducts = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->with(['brand', 'primaryImage'])
            ->inRandomOrder()
            ->take(4)
            ->get();

        // Active coupons applicable to this product
        $activeCoupons = Coupon::where('is_active', true)
            ->where(function ($q) { $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()); })
            ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()); })
            ->whereRaw('(usage_limit IS NULL OR times_used < usage_limit)')
            ->orderByDesc('value')
            ->take(3)
            ->get();

        return view('products.show', compact('product', 'relatedProducts', 'crossSellProducts', 'compareProducts', 'activeCoupons', 'breadcrumbs', 'productSchema', 'faqSchema'));
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
        $request->validate([
            'question' => 'required|string|min:10|max:1000',
            'guest_name' => 'nullable|string|max:100',
            'guest_email' => 'nullable|email|max:255',
        ]);

        ProductQuestion::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'question' => $request->question,
        ]);

        return response()->json(['message' => 'Question submitted successfully!']);
    }

    public function notifyBackInStock(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        BackInStockSubscription::updateOrCreate(
            [
                'product_id' => $product->id,
                'email' => $request->email,
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
