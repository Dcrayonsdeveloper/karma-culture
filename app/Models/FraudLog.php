<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudLog extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'risk_score',
        'indicators',
        'action',
        'reviewed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'risk_score' => 'decimal:2',
            'indicators' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function scopeFlagged($query)
    {
        return $query->where('action', 'flagged');
    }

    public function scopeBlocked($query)
    {
        return $query->where('action', 'blocked');
    }

    public function scopeUnreviewed($query)
    {
        return $query->whereNull('reviewed_by');
    }

    public function scopeHighRisk($query)
    {
        return $query->where('risk_score', '>=', 70);
    }
}
