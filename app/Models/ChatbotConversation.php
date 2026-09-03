<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotConversation extends Model
{
    /**
     * Where a human has put this lead, as opposed to what the bot guessed.
     *
     * `is_lead` and `last_intent` are the assistant's own read of the chat;
     * this is the team's, set by hand from the Chat Leads screen. Keys are
     * stored in the column, so renaming one needs a data migration - the
     * labels are free to change.
     */
    public const LEAD_STATUSES = [
        'new' => 'New',
        'chatting' => 'Chatting',
        'on_hold' => 'On hold',
        'acquired' => 'Acquired',
        'lost' => 'Lost',
    ];

    /** Badge background/text pairs, matching the admin's existing palette. */
    public const LEAD_STATUS_COLOURS = [
        'new' => ['#f1f1f1', '#616161'],
        'chatting' => ['#e7f0ff', '#0064a4'],
        'on_hold' => ['#fff1e3', '#8a5c0d'],
        'acquired' => ['#e3f5e9', '#0a7d3f'],
        'lost' => ['#fde8e6', '#d72c0d'],
    ];

    protected $fillable = [
        'session_id', 'user_id', 'message_count',
        'is_lead', 'lead_id', 'last_intent', 'last_message_at',
        'lead_status',
    ];

    protected function casts(): array
    {
        return [
            'is_lead' => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * The stored key, but only ever one this build knows about. A row written
     * before the column existed, or left behind by a status we later removed,
     * still has to render as something.
     */
    public function leadStatusKey(): string
    {
        $key = (string) ($this->lead_status ?? '');

        return isset(self::LEAD_STATUSES[$key]) ? $key : 'new';
    }

    public function leadStatusLabel(): string
    {
        return self::LEAD_STATUSES[$this->leadStatusKey()];
    }

    /**
     * @return array{0: string, 1: string} background, then text colour.
     *
     * Falls back the same way leadStatusKey() does: a status added to
     * LEAD_STATUSES without a matching colour renders grey rather than
     * destructuring two nulls into the style attribute.
     */
    public function leadStatusColour(): array
    {
        return self::LEAD_STATUS_COLOURS[$this->leadStatusKey()] ?? ['#f1f1f1', '#616161'];
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
