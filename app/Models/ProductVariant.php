<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected static function booted(): void
    {
        // Size chips are cached for ten minutes and are built from variants,
        // so adding or removing a size must drop that cache immediately.
        $forget = fn () => \Illuminate\Support\Facades\Cache::forget('kk_filter_sizes_v2');

        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * The size a shopper recognises.
     *
     * Variants created in the sizes editor store just the size ("M", "XL").
     * Older ones carry the whole variant name — "Block Print Kurti - Indigo - L"
     * — and the size is the last segment. Showing those raw turned the size
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
                  ->orWhere('name', 'like', '% - ' . $size);
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
        if (!is_array($attrs)) {
            return collect();
        }

        return collect($attrs)->map(fn ($value, $name) => (object) [
            'attribute' => (object) ['name' => $name],
            'value' => $value,
        ])->values();
    }
}
