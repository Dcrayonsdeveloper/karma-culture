<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    protected $fillable = ['conversation_id', 'role', 'content', 'product_ids', 'response_ms'];

    protected function casts(): array
    {
        return ['product_ids' => 'array'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }
}
