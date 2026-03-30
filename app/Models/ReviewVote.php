<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewVote extends Model
{
    protected $fillable = [
        'review_id',
        'user_id',
        'vote',
    ];

    protected static function booted(): void
    {
        static::created(function ($vote) {
            if ($vote->vote === 'helpful') {
                $vote->review->increment('helpful_count');
            } else {
                $vote->review->increment('unhelpful_count');
            }
        });

        static::deleted(function ($vote) {
            if ($vote->vote === 'helpful') {
                $vote->review->decrement('helpful_count');
            } else {
                $vote->review->decrement('unhelpful_count');
            }
        });
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
