<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A reusable size label (with optional default measurements) an admin can
 * pick on the product form. Picking one adds a size row the admin then fills
 * in with that product's price, MRP, stock and SKU - only the name and any
 * default measurements come from the preset.
 */
class SizePreset extends Model
{
    protected $fillable = [
        'name',
        'measurements',
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
        // The size rail is derived and cached for six hours, and it reads this
        // table to decide which sizes it offers and in what order - so an edit
        // here has to retire that cache the way a product save does, exactly as
        // TexturePreset already does for its swatches. Without this, a deleted
        // size stayed on the rail: nothing bumped the version, so the shop went
        // on serving the answer it had cached while the size still existed.
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
}
