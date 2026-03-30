<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'seller_id',
        'code',
        'name',
        'description',
        'type',
        'value',
        'max_discount',
        'min_order_amount',
        'usage_limit',
        'usage_per_user',
        'times_used',
        'is_active',
        'auto_apply',
        'starts_at',
        'expires_at',
        'conditions',
        'applicable_products',
        'applicable_categories',
        'applicable_users',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'auto_apply' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'conditions' => 'array',
            'applicable_products' => 'array',
            'applicable_categories' => 'array',
            'applicable_users' => 'array',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function canBeUsedBy(User $user): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Check if user-specific
        if (!empty($this->applicable_users) && !in_array($user->id, $this->applicable_users)) {
            return false;
        }

        // Check usage per user limit
        $userUsageCount = $this->usages()->where('user_id', $user->id)->count();
        if ($userUsageCount >= $this->usage_per_user) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $subtotal, $cartItems = null): float
    {
        if ($this->type === 'buy_x_get_y') {
            return $this->calculateBuyXGetYDiscount($cartItems);
        }

        if ($subtotal < $this->min_order_amount) {
            return 0;
        }

        $discount = match ($this->type) {
            'percentage' => $subtotal * ($this->value / 100),
            'fixed' => (float) $this->value,
            'free_shipping' => 0, // Handled separately
            default => 0,
        };

        if ($this->max_discount && $discount > $this->max_discount) {
            $discount = (float) $this->max_discount;
        }

        return min($discount, $subtotal);
    }

    protected function calculateBuyXGetYDiscount($cartItems): float
    {
        if (!$cartItems || $cartItems->isEmpty()) {
            return 0;
        }

        $buyQty = (int) ($this->conditions['buy_qty'] ?? 0);
        $getQty = (int) ($this->conditions['get_qty'] ?? 0);

        if ($buyQty <= 0 || $getQty <= 0) {
            return 0;
        }

        $applicableProducts = $this->applicable_products ?? [];
        $applicableCategories = $this->applicable_categories ?? [];

        // Build a flat list of qualifying unit prices
        $unitPrices = [];
        foreach ($cartItems as $item) {
            $qualifies = true;

            if (!empty($applicableProducts) && !in_array($item->product_id, $applicableProducts)) {
                $qualifies = false;
            }

            if ($qualifies && !empty($applicableCategories)) {
                $product = $item->product;
                if (!$product || !in_array($product->category_id, $applicableCategories)) {
                    $qualifies = false;
                }
            }

            if ($qualifies) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    $unitPrices[] = (float) $item->price;
                }
            }
        }

        $totalQty = count($unitPrices);
        $setSize = $buyQty + $getQty;

        if ($totalQty < $setSize) {
            return 0;
        }

        $sets = (int) floor($totalQty / $setSize);
        $freeCount = $sets * $getQty;

        // Sort ascending — cheapest items are the "free" ones
        sort($unitPrices);

        $discount = 0;
        for ($i = 0; $i < $freeCount; $i++) {
            $discount += $unitPrices[$i] * ($this->value / 100);
        }

        if ($this->max_discount && $discount > $this->max_discount) {
            $discount = (float) $this->max_discount;
        }

        return $discount;
    }

    /**
     * Find the best auto-apply coupon for a cart.
     */
    public static function findBestAutoApply(Cart $cart): ?self
    {
        $coupons = static::where('auto_apply', true)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->whereRaw('(usage_limit IS NULL OR times_used < usage_limit)')
            ->get();

        if ($coupons->isEmpty()) {
            return null;
        }

        $bestCoupon = null;
        $bestDiscount = 0;

        foreach ($coupons as $coupon) {
            // Check min order amount (not for BOGO)
            if ($coupon->type !== 'buy_x_get_y' && $coupon->min_order_amount && $cart->subtotal < $coupon->min_order_amount) {
                continue;
            }

            // Check applicable products
            if (!empty($coupon->applicable_products)) {
                $cartProductIds = $cart->items->pluck('product_id')->toArray();
                if (empty(array_intersect($cartProductIds, $coupon->applicable_products))) {
                    continue;
                }
            }

            // Check applicable categories
            if (!empty($coupon->applicable_categories)) {
                $cartCategoryIds = $cart->items->map(fn ($item) => $item->product->category_id)->unique()->toArray();
                if (empty(array_intersect($cartCategoryIds, $coupon->applicable_categories))) {
                    continue;
                }
            }

            $discount = $coupon->calculateDiscount((float) $cart->subtotal, $cart->items);

            if ($discount > $bestDiscount) {
                $bestDiscount = $discount;
                $bestCoupon = $coupon;
            }
        }

        return $bestCoupon;
    }

    public function incrementUsage(): void
    {
        $this->increment('times_used');
    }
}
