<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConsent extends Model
{
    protected $fillable = [
        'user_id',
        'consent_type',
        'granted',
        'ip_address',
        'user_agent',
        'granted_at',
        'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'granted' => 'boolean',
            'granted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grant(): void
    {
        $this->update([
            'granted' => true,
            'granted_at' => now(),
            'withdrawn_at' => null,
        ]);
    }

    public function withdraw(): void
    {
        $this->update([
            'granted' => false,
            'withdrawn_at' => now(),
        ]);
    }
}
