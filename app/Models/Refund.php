<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'order_id',
        'payment_id',
        'refund_id',
        'amount',
        'type',
        'status',
        'reason',
        'gateway_refund_id',
        'gateway_response',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($refund) {
            if (empty($refund->refund_id)) {
                $refund->refund_id = 'REF-' . strtoupper(uniqid());
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function process(string $gatewayRefundId): void
    {
        $this->update([
            'status' => 'completed',
            'gateway_refund_id' => $gatewayRefundId,
            'processed_at' => now(),
        ]);

        // Update order payment status
        $totalRefunded = $this->order->refunds()->where('status', 'completed')->sum('amount');
        if ($totalRefunded >= $this->order->paid_amount) {
            $this->order->update(['payment_status' => 'refunded']);
        } else {
            $this->order->update(['payment_status' => 'partial_refund']);
        }
    }
}
