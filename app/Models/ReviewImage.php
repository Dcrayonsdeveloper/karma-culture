<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewImage extends Model
{
    protected $fillable = [
        'review_id',
        'media_type',
        'url',
        'thumbnail_url',
        'alt_text',
        'position',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function getIsVideoAttribute(): bool
    {
        return $this->media_type === 'video';
    }

    /** Resolve a stored path (or absolute URL) to a browser-usable URL. */
    public function getDisplayUrlAttribute(): string
    {
        return $this->resolveUrl($this->url);
    }

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

        return asset_v('storage/'.$path);
    }
}
