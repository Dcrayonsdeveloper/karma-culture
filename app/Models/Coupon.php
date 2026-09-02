<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_USED_UP   = 'used_up';
    public const STATUS_DISABLED  = 'disabled';

    /**
     * Every state a coupon can be in, keyed for the query string and mapped to
     * the label the admin sees. Listed in the order the index tabs render them,
     * which is not the precedence order - that lives in status().
     */
    public const STATUSES = [
        self::STATUS_ACTIVE    => 'Active',
        self::STATUS_SCHEDULED => 'Scheduled',
        self::STATUS_EXPIRED   => 'Expired',
        self::STATUS_USED_UP   => 'Used up',
        self::STATUS_DISABLED  => 'Disabled',
    ];

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

    /**
     * The one reason this coupon is not usable right now, or STATUS_ACTIVE.
     *
     * The admin index used to answer this question twice - once in SQL for the
     * tab filter, once in PHP for the row badge - and the two disagreed. The
     * SQL weighed only is_active and expires_at, so a coupon that had hit its
     * usage cap, or had not started yet, was listed under "Active" while its
     * own badge read "Inactive". Both sides now come from here.
     *
     * Precedence is deliberate, and scopeStatusIs() mirrors it exactly: the two
     * states a coupon cannot be talked out of come first, so one that is past
     * its expiry date reports "Expired" even when it was also switched off.
     * That is the answer the admin is looking for, and it is what keeps the
     * Expired tab complete.
     */
    public function status(): string
    {
        // Not isPast(): a coupon expiring on this exact second is finished, and
        // the scope's `<=` has to agree or that row falls between two tabs.
        if ($this->expires_at && ! $this->expires_at->isFuture()) {
            return self::STATUS_EXPIRED;
        }

        // `!== null` rather than a truthy test - a limit of 0 is a coupon
        // nobody may redeem, not a coupon without a limit.
        if ($this->usage_limit !== null && $this->times_used >= $this->usage_limit) {
            return self::STATUS_USED_UP;
        }

        if (! $this->is_active) {
            return self::STATUS_DISABLED;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return self::STATUS_SCHEDULED;
        }

        return self::STATUS_ACTIVE;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status()];
    }

    /**
     * The badge class the admin screens paint this status with.
     *
     * Lives here rather than in the blades because the index row and the edit
     * header both draw this badge, and the map was copied into both - which is
     * how the two screens came to disagree in the first place. No default arm:
     * a status with no colour should fail loudly, not paint the wrong one.
     */
    public function statusBadgeClass(): string
    {
        return match ($this->status()) {
            self::STATUS_ACTIVE    => 'badge-success',
            self::STATUS_SCHEDULED => 'badge-info',
            self::STATUS_EXPIRED   => 'badge-error',
            self::STATUS_USED_UP   => 'badge-warning',
            self::STATUS_DISABLED  => 'badge-neutral',
        };
    }

    public function isValid(): bool
    {
        return $this->status() === self::STATUS_ACTIVE;
    }

    /** Rows still inside their expiry date. The complement of STATUS_EXPIRED. */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** Rows still under their redemption cap. The complement of STATUS_USED_UP. */
    public function scopeNotUsedUp(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('times_used', '<', 'usage_limit'));
    }

    /**
     * The SQL twin of status(): same order, same boundaries.
     *
     * Each arm excludes the states that outrank it, so the five scopes
     * partition the table - every coupon matches exactly one, which is what
     * lets the tab counts add up to the total.
     */
    public function scopeStatusIs(Builder $query, string $status): Builder
    {
        return match ($status) {
            self::STATUS_EXPIRED => $query
                ->whereNotNull('expires_at')->where('expires_at', '<=', now()),

            self::STATUS_USED_UP => $query->notExpired()
                ->whereNotNull('usage_limit')->whereColumn('times_used', '>=', 'usage_limit'),

            self::STATUS_DISABLED => $query->notExpired()->notUsedUp()
                ->where('is_active', false),

            self::STATUS_SCHEDULED => $query->notExpired()->notUsedUp()
                ->where('is_active', true)
                ->whereNotNull('starts_at')->where('starts_at', '>', now()),

            self::STATUS_ACTIVE => $query->notExpired()->notUsedUp()
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now())),

            // No default arm on purpose. An unrecognised status used to mean
            // "no filter", which quietly returned every coupon under whatever
            // tab was asked for - the same silent-wrong-answer failure this
            // whole change exists to remove. match() throws instead.
        };
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

        // Sort ascending - cheapest items are the "free" ones
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
        // Was another hand-written copy of the validity predicate.
        $coupons = static::where('auto_apply', true)->statusIs(self::STATUS_ACTIVE)->get();

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
