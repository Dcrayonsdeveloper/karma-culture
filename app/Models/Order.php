<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /**
     * Which status an order may move to next. Orders are created as "pending",
     * so that key has to exist or every new order is frozen: an empty allow
     * list rejects every transition, including cancelling.
     */
    public const STATUS_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['packed', 'cancelled'],
        'packed' => ['shipped', 'cancelled'],
        'shipped' => ['out_for_delivery', 'returned'],
        'out_for_delivery' => ['delivered', 'returned'],
        'delivered' => ['returned'],
        'cancelled' => [],
        'returned' => [],
    ];

    /** The statuses this order can legally move to right now. */
    public function allowedNextStatuses(): array
    {
        return self::STATUS_TRANSITIONS[$this->status] ?? [];
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedNextStatuses(), true);
    }
    protected $fillable = [
        'order_number',
        'user_id',
        'seller_id',
        'delivery_partner_id',
        'shipping_address_id',
        'billing_address_id',
        'coupon_id',
        'status',
        'payment_status',
        'subtotal',
        'discount',
        'tax',
        'shipping_cost',
        'total',
        'paid_amount',
        'payment_collected',
        'payment_collected_at',
        'payment_collected_by',
        'currency',
        'shipping_address_snapshot',
        'billing_address_snapshot',
        'notes',
        'admin_notes',
        'ip_address',
        'user_agent',
        'source',
        'metadata',
        'confirmed_at',
        'packed_at',
        'shipped_at',
        'out_for_delivery_at',
        'delivered_at',
        'cancelled_at',
        'expected_delivery_date',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'shipping_address_snapshot' => 'array',
            'billing_address_snapshot' => 'array',
            'metadata' => 'array',
            'confirmed_at' => 'datetime',
            'packed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'out_for_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'payment_collected' => 'boolean',
            'payment_collected_at' => 'datetime',
            'expected_delivery_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(bin2hex(random_bytes(4)));

        return "{$prefix}-{$date}-{$random}";
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'shipping_address_id');
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'billing_address_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function deliveryPartner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class, 'order_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return in_array($this->status, ['confirmed', 'processing', 'shipped', 'delivered']);
    }

    public function isPacked(): bool
    {
        return in_array($this->status, ['packed', 'shipped', 'out_for_delivery', 'delivered']);
    }

    public function isShipped(): bool
    {
        return in_array($this->status, ['shipped', 'out_for_delivery', 'delivered']);
    }

    public function isOutForDelivery(): bool
    {
        return in_array($this->status, ['out_for_delivery', 'delivered']);
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'processing']);
    }

    public function canBeReturned(): bool
    {
        return $this->status === 'delivered'
            && $this->delivered_at
            && $this->delivered_at->addHours(24)->isPast()
            && $this->delivered_at->diffInDays(now()) <= 7;
    }

    public function updateStatus(string $status, ?int $userId = null, ?string $comment = null): void
    {
        $this->update(['status' => $status]);

        $this->statusHistory()->create([
            'status' => $status,
            'comment' => $comment,
            'created_by' => $userId,
        ]);

        // Update timestamps
        match ($status) {
            'confirmed' => $this->update(['confirmed_at' => now()]),
            'packed' => $this->update(['packed_at' => now()]),
            'shipped' => $this->update(['shipped_at' => now()]),
            'out_for_delivery' => $this->update(['out_for_delivery_at' => now()]),
            'delivered' => $this->update(['delivered_at' => now()]),
            'cancelled' => $this->update(['cancelled_at' => now()]),
            default => null,
        };
    }

    public function getTrackingSteps(): array
    {
        $steps = [
            [
                'key' => 'confirmed',
                'label' => 'Ordered',
                'icon' => 'clipboard-check',
                'completed' => $this->isConfirmed(),
                'current' => $this->status === 'confirmed',
                'timestamp' => $this->confirmed_at,
            ],
            [
                'key' => 'packed',
                'label' => 'Packed',
                'icon' => 'cube',
                'completed' => $this->isPacked(),
                'current' => $this->status === 'packed',
                'timestamp' => $this->packed_at,
            ],
            [
                'key' => 'shipped',
                'label' => 'Shipped',
                'icon' => 'truck',
                'completed' => $this->isShipped(),
                'current' => $this->status === 'shipped',
                'timestamp' => $this->shipped_at,
            ],
            [
                'key' => 'out_for_delivery',
                'label' => 'Out for Delivery',
                'icon' => 'map-pin',
                'completed' => $this->isOutForDelivery(),
                'current' => $this->status === 'out_for_delivery',
                'timestamp' => $this->out_for_delivery_at,
            ],
            [
                'key' => 'delivered',
                'label' => 'Delivered',
                'icon' => 'check-circle',
                'completed' => $this->isDelivered(),
                'current' => $this->status === 'delivered',
                'timestamp' => $this->delivered_at,
            ],
        ];

        return $steps;
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, $this->total - $this->paid_amount);
    }
}
