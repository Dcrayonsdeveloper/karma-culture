<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductQuestion extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'question',
        'is_answered',
        'is_approved',
        'vote_count',
    ];

    protected function casts(): array
    {
        return [
            'is_answered' => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ProductAnswer::class, 'question_id');
    }

    public function approvedAnswers(): HasMany
    {
        return $this->hasMany(ProductAnswer::class, 'question_id')->where('is_approved', true);
    }
}
