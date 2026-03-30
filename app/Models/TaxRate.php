<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $fillable = [
        'name',
        'state',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cgst_rate' => 'decimal:2',
            'sgst_rate' => 'decimal:2',
            'igst_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
