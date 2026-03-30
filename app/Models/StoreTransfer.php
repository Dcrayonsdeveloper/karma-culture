<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StoreTransfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'from_store_id',
        'to_store_id',
        'requested_by',
        'approved_by',
        'status',
        'notes',
        'rejection_reason',
    ];

    protected static function booted(): void
    {
        static::creating(function ($transfer) {
            if (empty($transfer->transfer_number)) {
                $today = now()->format('Ymd');
                $count = static::whereDate('created_at', today())->count() + 1;
                $transfer->transfer_number = 'TRF-' . $today . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StoreTransferItem::class);
    }
}
