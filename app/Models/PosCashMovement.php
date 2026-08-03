<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosCashMovement extends Model
{
    protected $table = 'pos_cash_movements';

    protected $fillable = [
        'shift_id',
        'staff_id',
        'type',           // sale | refund | cash_in | cash_out
        'amount',
        'reference_type', // pos_sale | pos_return | manual
        'reference_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(StaffShift::class, 'shift_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Record a cash movement from any context.
     */
    public static function record(int $shiftId, int $staffId, string $type, float $amount, ?string $refType = null, ?int $refId = null, ?string $note = null): void
    {
        static::create([
            'shift_id'       => $shiftId,
            'staff_id'       => $staffId,
            'type'           => $type,
            'amount'         => abs($amount),
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'note'           => $note,
        ]);
    }
}
