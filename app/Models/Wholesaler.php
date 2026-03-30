<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wholesaler extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'gst_number',
        'gst_status',
        'pan_number',
        'tier',
        'discount_percentage',
        'credit_limit',
        'available_credit',
        'account_manager_id',
        'documents',
        'status',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'available_credit' => 'decimal:2',
            'documents' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function hasAvailableCredit(float $amount): bool
    {
        return $this->available_credit >= $amount;
    }

    public function useCredit(float $amount): void
    {
        $this->decrement('available_credit', $amount);
    }

    public function restoreCredit(float $amount): void
    {
        $newCredit = min($this->available_credit + $amount, $this->credit_limit);
        $this->update(['available_credit' => $newCredit]);
    }
}
