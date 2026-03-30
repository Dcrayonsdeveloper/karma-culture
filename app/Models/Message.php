<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'content',
        'attachments',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function ($message) {
            $conversation = $message->conversation;
            $conversation->update(['last_message_at' => now()]);

            if ($message->sender_type === 'user') {
                $conversation->increment('seller_unread_count');
            } else {
                $conversation->increment('user_unread_count');
            }
        });
    }

    // Relationships
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->sender_type === 'user'
            ? $this->belongsTo(User::class, 'sender_id')
            : $this->belongsTo(Seller::class, 'sender_id');
    }

    // Helper methods
    public function isFromUser(): bool
    {
        return $this->sender_type === 'user';
    }

    public function isFromSeller(): bool
    {
        return $this->sender_type === 'seller';
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }
}
