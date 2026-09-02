<?php

namespace App\Models;

use App\Models\Concerns\TracksWarehouseStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasSlug, Searchable, SoftDeletes, TracksWarehouseStock;

    protected $fillable = [
        'uuid',
        'seller_id',
        'brand_id',
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'sku',
        'barcode',
        'mrp',
        'price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'stock_status',
        'weight',
        'length',
        'width',
        'height',
        'model_glb_path',
        'model_usdz_path',
        'weight_unit',
        'dimension_unit',
        'is_active',
        'is_featured',
        'is_taxable',
        'tax_rate',
        'hsn_code',
        'rating',
        'review_count',
        'view_count',
        'sales_count',
        'wishlist_count',
        'seo_data',
        'attributes',
        'specifications',
        'feature_highlights',
        'status',
        'rejection_reason',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'mrp' => 'decimal:2',
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'rating' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_taxable' => 'boolean',
            'seo_data' => 'array',
            'attributes' => 'array',
            'specifications' => 'array',
            'feature_highlights' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    protected static function booted(): void
    {
        // Colour swatches are cached and read from the product's Colours list.
        $forgetColours = fn () => \App\Models\ProductVariant::bumpFilterCache();

        static::saved($forgetColours);
        static::deleted($forgetColours);
        static::creating(function ($product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }
        });
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'rating' => $this->rating,
            'sales_count' => $this->sales_count,
        ];
    }

    // Relationships
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_primary', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function aplusImages(): HasMany
    {
        return $this->hasMany(ProductAplusImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_tag_pivot', 'product_id', 'tag_id');
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'related_products', 'product_id', 'related_product_id')
            ->withPivot('type', 'position');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class);
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    /** @see TracksWarehouseStock - stock_quantity here is the product's own, not a size's. */
    public function warehouseStockKey(): array
    {
        return [$this->id, null];
    }

    /**
     * What each warehouse holds of this product, keyed by location id.
     *
     * The Adjust Stock dialog sets stock at one location, so the figure it
     * shows has to be that location's: reading the product-wide total and
     * typing it back into "Set stock to" moved the total up by the difference.
     */
    public function heldByLocation(): array
    {
        return $this->inventoryStocks
            ->whereNull('variant_id')
            ->mapWithKeys(fn ($line) => [(string) $line->location_id => (int) $line->quantity])
            ->all();
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(ProductView::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    /**
     * The price this product sells at right now because of a running flash
     * sale, or null when none applies.
     *
     * A sale that lists a product but charges the normal price is worse than
     * no sale at all, so cart, checkout and the product page all read this.
     */
    public function flashSalePrice(): ?float
    {
        $sale = \App\Models\FlashSale::active()
            ->whereHas('products', fn ($q) => $q->where('products.id', $this->id))
            ->with(['products' => fn ($q) => $q->where('products.id', $this->id)])
            ->first();

        $pivot = $sale?->products->first()?->pivot;

        if (! $pivot || $pivot->sale_price === null) {
            return null;
        }

        // A limit that has been reached ends the discount for later buyers.
        if ($pivot->stock_limit !== null && (int) $pivot->sold_count >= (int) $pivot->stock_limit) {
            return null;
        }

        $price = (float) $pivot->sale_price;

        // Never let a misconfigured sale raise the price.
        return $price > 0 && $price < (float) $this->price ? $price : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_status', 'in_stock');
    }

    // Accessors
    public function getDiscountPercentageAttribute(): int
    {
        if ($this->mrp <= 0 || $this->price >= $this->mrp) {
            return 0;
        }

        return (int) round((($this->mrp - $this->price) / $this->mrp) * 100);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->price < $this->mrp;
    }

    public function getPrimaryImageUrlAttribute(): string
    {
        // Never use a video as the thumbnail - pick the primary image, else the
        // first non-video media, else any first media.
        $notVideo = fn ($i) => ($i->media_type ?? 'image') !== 'video';
        $primary = $this->images->firstWhere('is_primary', true);
        $img = ($primary && $notVideo($primary)) ? $primary
            : ($this->images->first($notVideo) ?? $this->images->first());
        $url = $img?->url;

        if ($url) {
            // If it's a relative path (stored in storage), prefix with /storage/
            if ($url && ! str_starts_with($url, 'http') && ! str_starts_with($url, '/')) {
                return asset_v('storage/'.$url);
            }

            return $url;
        }

        return asset_v('images/no-product-image.svg');
    }

    /**
     * Public URL for the .glb 3D model (used by <model-viewer> and Android Scene Viewer).
     * Accepts: full http URL, absolute path ("/foo.glb"), or storage-relative path ("models/foo.glb").
     */
    public function getModelGlbUrlAttribute(): ?string
    {
        return $this->resolveModelUrl($this->model_glb_path);
    }

    /**
     * Public URL for the .usdz 3D model (used by iOS AR QuickLook).
     */
    public function getModelUsdzUrlAttribute(): ?string
    {
        return $this->resolveModelUrl($this->model_usdz_path);
    }

    public function hasArModel(): bool
    {
        return ! empty($this->model_glb_path);
    }

    protected function resolveModelUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, '/')) {
            return asset_v(ltrim($path, '/'));
        }

        return asset_v('storage/'.$path);
    }

    // Helper methods
    public function isInStock(): bool
    {
        return $this->stock_status === 'in_stock' && $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function incrementSalesCount(int $quantity = 1): void
    {
        $this->increment('sales_count', $quantity);
    }

    public function updateRating(): void
    {
        $reviews = $this->reviews()->where('is_approved', true);
        $this->update([
            'rating' => $reviews->avg('rating') ?? 0,
            'review_count' => $reviews->count(),
        ]);
    }
}
