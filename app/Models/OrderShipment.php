<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShipment extends Model
{
    protected $fillable = [
        'order_id',
        'delivery_partner_id',
        'tracking_number',
        'carrier',
        'carrier_code',
        'label_url',
        'weight',
        'dimensions',
        'status',
        'tracking_history',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'dimensions' => 'array',
            'tracking_history' => 'array',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryPartner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class);
    }

    public function addTrackingEvent(string $status, string $location, ?string $description = null): void
    {
        $history = $this->tracking_history ?? [];
        $history[] = [
            'status' => $status,
            'location' => $location,
            'description' => $description,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->update([
            'tracking_history' => $history,
            'status' => $status,
        ]);
    }
}
