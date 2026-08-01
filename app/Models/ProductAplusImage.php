<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAplusImage extends Model
{
    protected $fillable = [
        'product_id',
        'image_path',
        'alt_text',
        'width',
        'height',
        'sort_order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Browser-usable URL (handles /storage, absolute URL, or a bare storage path). */
    public function getImageUrlAttribute(): string
    {
        $path = $this->image_path;
        if (! $path) {
            return '';
        }
        if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return $path;
        }

        return asset('storage/'.$path);
    }
}
