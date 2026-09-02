<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enquiry extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'is_read',
        'read_at',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
                'status' => $this->status === 'new' ? 'read' : $this->status,
            ]);
        }
    }

    public function replies(): HasMany
    {
        // Second-precision timestamps tie when two replies land in the same
        // second, so fall back to the monotonic id to keep the thread stable.
        return $this->hasMany(EnquiryReply::class)->oldest()->orderBy('id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }
}
