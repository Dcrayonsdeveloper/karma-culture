# AI Context Prompt - Master Reference

## [AI-REF] Comprehensive Context for AI Code Generation

Use this document as context when generating code for this project.

---

## Project Summary

**ShopVerse** is an enterprise-grade multi-vendor e-commerce platform built with:
- **Backend**: Laravel 11+ (PHP 8.3+)
- **Frontend**: Blade + Alpine.js + Livewire (SSR, no Inertia)
- **Database**: MySQL 8.0+ with Redis cache
- **Search**: Meilisearch
- **Styling**: Tailwind CSS 3.4+

---

## Core Constraints

### MUST Follow
1. **Server-Side Rendering**: Use Blade for all pages, no SPA
2. **No Inline CSS**: Use only Tailwind classes from config
3. **SVG Icons**: 1px stroke, no fills, use `currentColor`
4. **Ultra-Light Borders**: Only `border-neutral-200`
5. **Mobile-First**: Design for mobile, enhance for desktop
6. **PHP 8.3 Features**: Use typed properties, enums, readonly classes
7. **Action Pattern**: Single-purpose action classes for business logic
8. **DTO Pattern**: Use DTOs for data transfer between layers

### MUST NOT Do
1. ❌ Use inline styles (`style="..."`)
2. ❌ Use dark/heavy borders or shadows
3. ❌ Use Inertia.js or Vue/React for frontend
4. ❌ Store sensitive data unencrypted
5. ❌ Skip input validation
6. ❌ Use raw SQL queries (use Eloquent)
7. ❌ Hardcode configuration values

---

## Code Generation Templates

### Controller Template

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Product\CreateProduct;
use App\DTOs\Product\CreateProductDTO;
use App\Http\Requests\Api\CreateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function store(
        CreateProductRequest $request,
        CreateProduct $action
    ): JsonResponse {
        $product = $action->execute(
            CreateProductDTO::fromRequest($request)
        );

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ], 201);
    }
}
```

### Action Template

```php
<?php

namespace App\Actions\Product;

use App\DTOs\Product\CreateProductDTO;
use App\Models\Product;
use App\Services\Search\SearchIndexService;
use Illuminate\Support\Facades\DB;

readonly class CreateProduct
{
    public function __construct(
        private SearchIndexService $searchIndex,
    ) {}

    public function execute(CreateProductDTO $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'seller_id' => $data->sellerId,
                'category_id' => $data->categoryId,
                'name' => $data->name,
                'slug' => Str::slug($data->name),
                'price' => $data->price,
                'mrp' => $data->mrp,
                'description' => $data->description,
                'sku' => $data->sku,
                'status' => 'pending',
            ]);

            // Handle images
            foreach ($data->images as $index => $image) {
                $product->images()->create([
                    'url' => $image,
                    'position' => $index,
                    'is_primary' => $index === 0,
                ]);
            }

            // Index for search
            $this->searchIndex->index($product);

            return $product->load(['images', 'category', 'seller']);
        });
    }
}
```

### DTO Template

```php
<?php

namespace App\DTOs\Product;

use App\Http\Requests\Api\CreateProductRequest;

readonly class CreateProductDTO
{
    public function __construct(
        public int $sellerId,
        public int $categoryId,
        public string $name,
        public float $price,
        public float $mrp,
        public string $description,
        public string $sku,
        public ?int $brandId = null,
        public array $images = [],
        public array $attributes = [],
    ) {}

    public static function fromRequest(CreateProductRequest $request): self
    {
        return new self(
            sellerId: auth()->user()->seller->id,
            categoryId: $request->validated('category_id'),
            name: $request->validated('name'),
            price: $request->validated('price'),
            mrp: $request->validated('mrp'),
            description: $request->validated('description'),
            sku: $request->validated('sku'),
            brandId: $request->validated('brand_id'),
            images: $request->validated('images', []),
            attributes: $request->validated('attributes', []),
        );
    }
}
```

### Model Template

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasUuid, HasSlug, Searchable;

    protected $fillable = [
        'seller_id',
        'category_id',
        'brand_id',
        'name',
        'slug',
        'price',
        'mrp',
        'description',
        'sku',
        'stock_quantity',
        'is_active',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'is_active' => 'boolean',
        'attributes' => 'array',
    ];

    // Relationships
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('status', 'approved');
    }

    // Accessors
    public function getDiscountPercentageAttribute(): float
    {
        if ($this->mrp <= 0) return 0;
        return round((($this->mrp - $this->price) / $this->mrp) * 100, 1);
    }
}
```

### Service Template

```php
<?php

namespace App\Services\Search;

use App\Models\Product;
use Illuminate\Support\Collection;
use Meilisearch\Client;

class ProductSearchService
{
    public function __construct(
        private Client $client,
    ) {}

    public function search(
        string $query,
        array $filters = [],
        int $page = 1,
        int $perPage = 20
    ): SearchResult {
        $options = [
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage,
            'filter' => $this->buildFilters($filters),
            'facets' => ['category_id', 'brand_id', 'price'],
        ];

        $results = $this->client
            ->index('products')
            ->search($query, $options);

        return new SearchResult(
            products: $this->hydrateProducts($results['hits']),
            total: $results['estimatedTotalHits'],
            facets: $results['facetDistribution'] ?? [],
        );
    }

    private function buildFilters(array $filters): array
    {
        $filterStrings = ['is_active = true'];

        if (!empty($filters['category_id'])) {
            $filterStrings[] = "category_id = {$filters['category_id']}";
        }

        if (!empty($filters['min_price'])) {
            $filterStrings[] = "price >= {$filters['min_price']}";
        }

        if (!empty($filters['max_price'])) {
            $filterStrings[] = "price <= {$filters['max_price']}";
        }

        return $filterStrings;
    }
}
```

### Blade Component Template

```php
{{-- resources/views/components/product/card.blade.php --}}
@props([
    'product',
    'showWishlist' => true,
])

<article {{ $attributes->class([
    'group bg-white border border-neutral-100 rounded-lg overflow-hidden',
    'hover:shadow-md transition-shadow duration-200',
]) }}>
    {{-- Image --}}
    <div class="relative aspect-square overflow-hidden bg-neutral-50">
        <img
            src="{{ $product->primaryImage?->url ?? asset('images/placeholder.webp') }}"
            alt="{{ $product->name }}"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            loading="lazy"
        >

        @if($showWishlist)
            <button
                x-data="{ wishlisted: false }"
                @click="wishlisted = !wishlisted"
                class="absolute top-2 right-2 p-1.5 bg-white/80 backdrop-blur-sm rounded-full hover:bg-white transition-colors"
            >
                <x-icons.heart
                    class="w-5 h-5"
                    :class="wishlisted ? 'fill-error-500 text-error-500' : 'text-neutral-400'"
                />
            </button>
        @endif

        @if($product->discount_percentage > 0)
            <span class="absolute top-2 left-2 px-2 py-0.5 text-xs font-medium text-white bg-deal rounded">
                -{{ $product->discount_percentage }}%
            </span>
        @endif
    </div>

    {{-- Content --}}
    <div class="p-3 space-y-2">
        @if($product->brand)
            <p class="text-xs text-neutral-500 uppercase tracking-wide">
                {{ $product->brand->name }}
            </p>
        @endif

        <h3 class="text-sm font-medium text-neutral-900 line-clamp-2">
            <a href="{{ route('products.show', $product) }}" class="hover:text-primary-500">
                {{ $product->name }}
            </a>
        </h3>

        <x-product.rating :rating="$product->rating" :count="$product->review_count" />

        <div class="flex items-baseline gap-2">
            <span class="text-lg font-bold text-neutral-900">
                ₹{{ number_format($product->price) }}
            </span>
            @if($product->is_on_sale)
                <span class="text-sm text-neutral-400 line-through">
                    ₹{{ number_format($product->mrp) }}
                </span>
            @endif
        </div>
    </div>
</article>
```

### Livewire Component Template

```php
<?php

namespace App\Livewire\Cart;

use App\Actions\Cart\AddToCart;
use App\Models\Product;
use Livewire\Component;

class AddToCartButton extends Component
{
    public Product $product;
    public int $quantity = 1;
    public bool $loading = false;

    public function add(AddToCart $action): void
    {
        $this->loading = true;

        try {
            $action->execute($this->product, $this->quantity);
            $this->dispatch('cart-updated');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Added to cart!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.cart.add-to-cart-button');
    }
}
```

```html
{{-- resources/views/livewire/cart/add-to-cart-button.blade.php --}}
<div class="flex items-center gap-2">
    <div class="flex items-center border border-neutral-200 rounded-md">
        <button
            wire:click="$set('quantity', Math.max(1, quantity - 1))"
            class="p-2 text-neutral-500 hover:bg-neutral-50"
        >
            <x-icons.minus class="w-4 h-4" />
        </button>
        <span class="px-3 text-sm font-medium">{{ $quantity }}</span>
        <button
            wire:click="$set('quantity', quantity + 1)"
            class="p-2 text-neutral-500 hover:bg-neutral-50"
        >
            <x-icons.plus class="w-4 h-4" />
        </button>
    </div>

    <button
        wire:click="add"
        wire:loading.attr="disabled"
        @class([
            'flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-500 rounded-md',
            'hover:bg-primary-600 transition-colors',
            'disabled:opacity-50 disabled:cursor-not-allowed',
        ])
    >
        <span wire:loading.remove>Add to Cart</span>
        <span wire:loading class="flex items-center justify-center">
            <x-icons.spinner class="w-5 h-5 animate-spin" />
        </span>
    </button>
</div>
```

---

## API Response Formats

### Success Response

```json
{
    "success": true,
    "data": { ... },
    "meta": {
        "request_id": "req_abc123"
    }
}
```

### Error Response

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid.",
        "details": { ... }
    }
}
```

### Paginated Response

```json
{
    "success": true,
    "data": [...],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 100
    },
    "links": {
        "next": "/api/v1/products?page=2"
    }
}
```

---

## Database Conventions

### Table Naming
- Plural snake_case: `users`, `order_items`, `product_variants`
- Pivot tables: alphabetical order: `product_tag`

### Column Naming
- Foreign keys: `{table}_id` (e.g., `user_id`)
- Boolean: `is_*` or `has_*` (e.g., `is_active`)
- Timestamps: `*_at` (e.g., `verified_at`)
- JSON: descriptive name (e.g., `metadata`, `settings`)

### Standard Columns
```php
$table->id();
$table->uuid('uuid')->unique();  // For public exposure
$table->timestamps();
$table->softDeletes();          // Where applicable
```

---

## Testing Conventions

### Test Naming
```php
public function test_user_can_login_with_valid_credentials(): void
public function test_checkout_fails_with_empty_cart(): void
public function test_search_returns_matching_products(): void
```

### Test Structure
```php
// Arrange
$user = User::factory()->create();
$product = Product::factory()->create();

// Act
$response = $this->actingAs($user)
    ->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

// Assert
$response->assertCreated();
$this->assertDatabaseHas('cart_items', [
    'product_id' => $product->id,
]);
```

---

## Common Patterns

### Query Optimization

```php
// Eager load relationships
$products = Product::with(['category', 'brand', 'images'])
    ->active()
    ->paginate(20);

// Prevent N+1
$orders = Order::with([
    'items.product.images',
    'user',
    'shipments',
])->get();

// Chunking for large datasets
Product::chunk(1000, function ($products) {
    foreach ($products as $product) {
        // Process
    }
});
```

### Cache Usage

```php
// Remember pattern
$categories = Cache::remember('categories:tree', 3600, fn() =>
    Category::with('children')->whereNull('parent_id')->get()
);

// Tags for invalidation
Cache::tags(['products', 'search'])->flush();

// Cache key conventions
"products:list:{$categoryId}:{$page}"
"users:cart:{$userId}"
```

### Event Dispatching

```php
// In action
ProductCreated::dispatch($product);

// Listener
class UpdateSearchIndex
{
    public function handle(ProductCreated $event): void
    {
        SearchIndexJob::dispatch($event->product);
    }
}
```

---

## File Organization

```
app/
├── Actions/          # Single-purpose business actions
├── DTOs/             # Data Transfer Objects
├── Enums/            # PHP 8.1 Enums
├── Events/           # Domain events
├── Exceptions/       # Custom exceptions
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/     # Form request validation
│   └── Resources/    # API resources
├── Jobs/             # Queue jobs
├── Livewire/         # Livewire components
├── Models/           # Eloquent models
├── Observers/        # Model observers
├── Policies/         # Authorization
├── Services/         # Business logic services
└── View/Components/  # Blade components
```

---

## Quick Reference

| What | How |
|------|-----|
| Create product | `CreateProduct` action |
| Search products | `ProductSearchService::search()` |
| Add to cart | `AddToCart` action via Livewire |
| Process checkout | `ProcessCheckout` action |
| Send notification | `$user->notify(new OrderConfirmed($order))` |
| Cache data | `Cache::remember($key, $ttl, $callback)` |
| Validate request | Form Request class |
| API response | JSON with `success`, `data`, `meta` |

