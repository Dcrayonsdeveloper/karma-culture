<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A reusable colour (name + swatch) an admin can pick on the product form
 * instead of typing it out each time. Picking one adds an ordinary colour
 * row to the product; the product still stores its own copy, so editing a
 * preset later does not rewrite products already saved.
 */
class ColourPreset extends Model
{
    protected $fillable = [
        'name',
        'hex',
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
