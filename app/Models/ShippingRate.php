<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $fillable = [
        'zone_id',
        'name',
        'type',
        'rate',
        'min_order',
        'min_weight',
        'max_weight',
        'estimated_days_min',
        'estimated_days_max',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'min_order' => 'decimal:2',
            'min_weight' => 'decimal:2',
            'max_weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }
}
