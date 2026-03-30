<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'transaction_id',
        'gateway',
        'gateway_transaction_id',
        'method',
        'amount',
        'currency',
        'status',
        'gateway_response',
        'failure_reason',
        'ip_address',
        'authorized_at',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'authorized_at' => 'datetime',
            'captured_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($payment) {
            if (empty($payment->transaction_id)) {
                $payment->transaction_id = 'TXN-' . strtoupper(uniqid());
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuccessful(): bool
    {
        return in_array($this->status, ['authorized', 'captured']);
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsAuthorized(string $gatewayTransactionId, array $response = []): void
    {
        $this->update([
            'status' => 'authorized',
            'gateway_transaction_id' => $gatewayTransactionId,
            'gateway_response' => $response,
            'authorized_at' => now(),
        ]);
    }

    public function markAsCaptured(): void
    {
        $this->update([
            'status' => 'captured',
            'captured_at' => now(),
        ]);

        $this->order->update(['payment_status' => 'paid']);
    }

    public function markAsFailed(string $reason, array $response = []): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'gateway_response' => $response,
        ]);

        $this->order->update(['payment_status' => 'failed']);
    }
}
