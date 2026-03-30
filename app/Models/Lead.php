<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'platform',
        'platform_id',
        'stage',
        'tags',
        'notes',
        'assigned_to',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    public function chats(): HasMany
    {
        return $this->hasMany(LeadChat::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeByPlatform($query, string $platform, string $platformId)
    {
        return $query->where('platform', $platform)->where('platform_id', $platformId);
    }

    public function scopeStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }
}
