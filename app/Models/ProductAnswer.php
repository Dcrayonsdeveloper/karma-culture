<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAnswer extends Model
{
    protected $fillable = [
        'question_id',
        'user_id',
        'seller_id',
        'answer',
        'is_approved',
        'is_seller_response',
        'vote_count',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'is_seller_response' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function ($answer) {
            $answer->question->update(['is_answered' => true]);
        });
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ProductQuestion::class, 'question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }
}
