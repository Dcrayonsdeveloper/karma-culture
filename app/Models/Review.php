<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Review extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'order_item_id',
        'guest_name',
        'guest_email',
        'rating',
        'title',
        'content',
        'pros',
        'cons',
        'is_verified_purchase',
        'is_approved',
        'is_featured',
        'helpful_count',
        'unhelpful_count',
        'status',
        'is_generated',
        'generated_from_order_item_id',
        'moderated_by',
        'moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'pros' => 'array',
            'cons' => 'array',
            'is_verified_purchase' => 'boolean',
            'is_approved' => 'boolean',
            'is_featured' => 'boolean',
            'is_generated' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * guest_name comes first, not the account name.
     *
     * Rows written before reviews carried a user_id have both: the name the
     * reviewer typed and published under, and - once the backfill matched their
     * address to an account - a user. Preferring the account there would quietly
     * rename reviews that are already live on the product page. A signed-in
     * reviewer is never asked for a name, so their row has no guest_name and
     * falls through to the account's own.
     */
    public function getReviewerNameAttribute(): string
    {
        if (filled($this->guest_name)) {
            return $this->guest_name;
        }

        // full_name concatenates first and last, so it is " " for an account
        // with no surname rather than empty.
        if ($this->user && filled(trim($this->user->full_name))) {
            return trim($this->user->full_name);
        }

        return 'Anonymous';
    }

    public function getReviewerInitialAttribute(): string
    {
        return strtoupper(mb_substr($this->reviewer_name, 0, 1));
    }

    protected static function booted(): void
    {
        static::created(function ($review) {
            $review->product?->updateRating();

            // Send coupon reward for non-generated reviews
            if (!$review->is_generated) {
                app(\App\Listeners\SendCouponAfterReview::class)->handle($review);
            }
        });

        static::updated(function ($review) {
            $review->product?->updateRating();
        });

        static::deleted(function ($review) {
            $review->product?->updateRating();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ReviewVote::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(ReviewResponse::class);
    }

    public function generatedFromOrderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'generated_from_order_item_id');
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function approve(?int $moderatorId = null): void
    {
        $this->moderate('approved', true, $moderatorId);
    }

    public function reject(?int $moderatorId = null): void
    {
        $this->moderate('rejected', false, $moderatorId);
    }

    /**
     * The single place a moderation decision is written. status drives the admin
     * screens and is_approved drives the storefront, so they only move together.
     */
    protected function moderate(string $status, bool $isApproved, ?int $moderatorId): void
    {
        $this->update([
            'is_approved' => $isApproved,
            'status' => $status,
            'moderated_by' => $moderatorId ?? $this->moderated_by,
            'moderated_at' => now(),
        ]);
    }
}
