<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'coupon_id',
        'subtotal',
        'discount',
        'tax',
        'shipping',
        'total',
        'metadata',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping' => 'decimal:2',
            'total' => 'decimal:2',
            'metadata' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function recalculate(bool $skipAutoApply = false): void
    {
        $this->load(['items.product', 'coupon']);
        $subtotal = $this->items->sum('total');
        $discount = 0;

        if ($this->coupon && $this->coupon->isValid()) {
            $discount = $this->coupon->calculateDiscount($subtotal, $this->items);
        }

        // Auto-apply: if no manual coupon, find the best auto-apply coupon
        if (!$skipAutoApply && !$this->coupon_id && $subtotal > 0) {
            $autoCoupon = Coupon::findBestAutoApply($this);
            if ($autoCoupon) {
                $this->coupon_id = $autoCoupon->id;
                $discount = $autoCoupon->calculateDiscount($subtotal, $this->items);
            }
        }

        // If current coupon no longer gives a discount, remove it
        if ($this->coupon_id && $discount == 0 && $this->coupon && $this->coupon->type !== 'free_shipping') {
            $this->coupon_id = null;
        }

        $tax = $this->items->sum(function ($item) {
            return $item->product->is_taxable
                ? ($item->total * $item->product->tax_rate / 100)
                : 0;
        });

        $this->update([
            'coupon_id' => $this->coupon_id,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $subtotal - $discount + $tax + $this->shipping,
        ]);
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function getItemCount(): int
    {
        return $this->items->sum('quantity');
    }
}
