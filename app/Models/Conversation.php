<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'seller_id',
        'order_id',
        'product_id',
        'subject',
        'status',
        'last_message_at',
        'user_unread_count',
        'seller_unread_count',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    // Helper methods
    public function markAsReadByUser(): void
    {
        $this->update(['user_unread_count' => 0]);
        $this->messages()
            ->where('sender_type', 'seller')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markAsReadBySeller(): void
    {
        $this->update(['seller_unread_count' => 0]);
        $this->messages()
            ->where('sender_type', 'user')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
