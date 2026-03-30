<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CreditNote extends Model
{
    protected $fillable = [
        'credit_note_number',
        'user_id',
        'return_id',
        'order_id',
        'amount',
        'used_amount',
        'remaining_amount',
        'status',
        'expires_at',
        'secure_code',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'used_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($creditNote) {
            if (empty($creditNote->credit_note_number)) {
                $creditNote->credit_note_number = 'CN-' . strtoupper(Str::random(8));
            }
            if (empty($creditNote->secure_code)) {
                $creditNote->secure_code = hash('sha256', Str::uuid() . now()->timestamp);
            }
            if (empty($creditNote->remaining_amount)) {
                $creditNote->remaining_amount = $creditNote->amount;
            }
            if (empty($creditNote->expires_at)) {
                $creditNote->expires_at = now()->addYear();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function return(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class, 'return_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CreditNoteUsage::class);
    }

    public function isValid(): bool
    {
        return $this->status === 'active'
            && $this->remaining_amount > 0
            && $this->expires_at->isFuture();
    }

    public function redeem(float $amount, Order $order): void
    {
        if ($amount > $this->remaining_amount) {
            throw new \InvalidArgumentException('Insufficient credit note balance');
        }

        $this->remaining_amount -= $amount;
        $this->used_amount += $amount;

        if ($this->remaining_amount <= 0) {
            $this->status = 'fully_used';
        } else {
            $this->status = 'partially_used';
        }

        $this->save();

        $this->usages()->create([
            'order_id' => $order->id,
            'amount' => $amount,
        ]);
    }
}
