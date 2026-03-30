<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShiprocketService
{
    private const BASE_URL = 'https://apiv2.shiprocket.in/v1/external';

    public static function isEnabled(): bool
    {
        return (bool) Setting::get('shiprocket_enabled', false);
    }

    /**
     * Get auth token. Uses direct API token if set, otherwise logs in with email/password.
     */
    private function getToken(): string
    {
        // Direct API token (no login needed)
        $apiToken = Setting::get('shiprocket_api_token');
        if (!empty($apiToken)) {
            return $apiToken;
        }

        // Fall back to email/password login (cached for 9 days — tokens expire in 10)
        return Cache::remember('shiprocket_token', 9 * 24 * 60 * 60, function () {
            $response = Http::post(self::BASE_URL . '/auth/login', [
                'email'    => Setting::get('shiprocket_email'),
                'password' => Setting::get('shiprocket_password'),
            ]);

            if ($response->failed()) {
                Log::error('Shiprocket auth failed', ['response' => $response->body()]);
                throw new \RuntimeException('Shiprocket authentication failed');
            }

            return $response->json('token');
        });
    }

    private function api(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($this->getToken())
            ->baseUrl(self::BASE_URL)
            ->timeout(30);
    }

    /**
     * Create order on Shiprocket. Returns ['order_id', 'shipment_id', 'status'].
     */
    public function createOrder(Order $order): array
    {
        $order->load(['items.product', 'user']);
        $shipping = $order->shipping_address_snapshot;
        $billing = $order->billing_address_snapshot ?? $shipping;

        $items = $order->items->map(fn($item) => [
            'name'          => $item->product_name,
            'sku'           => $item->sku ?: ('FK-' . $item->product_id),
            'units'         => $item->quantity,
            'selling_price' => round($item->price, 2),
            'discount'      => round($item->discount ?? 0, 2),
            'tax'           => round($item->tax ?? 0, 2),
            'hsn'           => $item->product->hsn_code ?? '',
        ])->toArray();

        // Calculate total weight from products (default 0.5 kg per item if not set)
        $totalWeight = $order->items->sum(function ($item) {
            $w = $item->product->weight ?? 0.5;
            return $w * $item->quantity;
        });

        // Determine payment method
        $paymentMethod = ($order->metadata['payment_method'] ?? '') === 'cod' ? 'COD' : 'Prepaid';

        $name = $shipping['name'] ?? trim(($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? ''));
        $nameParts = explode(' ', $name, 2);

        $billingName = $billing['name'] ?? trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
        $billingParts = explode(' ', $billingName, 2);

        $payload = [
            'order_id'               => $order->order_number,
            'order_date'             => $order->created_at->format('Y-m-d H:i:s'),
            'pickup_location'        => Setting::get('shiprocket_pickup_location', 'Primary'),
            'channel_id'             => Setting::get('shiprocket_channel_id', ''),
            'billing_customer_name'  => $billingParts[0] ?? $name,
            'billing_last_name'      => $billingParts[1] ?? '',
            'billing_address'        => $billing['address'] ?? $billing['address_line_1'] ?? '',
            'billing_address_2'      => $billing['address_line_2'] ?? '',
            'billing_city'           => $billing['city'] ?? '',
            'billing_pincode'        => $billing['postal_code'] ?? $billing['zip'] ?? '',
            'billing_state'          => $billing['state'] ?? '',
            'billing_country'        => $billing['country'] ?? 'India',
            'billing_email'          => $order->user->email ?? '',
            'billing_phone'          => preg_replace('/\D/', '', $billing['phone'] ?? ''),
            'shipping_is_billing'    => $shipping === $billing ? true : false,
            'shipping_customer_name' => $nameParts[0] ?? $name,
            'shipping_last_name'     => $nameParts[1] ?? '',
            'shipping_address'       => $shipping['address'] ?? $shipping['address_line_1'] ?? '',
            'shipping_address_2'     => $shipping['address_line_2'] ?? '',
            'shipping_city'          => $shipping['city'] ?? '',
            'shipping_pincode'       => $shipping['postal_code'] ?? $shipping['zip'] ?? '',
            'shipping_state'         => $shipping['state'] ?? '',
            'shipping_country'       => $shipping['country'] ?? 'India',
            'shipping_email'         => $order->user->email ?? '',
            'shipping_phone'         => preg_replace('/\D/', '', $shipping['phone'] ?? ''),
            'order_items'            => $items,
            'payment_method'         => $paymentMethod,
            'sub_total'              => round($order->subtotal, 2),
            'length'                 => 10,
            'breadth'                => 10,
            'height'                 => 10,
            'weight'                 => max(round($totalWeight, 2), 0.5),
        ];

        // Remove channel_id if empty
        if (empty($payload['channel_id'])) {
            unset($payload['channel_id']);
        }

        $response = $this->api()->post('/orders/create/adhoc', $payload);

        if ($response->failed()) {
            Log::error('Shiprocket create order failed', [
                'order'    => $order->order_number,
                'payload'  => $payload,
                'response' => $response->json(),
            ]);
            throw new \RuntimeException('Shiprocket: ' . ($response->json('message') ?? $response->body()));
        }

        $data = $response->json();

        // Store Shiprocket IDs in order metadata
        $metadata = $order->metadata ?? [];
        $metadata['shiprocket_order_id']    = $data['order_id'] ?? null;
        $metadata['shiprocket_shipment_id'] = $data['shipment_id'] ?? null;
        $order->update(['metadata' => $metadata]);

        Log::info('Shiprocket order created', [
            'order'                  => $order->order_number,
            'shiprocket_order_id'    => $data['order_id'] ?? null,
            'shiprocket_shipment_id' => $data['shipment_id'] ?? null,
        ]);

        return $data;
    }

    /**
     * Auto-assign courier (AWB) to a shipment. Returns AWB data.
     */
    public function assignAWB(int $shipmentId, ?int $courierId = null): array
    {
        $payload = ['shipment_id' => $shipmentId];
        if ($courierId) {
            $payload['courier_id'] = $courierId;
        }

        $response = $this->api()->post('/courier/assign/awb', $payload);

        if ($response->failed()) {
            Log::error('Shiprocket AWB assign failed', [
                'shipment_id' => $shipmentId,
                'response'    => $response->json(),
            ]);
            throw new \RuntimeException('Shiprocket AWB: ' . ($response->json('message') ?? $response->body()));
        }

        return $response->json();
    }

    /**
     * Request pickup for a shipment.
     */
    public function requestPickup(int $shipmentId): array
    {
        $response = $this->api()->post('/courier/generate/pickup', [
            'shipment_id' => [$shipmentId],
        ]);

        if ($response->failed()) {
            Log::error('Shiprocket pickup request failed', [
                'shipment_id' => $shipmentId,
                'response'    => $response->json(),
            ]);
            throw new \RuntimeException('Shiprocket pickup: ' . ($response->json('message') ?? $response->body()));
        }

        return $response->json();
    }

    /**
     * Generate shipping label for a shipment.
     */
    public function generateLabel(int $shipmentId): ?string
    {
        $response = $this->api()->post('/courier/generate/label', [
            'shipment_id' => [$shipmentId],
        ]);

        if ($response->failed()) {
            Log::error('Shiprocket label generation failed', ['response' => $response->json()]);
            return null;
        }

        return $response->json('label_url') ?? $response->json('label_created') ?? null;
    }

    /**
     * Generate manifest for a shipment.
     */
    public function generateManifest(int $shipmentId): ?string
    {
        $response = $this->api()->post('/manifests/generate', [
            'shipment_id' => [$shipmentId],
        ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json('manifest_url') ?? null;
    }

    /**
     * Get tracking data for a shipment.
     */
    public function getTracking(int $shipmentId): array
    {
        $response = $this->api()->get("/courier/track/shipment/{$shipmentId}");

        if ($response->failed()) {
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Get tracking data by AWB number.
     */
    public function getTrackingByAWB(string $awb): array
    {
        $response = $this->api()->get("/courier/track/awb/{$awb}");

        if ($response->failed()) {
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Cancel a Shiprocket order.
     */
    public function cancelOrder(int $shiprocketOrderId): bool
    {
        $response = $this->api()->post('/orders/cancel', [
            'ids' => [$shiprocketOrderId],
        ]);

        return $response->successful();
    }

    /**
     * Full flow: Create order → Assign AWB → Request pickup.
     * Returns shipment data with AWB and tracking info.
     */
    public function pushOrder(Order $order): array
    {
        // Step 1: Create order on Shiprocket
        $orderData = $this->createOrder($order);

        $shipmentId = $orderData['shipment_id'] ?? null;
        if (!$shipmentId) {
            throw new \RuntimeException('No shipment ID returned from Shiprocket');
        }

        $result = [
            'shiprocket_order_id'    => $orderData['order_id'] ?? null,
            'shiprocket_shipment_id' => $shipmentId,
            'awb_code'               => null,
            'courier_name'           => null,
            'label_url'              => null,
            'pickup_status'          => null,
        ];

        // Step 2: Assign AWB (auto-selects best courier)
        try {
            $awbData = $this->assignAWB($shipmentId);
            $awbInfo = $awbData['response']['data'] ?? $awbData['awb_assign_status'] ?? null;

            if (is_array($awbInfo)) {
                $result['awb_code']     = $awbInfo['awb_code'] ?? null;
                $result['courier_name'] = $awbInfo['courier_name'] ?? null;
            } elseif (isset($awbData['response']['data']['awb_code'])) {
                $result['awb_code']     = $awbData['response']['data']['awb_code'];
                $result['courier_name'] = $awbData['response']['data']['courier_name'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('Shiprocket AWB assign skipped', ['error' => $e->getMessage()]);
        }

        // Step 3: Request pickup
        if ($result['awb_code']) {
            try {
                $pickup = $this->requestPickup($shipmentId);
                $result['pickup_status'] = $pickup['pickup_status'] ?? 'requested';
            } catch (\Exception $e) {
                Log::warning('Shiprocket pickup request skipped', ['error' => $e->getMessage()]);
            }

            // Step 4: Generate label
            try {
                $result['label_url'] = $this->generateLabel($shipmentId);
            } catch (\Exception $e) {
                Log::warning('Shiprocket label generation skipped', ['error' => $e->getMessage()]);
            }
        }

        // Create/update OrderShipment record
        $order->shipments()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'tracking_number' => $result['awb_code'],
                'carrier'         => $result['courier_name'] ?? 'Shiprocket',
                'carrier_code'    => 'shiprocket',
                'label_url'       => $result['label_url'],
                'status'          => 'created',
                'shipped_at'      => now(),
            ]
        );

        // Update order metadata with full Shiprocket data
        $metadata = $order->metadata ?? [];
        $metadata['shiprocket_order_id']    = $result['shiprocket_order_id'];
        $metadata['shiprocket_shipment_id'] = $result['shiprocket_shipment_id'];
        $metadata['shiprocket_awb']         = $result['awb_code'];
        $metadata['shiprocket_courier']     = $result['courier_name'];
        $metadata['shiprocket_label_url']   = $result['label_url'];
        $order->update(['metadata' => $metadata]);

        return $result;
    }

    /**
     * Sync tracking from Shiprocket and update local records.
     */
    public function syncTracking(Order $order): ?array
    {
        $shipmentId = $order->metadata['shiprocket_shipment_id'] ?? null;
        if (!$shipmentId) {
            return null;
        }

        $tracking = $this->getTracking($shipmentId);
        if (empty($tracking)) {
            return null;
        }

        $trackingData = $tracking['tracking_data'] ?? $tracking;
        $shipment = $order->shipments()->where('carrier_code', 'shiprocket')->latest()->first();

        if ($shipment && isset($trackingData['shipment_track_activities'])) {
            $history = collect($trackingData['shipment_track_activities'])->map(fn($a) => [
                'status'      => $a['activity'] ?? $a['sr-status'] ?? '',
                'location'    => $a['location'] ?? '',
                'description' => $a['activity'] ?? '',
                'timestamp'   => $a['date'] ?? now()->toIso8601String(),
            ])->toArray();

            $shipment->update(['tracking_history' => $history]);
        }

        // Map Shiprocket status to order status
        $currentStatus = $trackingData['shipment_status'] ?? $trackingData['current_status'] ?? null;
        if ($currentStatus) {
            $this->mapAndUpdateOrderStatus($order, $currentStatus);
        }

        return $trackingData;
    }

    /**
     * Map Shiprocket shipment status to local order status.
     */
    private function mapAndUpdateOrderStatus(Order $order, $shiprocketStatus): void
    {
        // Shiprocket status codes: https://apidocs.shiprocket.in/
        $statusMap = [
            1  => 'shipped',          // AWB Assigned
            2  => 'shipped',          // Pickup Scheduled
            3  => 'shipped',          // Picked Up
            4  => 'shipped',          // In Transit
            5  => 'shipped',          // Out For Delivery → we use shipped until actual OFD
            6  => 'out_for_delivery', // Out For Delivery
            7  => 'delivered',        // Delivered
            8  => 'cancelled',        // Cancelled
            9  => 'returned',         // RTO Initiated
            10 => 'returned',        // RTO Delivered
        ];

        $newStatus = null;
        if (is_numeric($shiprocketStatus)) {
            $newStatus = $statusMap[(int) $shiprocketStatus] ?? null;
        } else {
            // String-based status matching
            $statusStr = strtolower((string) $shiprocketStatus);
            if (str_contains($statusStr, 'delivered')) {
                $newStatus = 'delivered';
            } elseif (str_contains($statusStr, 'out for delivery')) {
                $newStatus = 'out_for_delivery';
            } elseif (str_contains($statusStr, 'transit') || str_contains($statusStr, 'picked')) {
                $newStatus = 'shipped';
            } elseif (str_contains($statusStr, 'cancel')) {
                $newStatus = 'cancelled';
            } elseif (str_contains($statusStr, 'rto') || str_contains($statusStr, 'return')) {
                $newStatus = 'returned';
            }
        }

        if ($newStatus && $newStatus !== $order->status) {
            // Only advance forward, never go backwards
            $statusOrder = ['confirmed', 'processing', 'packed', 'shipped', 'out_for_delivery', 'delivered'];
            $currentIndex = array_search($order->status, $statusOrder);
            $newIndex = array_search($newStatus, $statusOrder);

            if ($newIndex !== false && ($currentIndex === false || $newIndex > $currentIndex)) {
                $order->updateStatus($newStatus, null, "Auto-updated via Shiprocket");

                if ($newStatus === 'delivered') {
                    $shipment = $order->shipments()->latest()->first();
                    $shipment?->update(['status' => 'delivered', 'delivered_at' => now()]);
                }
            }
        }
    }

    /**
     * Clear cached auth token (useful if credentials change).
     */
    public static function clearToken(): void
    {
        Cache::forget('shiprocket_token');
    }
}
