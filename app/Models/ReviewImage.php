<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewImage extends Model
{
    protected $fillable = [
        'review_id',
        'url',
        'alt_text',
        'position',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
