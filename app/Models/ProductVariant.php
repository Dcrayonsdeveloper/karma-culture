<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
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
}
