<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosReturn extends Model
{
    protected $fillable = [
        'return_number',
        'pos_sale_id',
        'store_id',
        'staff_id',
        'customer_id',
        'amount',
        'refund_method',
        'credit_note_id',
        'reason',
        'status',
        'type',
        'exchange_sale_id',
        'authorized_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($return) {
            if (empty($return->return_number)) {
                $return->return_number = 'PRET-' . now()->format('Ymd') . str_pad(static::whereDate('created_at', today())->count() + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosReturnItem::class, 'pos_return_id');
    }
}
