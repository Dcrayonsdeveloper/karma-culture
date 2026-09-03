<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Notification extends Model
{
    /**
     * A notification row is addressed to one of two bells. Both live in this
     * table and both are keyed by user_id, because an admin is a users row with
     * role = 'admin', so the audience is the only thing that keeps a customer's
     * own order updates out of the admin bell when the admin also shops.
     */
    public const AUDIENCE_CUSTOMER = 'customer';
    public const AUDIENCE_ADMIN = 'admin';

    protected $fillable = [
        'uuid',
        'user_id',
        'type',
        'audience',
        'title',
        'content',
        'data',
        'channel',
        'is_read',
        'read_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($notification) {
            if (empty($notification->uuid)) {
                $notification->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function markAsUnread(): void
    {
        if ($this->is_read) {
            $this->update([
                'is_read' => false,
                'read_at' => null,
            ]);
        }
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForAdmin($query)
    {
        return $query->where('audience', self::AUDIENCE_ADMIN);
    }

    public function scopeForCustomer($query)
    {
        return $query->where('audience', self::AUDIENCE_CUSTOMER);
    }
}
