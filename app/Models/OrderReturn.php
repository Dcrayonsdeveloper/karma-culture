<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderReturn extends Model
{
    protected $table = 'returns';

    /** Money back to however the order was paid for. */
    public const PREFERENCE_REFUND = 'refund';

    /** A single-use store credit worth the refund, issued as a coupon. */
    public const PREFERENCE_COUPON = 'coupon';

    /**
     * What the customer can ask to be given back, mapped to the label the
     * return form and the admin queue both render, so the two cannot drift.
     */
    public const PREFERENCES = [
        self::PREFERENCE_REFUND => 'Refund to original payment method',
        self::PREFERENCE_COUPON => 'Store coupon',
    ];

    /**
     * Order value above which store credit is the only option.
     *
     * A setting rather than a literal so the store can move the line without a
     * deploy, and read through Setting::get() so an unset row falls back to
     * 2000 rather than to zero - a zero threshold would force EVERY return to
     * credit, which is how a policy silently becomes a bug.
     */
    public static function couponThreshold(): float
    {
        return (float) Setting::get('return_coupon_threshold', 2000);
    }

    /**
     * Is store credit compulsory for this order?
     *
     * Strictly greater than: the policy is "above 2000", so an order of exactly
     * 2000 is still the customer's own choice. Weighed against the ORDER total
     * rather than the value of the items being sent back - that is what "the
     * order price" means, and it also stops the rule being sidestepped by
     * returning one item at a time out of an expensive order.
     *
     * This is the ONE place the question is answered. The form disables the
     * radio with it, the store action overrides the posted value with it, and
     * the admin screen explains itself with it.
     */
    public static function couponForced(?Order $order): bool
    {
        return $order !== null && (float) $order->total > self::couponThreshold();
    }

    protected $fillable = [
        'return_number',
        'order_id',
        'user_id',
        'type',
        'status',
        'reason',
        'description',
        'images',
        'refund_amount',
        'refund_method',
        'refund_preference',
        'refund_coupon_id',
        'exchange_order_id',
        'processed_by',
        'pickup_partner_id',
        'approved_at',
        'pickup_scheduled_at',
        'picked_up_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'refund_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'pickup_scheduled_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($return) {
            if (empty($return->return_number)) {
                $return->return_number = 'RET-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }


    public function exchangeOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'exchange_order_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function pickupPartner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class, 'pickup_partner_id');
    }

    /** The store credit issued for this return, once one has been minted. */
    public function refundCoupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'refund_coupon_id');
    }

    /** Did the customer ask to be paid in store credit? */
    public function wantsCoupon(): bool
    {
        return $this->refund_preference === self::PREFERENCE_COUPON;
    }

    /** The customer's stated preference in words, or null if never asked. */
    public function preferenceLabel(): ?string
    {
        return self::PREFERENCES[$this->refund_preference] ?? null;
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'description' => $reason,
        ]);
    }
}
