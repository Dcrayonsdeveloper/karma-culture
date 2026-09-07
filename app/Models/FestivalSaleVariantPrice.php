<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one size cost before a festival sale reached it.
 *
 * Variants are snapshotted separately from their product because they are
 * priced separately: Admin\ProductController writes a concrete price onto every
 * variant row on save, and products/show.blade.php reads that figure rather
 * than the product's. Discounting only the parent would leave the product page
 * quoting the old price while the cart charged the new one.
 */
class FestivalSaleVariantPrice extends Model
{
    protected $fillable = [
        'festival_sale_id',
        'product_variant_id',
        'original_price',
        'original_mrp',
        'sale_price',
    ];

    protected function casts(): array
    {
        return [
            'original_price' => 'decimal:2',
            'original_mrp' => 'decimal:2',
            'sale_price' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(FestivalSale::class, 'festival_sale_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
