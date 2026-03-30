<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosHeldBill extends Model
{
    protected $fillable = [
        'store_id',
        'register_id',
        'staff_id',
        'customer_id',
        'items',
        'discount_data',
        'note',
        'subtotal',
        'tax',
        'total',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'discount_data' => 'array',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(PosRegister::class, 'register_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
