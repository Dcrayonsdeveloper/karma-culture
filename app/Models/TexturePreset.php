<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A reusable texture name an admin can pick on the product form, with an
 * optional swatch image - the fabric itself, photographed. The name is what a
 * product stores; the image is library-only, and the home rail wears it on the
 * shirt for that texture instead of a flat colour.
 */
class TexturePreset extends Model
{
    protected $fillable = [
        'name',
        'image_path',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The rails are derived and cached, and the texture rail now reads this
        // table for its swatches - so a swatch uploaded here has to retire that
        // cache the way a product save does, or it would not reach the home
        // page until the entries aged out six hours later.
        static::saved(fn () => ProductVariant::bumpFilterCache());
        static::deleted(fn () => ProductVariant::bumpFilterCache());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Browser-ready URL for the swatch, or null when none is set.
     *
     * image_path holds a storage-relative path (admin upload), but a seeded or
     * hand-edited row may carry a full URL or an absolute path - resolve all
     * three, the way {@see Brand::getLogoSrcAttribute()} does.
     */
    public function getImageSrcAttribute(): ?string
    {
        $path = $this->image_path;

        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return asset_v(ltrim($path, '/'));
        }

        return asset_v('storage/'.$path);
    }
}
