<?php

namespace App\Models;

use App\Models\Concerns\TracksWarehouseStock;
use App\Support\ShopFilterCatalogue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ProductVariant extends Model
{
    use TracksWarehouseStock;

    protected static function booted(): void
    {
        static::saved(fn () => self::bumpFilterCache());
        static::deleted(fn () => self::bumpFilterCache());
    }

    /**
     * Filter chips are cached per category, so there is no single key to
     * forget. Bumping a shared version number retires every one of them at
     * once the moment the catalogue changes.
     */
    public static function bumpFilterCache(): void
    {
        // The derived filter rails hold a per-request memo of what they last
        // read, so retiring the cached arrays is only half of it: without this
        // an admin who saves a product and is redirected onto a page that draws
        // the rails would still be shown the answer from before the save.
        ShopFilterCatalogue::forget();

        $cache = Cache::getFacadeRoot();

        // A missing counter reads as 1, because that is what every reader
        // defaults to - so it is incremented like any other value rather than
        // being seeded AT the default. Writing 1 here was a bump that changed
        // nothing: `artisan optimize:clear` runs on every deploy and takes the
        // counter with it, so the first edit afterwards left every cached
        // answer still looking current and did not reach the storefront until
        // the entries aged out six hours later. Caught on production: hiding a
        // shade wrote its row and the rail went on offering it.
        $cache->forever('kk_filter_ver', ((int) $cache->get('kk_filter_ver', 1)) + 1);
    }

    public static function filterCacheVersion(): int
    {
        return (int) Cache::get('kk_filter_ver', 1);
    }

    /**
     * The size a shopper recognises.
     *
     * Variants created in the sizes editor store just the size ("M", "XL").
     * Older ones carry the whole variant name - "Block Print Kurti - Indigo - L"
     * - and the size is the last segment. Showing those raw turned the size
     * filter into a list of 115 product names.
     */
    public static function sizeLabel(?string $name): string
    {
        $name = trim((string) $name);

        if (str_contains($name, ' - ')) {
            $parts = explode(' - ', $name);
            $name = trim((string) end($parts));
        }

        return $name;
    }

    /**
     * Match a variant against a size label, covering both storage styles.
     */
    public function scopeWhereSizeIn($query, array $sizes)
    {
        return $query->where(function ($q) use ($sizes) {
            foreach ($sizes as $size) {
                $q->orWhere('name', $size)
                    ->orWhere('name', 'like', '% - '.$size);
            }
        });
    }

    /**
     * Sort order a shopper expects, rather than alphabetical, which puts
     * L before M and XL before XS.
     */
    public static function sizeRank(string $size): array
    {
        $order = ['XXS', 'XS', 'S', 'SM', 'M', 'MD', 'L', 'LG', 'XL', 'XXL', '2XL', '3XL', 'XXXL'];
        $upper = strtoupper($size);
        $index = array_search($upper, $order, true);

        // Numeric sizes (UK 7, 32, 40) sort after letters, by their number.
        if ($index === false && preg_match('/(\d+)/', $size, $m)) {
            return [1, (int) $m[1], $upper];
        }

        return $index === false ? [2, 0, $upper] : [0, $index, $upper];
    }

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'barcode',
        'mrp',
        'price',
        'stock_quantity',
        'attributes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'mrp' => 'decimal:2',
            'price' => 'decimal:2',
            'attributes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'variant_id');
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class, 'variant_id');
    }

    /** @see TracksWarehouseStock - a size's shelves are its own, never the product's. */
    public function warehouseStockKey(): array
    {
        return [$this->product_id, $this->id];
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->price ?? $this->product->price;
    }

    public function getEffectiveMrpAttribute(): float
    {
        return $this->mrp ?? $this->product->mrp;
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Virtual collection built from the JSON `attributes` column so views can
     * iterate `$variant->attributeValues` and read `$av->attribute->name` / `$av->value`.
     */
    public function getAttributeValuesAttribute()
    {
        $attrs = $this->getAttribute('attributes');
        if (! is_array($attrs)) {
            return collect();
        }

        return collect($attrs)->map(fn ($value, $name) => (object) [
            'attribute' => (object) ['name' => $name],
            'value' => $value,
        ])->values();
    }
}
