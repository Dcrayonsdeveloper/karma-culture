<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'type',
        'model_type',
        'model_id',
        'data',
        'ip_address',
        'user_agent',
        'referrer',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $type, ?int $userId = null, ?string $modelType = null, ?int $modelId = null, array $data = []): self
    {
        return static::create([
            'user_id' => $userId,
            'session_id' => session()->getId(),
            'type' => $type,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->header('referer'),
        ]);
    }
}
