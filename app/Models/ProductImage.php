<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'variant_id',
        'media_type',
        'url',
        'thumbnail_url',
        'alt_text',
        'position',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function getIsVideoAttribute(): bool
    {
        return $this->media_type === 'video';
    }

    /** Browser-usable URL for the media (handles /storage, absolute URL, or bare path). */
    public function getDisplayUrlAttribute(): string
    {
        return $this->resolveUrl($this->url);
    }

    /** Poster/thumbnail URL for videos, if set. */
    public function getDisplayThumbnailAttribute(): ?string
    {
        return $this->thumbnail_url ? $this->resolveUrl($this->thumbnail_url) : null;
    }

    protected function resolveUrl(?string $path): string
    {
        if (! $path) {
            return '';
        }
        if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return $path;
        }

        return asset('storage/'.$path);
    }
}
