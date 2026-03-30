<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosRegister extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'device_id',
        'status',
        'settings',
        'last_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'last_sync_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'register_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
