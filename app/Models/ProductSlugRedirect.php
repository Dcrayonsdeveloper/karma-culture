<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One retired product URL and the product it should now lead to.
 *
 * @see \App\Console\Commands\RefreshProductSlugs for what writes these rows.
 */
class ProductSlugRedirect extends Model
{
    protected $fillable = [
        'old_slug',
        'product_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
