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
        return $this->hasMany(EnquiryReply::class)->oldest();
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
