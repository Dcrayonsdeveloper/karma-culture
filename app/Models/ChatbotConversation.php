<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotConversation extends Model
{
    protected $fillable = [
        'session_id', 'user_id', 'message_count',
        'is_lead', 'lead_id', 'last_intent', 'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'is_lead' => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
