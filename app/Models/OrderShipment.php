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

    /** The members of the order_shipments.status ENUM. */
    public const STATUSES = ['created', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed'];

    public function addTrackingEvent(string $status, string $location, ?string $description = null): void
    {
        $history = $this->tracking_history ?? [];
        $history[] = [
            'status' => $status,
            'location' => $location,
            'description' => $description,
            'timestamp' => now()->toIso8601String(),
        ];

        $update = ['tracking_history' => $history];

        // The carrier's own wording ("RTO Initiated", "Out For Delivery") goes
        // in the JSON history, which has no vocabulary, but must not reach the
        // ENUM column. It used to: the Shiprocket webhook hands the raw scan
        // text straight to this method, MySQL rejects anything outside the six
        // members, and the webhook answered 500 - so Shiprocket retried, kept
        // failing, and tracking stopped updating at all.
        if (in_array($status, self::STATUSES, true)) {
            $update['status'] = $status;
        }

        $this->update($update);
    }
}
