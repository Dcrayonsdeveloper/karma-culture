<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FlashSale extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'banner_url',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'flash_sale_products')
            ->withPivot(['sale_price', 'stock_limit', 'sold_count'])
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '>', now());
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->is_active
            && $this->starts_at <= now()
            && $this->ends_at >= now();
    }

    public function isUpcoming(): bool
    {
        return $this->is_active && $this->starts_at > now();
    }

    public function hasEnded(): bool
    {
        return $this->ends_at < now();
    }

    public function getRemainingTimeAttribute(): ?int
    {
        if (!$this->isActive()) {
            return null;
        }

        return now()->diffInSeconds($this->ends_at);
    }
}
