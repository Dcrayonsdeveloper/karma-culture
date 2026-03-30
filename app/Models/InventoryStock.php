<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    protected $fillable = [
        'product_id',
        'variant_id',
        'location_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'last_updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function ($stock) {
            $stock->available_quantity = $stock->quantity - $stock->reserved_quantity;
            $stock->last_updated_at = now();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function reserve(int $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }

        $this->increment('reserved_quantity', $quantity);
        $this->decrement('available_quantity', $quantity);

        return true;
    }

    public function release(int $quantity): void
    {
        $this->decrement('reserved_quantity', $quantity);
        $this->increment('available_quantity', $quantity);
    }

    public function deduct(int $quantity): void
    {
        $this->decrement('quantity', $quantity);
        $this->decrement('reserved_quantity', $quantity);
    }

    public function add(int $quantity): void
    {
        $this->increment('quantity', $quantity);
        $this->increment('available_quantity', $quantity);
    }
}
