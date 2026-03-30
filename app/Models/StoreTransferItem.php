<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreTransferItem extends Model
{
    protected $fillable = [
        'store_transfer_id',
        'product_id',
        'variant_id',
        'product_name',
        'sku',
        'quantity_requested',
        'quantity_sent',
        'quantity_received',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StoreTransfer::class, 'store_transfer_id');
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
