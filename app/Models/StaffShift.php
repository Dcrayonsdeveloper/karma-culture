<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffShift extends Model
{
    protected $fillable = [
        'staff_id',
        'store_id',
        'register_id',
        'shift_start',
        'shift_end',
        'opening_cash',
        'closing_cash',
        'register_summary',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'shift_start' => 'datetime',
            'shift_end' => 'datetime',
            'opening_cash' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'register_summary' => 'array',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function close(float $closingCash): void
    {
        $this->update([
            'shift_end' => now(),
            'closing_cash' => $closingCash,
            'status' => 'closed',
        ]);
    }

    public function getVarianceAttribute(): float
    {
        if (!$this->closing_cash) {
            return 0;
        }

        $expectedCash = $this->opening_cash + ($this->register_summary['cash_sales'] ?? 0);
        return $this->closing_cash - $expectedCash;
    }
}
