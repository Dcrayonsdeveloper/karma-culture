<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One abandonment episode of one cart.
 *
 * Everything here is either state that cannot be derived (was a reminder sent?
 * did it convert?) or a snapshot of figures the cart destroys when it converts.
 * Nothing readable from `carts` / `cart_items` today is duplicated - the live
 * basket is always read through the cart relation.
 */
class AbandonedCart extends Model
{
    /** Nothing has been done about this cart yet. */
    public const STATUS_PENDING = 'pending';

    /** At least one recovery email has gone out. */
    public const STATUS_REMINDER_SENT = 'reminder_sent';

    /** An admin reached the customer some other way (phone, WhatsApp, in store). */
    public const STATUS_CONTACTED = 'contacted';

    /** The basket converted; recovered_order_id says which order it became. */
    public const STATUS_RECOVERED = 'recovered';

    /** Aged out of the recovery window without converting. */
    public const STATUS_EXPIRED = 'expired';

    /** An admin dismissed it - a test basket, a duplicate, a known bad address. */
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REMINDER_SENT,
        self::STATUS_CONTACTED,
        self::STATUS_RECOVERED,
        self::STATUS_EXPIRED,
        self::STATUS_ARCHIVED,
    ];

    /**
     * Statuses where the episode is still live: it can still be reminded, still
     * be recovered, and still blocks a second episode opening for the same cart.
     */
    public const OPEN_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REMINDER_SENT,
        self::STATUS_CONTACTED,
    ];

    protected $fillable = [
        'cart_id',
        'user_id',
        'session_id',
        'token',
        'last_activity_at',
        'abandoned_at',
        'item_count',
        'quantity',
        'subtotal',
        'discount',
        'tax',
        'shipping',
        'total',
        'currency',
        'recovery_status',
        'reminder_count',
        'last_reminder_at',
        'last_reminder_error',
        'last_contacted_at',
        'recovered_at',
        'recovered_order_id',
    ];

    /**
     * The token is the recovery link's entire credential, so it must never be
     * serialised into a response or a log line by accident. Blade reads it
     * deliberately through recoveryUrl().
     */
    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'abandoned_at' => 'datetime',
            'last_reminder_at' => 'datetime',
            'last_contacted_at' => 'datetime',
            'recovered_at' => 'datetime',
            'item_count' => 'integer',
            'quantity' => 'integer',
            'reminder_count' => 'integer',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * withTrashed() on purpose: users soft-delete, and an episode belonging to
     * a closed account must still render its history rather than silently
     * turning into a guest row.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function recoveredOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'recovered_order_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('recovery_status', self::OPEN_STATUSES);
    }

    public function isOpen(): bool
    {
        return in_array($this->recovery_status, self::OPEN_STATUSES, true);
    }

    public function isRecovered(): bool
    {
        return $this->recovery_status === self::STATUS_RECOVERED;
    }

    /**
     * State of the underlying basket right now, as opposed to the recovery
     * workflow state. The two are independent: a customer can clear a cart
     * without it ever being recovered.
     */
    public function cartStatus(): string
    {
        if (! $this->resolveCart()) {
            return 'deleted';
        }

        $live = $this->liveItemCount();

        if ($live === 0) {
            return 'emptied';
        }

        return $live === $this->item_count ? 'active' : 'changed';
    }

    public function cartStatusBadgeClass(): string
    {
        return match ($this->cartStatus()) {
            'active' => 'badge-info',
            'changed' => 'badge-warning',
            'emptied' => 'badge-neutral',
            default => 'badge-error',
        };
    }

    /**
     * Lines in the basket today. Read-only by design - never call
     * Cart::recalculate() from here. recalculate() re-prices lines and can
     * auto-attach a coupon the customer never saw, and it writes to the cart,
     * which would move the very timestamp this feature reports on.
     */
    public function liveItemCount(): int
    {
        $cart = $this->resolveCart();

        if (! $cart) {
            return 0;
        }

        return $cart->relationLoaded('items')
            ? $cart->items->count()
            : $cart->items()->count();
    }

    /**
     * Value of the basket today, summed from the lines.
     *
     * `carts.total` is not usable for this: checkout empties a cart with a
     * query-builder mass delete, which fires no model events, so the stored
     * total is left holding the figure of the order that emptied it.
     */
    public function liveTotal(): float
    {
        $cart = $this->resolveCart();

        if (! $cart) {
            return 0.0;
        }

        return (float) ($cart->relationLoaded('items')
            ? $cart->items->sum('total')
            : $cart->items()->sum('total'));
    }

    /** How long the basket has been sitting abandoned, in words. */
    public function timeSinceAbandonment(): string
    {
        return $this->abandoned_at->diffForHumans(null, CarbonInterface::DIFF_ABSOLUTE, false, 2);
    }

    /** The best contact address we have, or null when there is none at all. */
    public function contactEmail(): ?string
    {
        return $this->user?->email;
    }

    public function contactPhone(): ?string
    {
        return $this->user?->phone;
    }

    public function customerName(): string
    {
        $user = $this->user;

        if (! $user) {
            return 'Guest';
        }

        $name = trim((string) $user->full_name);

        return $name !== '' ? $name : (string) ($user->email ?? 'Customer');
    }

    public function recoveryUrl(): string
    {
        return route('cart.recover', ['token' => $this->token]);
    }

    public function badgeClass(): string
    {
        return match ($this->recovery_status) {
            self::STATUS_RECOVERED => 'badge-success',
            self::STATUS_REMINDER_SENT => 'badge-info',
            self::STATUS_CONTACTED => 'badge-primary',
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_EXPIRED => 'badge-error',
            default => 'badge-neutral',
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_REMINDER_SENT => 'Reminder sent',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function resolveCart(): ?Cart
    {
        return $this->relationLoaded('cart') ? $this->cart : $this->cart()->first();
    }
}
