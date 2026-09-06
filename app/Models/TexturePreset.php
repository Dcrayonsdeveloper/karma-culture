<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A reusable texture name an admin can pick on the product form. A texture
 * carries no swatch - it is a name on its own - so this holds only that.
 */
class TexturePreset extends Model
{
    protected $fillable = [
        'name',
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
