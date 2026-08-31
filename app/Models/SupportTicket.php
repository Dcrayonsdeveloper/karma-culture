<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'category',
        'message',
        'status',
        'priority',
    ];

    public function user(): BelongsTo
    {
        // withTrashed: the ticket must stay readable after the customer deletes their account.
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class)->oldest();
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
