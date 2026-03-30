<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, string $description, ?Model $model = null, ?array $extra = null): self
    {
        $user = auth()->user();

        $properties = array_filter([
            'description' => $description,
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'extra' => $extra,
        ]);

        return self::create([
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $model ? get_class($model) : null,
            'subject_id' => $model?->id,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
