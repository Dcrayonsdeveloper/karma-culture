<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadChat extends Model
{
    protected $fillable = [
        'lead_id',
        'sender',
        'message',
        'platform_message_id',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function isFromCustomer(): bool
    {
        return $this->sender === 'customer';
    }

    public function isFromBot(): bool
    {
        return $this->sender === 'bot';
    }
}
