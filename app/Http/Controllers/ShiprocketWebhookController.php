<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShiprocketWebhookController extends Controller
{
    /**
     * Handle Shiprocket tracking webhook.
     * Shiprocket sends POST with order/shipment tracking updates.
     */
    public function handle(Request $request)
    {
        // This endpoint is CSRF-exempt and can move an order to "delivered" or
        // "cancelled", so the token is mandatory. The old check was
        // `if ($token && $token !== '...')`, which let a request carrying NO
        // token through the guard entirely - an open endpoint in practice.
        $expected = (string) config('services.shiprocket.webhook_token');
        $token = (string) ($request->header('X-Webhook-Token') ?? $request->input('token') ?? '');

        if ($expected === '' || ! hash_equals($expected, $token)) {
            Log::warning('Shiprocket webhook rejected', [
                'reason' => $expected === '' ? 'no token configured' : 'token mismatch',
                'ip'     => $request->ip(),
            ]);

            return response()->json(['status' => 'unauthorized'], 401);
        }

        $data = $request->all();

        Log::info('Shiprocket webhook received', $data);

        $orderNumber = $data['order_id'] ?? null;
        $awb = $data['awb'] ?? null;
        $currentStatus = $data['current_status'] ?? null;
        $shipmentStatus = $data['shipment_status'] ?? null;
        $etd = $data['etd'] ?? null;

        if (!$orderNumber && !$awb) {
            return response()->json(['status' => 'ignored', 'reason' => 'no order_id or awb']);
        }

        // Find order by order_number (Shiprocket stores our order_number as order_id)
        $order = null;
        if ($orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();
        }

        // Fallback: find by AWB in shipments
        if (!$order && $awb) {
            $shipment = \App\Models\OrderShipment::where('tracking_number', $awb)->first();
            $order = $shipment?->order;
        }

        if (!$order) {
            Log::warning('Shiprocket webhook: order not found', ['order_id' => $orderNumber, 'awb' => $awb]);
            return response()->json(['status' => 'ignored', 'reason' => 'order not found']);
        }

        // Update expected delivery date
        if ($etd && !$order->expected_delivery_date) {
            try {
                $order->update(['expected_delivery_date' => \Carbon\Carbon::parse($etd)->toDateString()]);
            } catch (\Exception $e) {
                // ignore parse errors
            }
        }

        // Update tracking history on the shipment
        $shipment = $order->shipments()->where('carrier_code', 'shiprocket')->latest()->first();
        if ($shipment) {
            // Update courier name if provided
            if (!empty($data['courier_name'])) {
                $shipment->update(['carrier' => $data['courier_name']]);
            }

            // Add tracking event
            $scans = $data['scans'] ?? [];
            if (!empty($scans)) {
                $history = collect($scans)->map(fn($scan) => [
                    'status'      => $scan['status'] ?? $scan['activity'] ?? '',
                    'location'    => $scan['location'] ?? '',
                    'description' => $scan['activity'] ?? $scan['status'] ?? '',
                    'timestamp'   => $scan['date'] ?? now()->toIso8601String(),
                ])->toArray();
                $shipment->update(['tracking_history' => $history]);
            } elseif ($currentStatus) {
                $shipment->addTrackingEvent(
                    $currentStatus,
                    $data['current_location'] ?? '',
                    $currentStatus
                );
            }
        }

        // Map status and update order
        $statusToMap = $shipmentStatus ?? $currentStatus;
        if ($statusToMap) {
            $newStatus = $this->mapStatus($statusToMap);
            if ($newStatus && $newStatus !== $order->status) {
                $statusOrder = ['confirmed', 'processing', 'packed', 'shipped', 'out_for_delivery', 'delivered'];
                $currentIndex = array_search($order->status, $statusOrder);
                $newIndex = array_search($newStatus, $statusOrder);

                // Terminal statuses are not points on the forward path, so they
                // are absent from $statusOrder and array_search returned false
                // for them - the branch below never ran and the update was
                // dropped on the floor. A returned-to-origin parcel therefore
                // stayed booked as a delivered sale, in the order list and in
                // every revenue report built on it.
                if (in_array($newStatus, ['cancelled', 'returned'], true)) {
                    $order->updateStatus($newStatus, null, "Shiprocket: {$currentStatus}");

                    if ($shipment) {
                        $shipment->update(['status' => 'failed']);
                    }
                } elseif ($newIndex !== false && ($currentIndex === false || $newIndex > $currentIndex)) {
                    $order->updateStatus($newStatus, null, "Shiprocket: {$currentStatus}");

                    if ($newStatus === 'delivered' && $shipment) {
                        $shipment->update(['status' => 'delivered', 'delivered_at' => now()]);
                    } elseif ($newStatus === 'shipped' && $shipment) {
                        $shipment->update(['status' => 'in_transit']);
                    } elseif ($newStatus === 'out_for_delivery' && $shipment) {
                        $shipment->update(['status' => 'out_for_delivery']);
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function mapStatus($status): ?string
    {
        if (is_numeric($status)) {
            return match ((int) $status) {
                1, 2, 3, 4, 5 => 'shipped',
                6              => 'out_for_delivery',
                7              => 'delivered',
                8              => 'cancelled',
                9, 10          => 'returned',
                default        => null,
            };
        }

        $s = strtolower((string) $status);
        if (str_contains($s, 'delivered') && !str_contains($s, 'out for')) return 'delivered';
        if (str_contains($s, 'out for delivery')) return 'out_for_delivery';
        if (str_contains($s, 'transit') || str_contains($s, 'picked') || str_contains($s, 'shipped')) return 'shipped';
        if (str_contains($s, 'cancel')) return 'cancelled';
        if (str_contains($s, 'rto') || str_contains($s, 'return')) return 'returned';

        return null;
    }
}
