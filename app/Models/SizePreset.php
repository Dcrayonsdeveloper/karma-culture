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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
