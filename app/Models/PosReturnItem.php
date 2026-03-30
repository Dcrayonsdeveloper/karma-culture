<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosReturnItem extends Model
{
    protected $fillable = [
        'pos_return_id',
        'product_id',
        'variant_id',
        'product_name',
        'quantity',
        'price',
        'refund_amount',
        'reason',
        'condition',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'refund_amount' => 'decimal:2',
        ];
    }

    public function return(): BelongsTo
    {
        return $this->belongsTo(PosReturn::class, 'pos_return_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
