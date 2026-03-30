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

    public function getReviewerNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->full_name;
        }

        return $this->guest_name ?? 'Anonymous';
    }

    public function getReviewerInitialAttribute(): string
    {
        if ($this->user) {
            return strtoupper(substr($this->user->first_name, 0, 1));
        }

        return strtoupper(substr($this->guest_name ?? 'A', 0, 1));
    }

    protected static function booted(): void
    {
        static::created(function ($review) {
            $review->product->updateRating();

            // Send coupon reward for non-generated reviews
            if (!$review->is_generated) {
                app(\App\Listeners\SendCouponAfterReview::class)->handle($review);
            }
        });

        static::updated(function ($review) {
            $review->product->updateRating();
        });

        static::deleted(function ($review) {
            $review->product->updateRating();
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

    public function approve(): void
    {
        $this->update([
            'is_approved' => true,
            'status' => 'approved',
            'moderated_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'is_approved' => false,
            'status' => 'rejected',
            'moderated_at' => now(),
        ]);
    }
}
