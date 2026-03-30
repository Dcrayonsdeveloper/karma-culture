<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLocation extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'address',
        'city',
        'state',
        'postal_code',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class, 'location_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'location_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
