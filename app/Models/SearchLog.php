<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'query',
        'results_count',
        'filters',
        'clicked_product_id',
        'clicked_position',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
