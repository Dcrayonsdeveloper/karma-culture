<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Seller extends Model
{
    use HasSlug;

    protected $fillable = [
        'user_id',
        'store_name',
        'business_name',
        'slug',
        'legal_name',
        'store_description',
        'gst_number',
        'pan_number',
        'description',
        'logo_url',
        'banner_url',
        'phone',
        'address',
        'status',
        'commission_rate',
        'available_balance',
        'pending_balance',
        'rating',
        'total_reviews',
        'total_products',
        'total_orders',
        'payout_method',
        'payout_email',
        'bank_name',
        'bank_account',
        'bank_routing',
        'bank_details',
        'documents',
        'settings',
        'email_notifications',
        'order_notifications',
        'review_notifications',
        'approved_at',
        'rejection_reason',
        'suspension_reason',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'rating' => 'decimal:2',
            'bank_details' => 'array',
            'documents' => 'array',
            'settings' => 'array',
            'email_notifications' => 'boolean',
            'order_notifications' => 'boolean',
            'review_notifications' => 'boolean',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('business_name')
            ->saveSlugsTo('slug');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SellerDocument::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(SellerPayout::class);
    }

    public function reviewResponses(): HasMany
    {
        return $this->hasMany(ReviewResponse::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }
}
