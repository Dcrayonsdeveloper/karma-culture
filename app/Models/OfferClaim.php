<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Somebody asked for the exit-popup offer, at an email address.
 *
 * A claim is a promise, not a redemption: it says "this address was shown the
 * offer and accepted it". Whether the coupon behind it can still be spent is a
 * separate question, and it is answered by Coupon::canBeUsedBy() against
 * coupon_usage - never here. See the migration for why the two are kept apart.
 */
class OfferClaim extends Model
{
    protected $fillable = [
        'email',
        'user_id',
        'code',
        'coupon_id',
        'source',
        'claimed_at',
        'expires_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Claims still inside their window.
     *
     * A scope rather than an inline where(), for the same reason
     * Coupon::status() exists: the admin list, the storefront lookup and the
     * tests all have to agree on what "still live" means, and this codebase has
     * already paid for letting two copies of a validity predicate drift.
     *
     * A null expires_at is a claim that never lapses - what you get when the
     * admin sets the horizon to nothing.
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where(
            fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())
        );
    }
}
