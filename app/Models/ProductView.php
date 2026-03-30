<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductView extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'session_id',
        'referrer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(Product $product, ?User $user = null): self
    {
        return static::create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'session_id' => session()->getId(),
            'referrer' => request()->header('referer'),
        ]);
    }
}
