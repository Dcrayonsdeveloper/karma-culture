<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
        'total',
        'attributes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'total' => 'decimal:2',
            'attributes' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function ($item) {
            // Always re-read price from product to prevent tampering
            if ($item->product_id) {
                $item->price = $item->variant_id
                    ? ($item->variant?->price ?? $item->product?->price ?? $item->price)
                    : ($item->product?->price ?? $item->price);
            }
            $item->total = $item->price * $item->quantity;
        });

        static::saved(function ($item) {
            $item->cart->recalculate();
        });

        static::deleted(function ($item) {
            $item->cart->recalculate();
        });
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function updateQuantity(int $quantity): void
    {
        $this->update(['quantity' => $quantity]);
    }
}
