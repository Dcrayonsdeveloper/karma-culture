<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCheckoutPreference extends Model
{
    protected $fillable = [
        'user_id',
        'default_shipping_address_id',
        'default_billing_address_id',
        'default_payment_method',
        'default_shipping_speed',
        'same_as_shipping',
        'save_card_for_future',
        'enable_one_click',
    ];

    protected function casts(): array
    {
        return [
            'same_as_shipping' => 'boolean',
            'save_card_for_future' => 'boolean',
            'enable_one_click' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function defaultShippingAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'default_shipping_address_id');
    }

    public function defaultBillingAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'default_billing_address_id');
    }
}
