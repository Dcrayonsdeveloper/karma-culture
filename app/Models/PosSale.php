<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    protected $fillable = [
        'sale_number',
        'store_id',
        'register_id',
        'staff_id',
        'customer_id',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid_amount',
        'change_amount',
        'payment_method',
        'payment_details',
        'status',
        'receipt_data',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'payment_details' => 'array',
            'receipt_data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($sale) {
            if (empty($sale->sale_number)) {
                $sale->sale_number = 'POS-' . now()->format('Ymd') . str_pad(static::whereDate('created_at', today())->count() + 1, 5, '0', STR_PAD_LEFT);
            }
        });
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

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class, 'pos_sale_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PosReturn::class, 'pos_sale_id');
    }
}
