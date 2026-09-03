<?php

namespace App\Models;

use App\Support\ShippingCharge;
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

    /**
     * Bring every line back to the price it would be added at today.
     *
     * Only flash-sale movement can change a price under the customer, so this
     * writes nothing when the figure is unchanged.
     */
    private function repriceItems(): void
    {
        foreach ($this->items as $item) {
            $product = $item->product;

            if (! $product) {
                continue;
            }

            $base = $item->variant_id
                ? ($product->variants->firstWhere('id', $item->variant_id)?->price ?? $product->price)
                : $product->price;

            $price = (float) $base;

            if ($flash = $product->flashSalePrice()) {
                $price = min($price, $flash);
            }

            if (abs($price - (float) $item->price) > 0.001) {
                // Quietly: CartItem::saved() calls recalculate(), and a normal
                // update here would re-enter this method until memory ran out.
                $item->updateQuietly([
                    'price' => $price,
                    'total' => $price * $item->quantity,
                ]);
            }
        }

        $this->load('items.product');
    }

    public function recalculate(bool $skipAutoApply = false): void
    {
        $this->load(['items.product.variants', 'coupon']);

        // A cart line stores the price it was added at. Without this, a flash
        // sale starting after the item went in never reaches the customer, and
        // one that has ended keeps discounting them forever.
        $this->repriceItems();

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

        // Delivery, from Settings -> Shipping. `shipping` was only ever read
        // here and never written, so it sat at the column default of 0 and the
        // total below could not include a charge however the admin configured
        // one. Set on the cart itself so every reader - the drawer, the cart
        // page, checkout and the order - agrees without each recomputing it.
        $this->setAttribute('subtotal', $subtotal);
        $this->setAttribute('discount', $discount);
        $shipping = ShippingCharge::for($this);

        $this->update([
            'coupon_id' => $this->coupon_id,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $subtotal - $discount + $tax + $shipping,
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
