<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryPartner extends Model
{
    protected $fillable = [
        'user_id',
        'partner_id',
        'phone',
        'company_name',
        'vehicle_type',
        'vehicle_number',
        'license_number',
        'profile_photo',
        'id_proof',
        'license_document',
        'address_proof',
        'verification_status',
        'verification_note',
        'verified_at',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function hasDocuments(): bool
    {
        return $this->id_proof && $this->license_document;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class);
    }

    public function activeOrders(): HasMany
    {
        return $this->hasMany(Order::class)
            ->whereIn('status', ['shipped', 'out_for_delivery']);
    }

    public function returnPickups(): HasMany
    {
        return $this->hasMany(OrderReturn::class, 'pickup_partner_id');
    }
}
