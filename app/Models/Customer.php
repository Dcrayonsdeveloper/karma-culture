<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'total_orders',
        'total_spent',
        'average_order_value',
        'last_order_at',
        'status',
        'notes',
        'tags',
        'marketing_consent',
        'sms_consent',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'total_spent' => 'decimal:2',
            'average_order_value' => 'decimal:2',
            'last_order_at' => 'datetime',
            'tags' => 'array',
            'marketing_consent' => 'boolean',
            'sms_consent' => 'boolean',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', 'user_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class, 'user_id', 'user_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTopSpenders($query, int $limit = 10)
    {
        return $query->orderByDesc('total_spent')->take($limit);
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    // Helper methods
    public function updateOrderStats(): void
    {
        $orders = $this->orders()->where('payment_status', 'paid');

        $this->update([
            'total_orders' => $orders->count(),
            'total_spent' => $orders->sum('total'),
            'average_order_value' => $orders->avg('total') ?? 0,
            'last_order_at' => $orders->latest()->first()?->created_at,
        ]);
    }

    public function isVip(): bool
    {
        return $this->total_spent >= 1000 || $this->total_orders >= 10;
    }
}
