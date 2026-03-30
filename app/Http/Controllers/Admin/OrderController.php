<?php

namespace App\Http\Controllers\Admin;

use App\Events\OrderDelivered;
use App\Events\OrderShipped;
use App\Events\OrderStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\ShiprocketService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::with(['user', 'items']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('email', 'like', "%{$request->search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $orders = $query->latest()->paginate($perPage)->withQueryString();

        $stats = [
            'total' => Order::count(),
            'confirmed' => Order::where('status', 'confirmed')->count(),
            'processing' => Order::whereIn('status', ['processing', 'packed'])->count(),
            'shipped' => Order::whereIn('status', ['shipped', 'out_for_delivery'])->count(),
            'completed' => Order::where('status', 'delivered')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];

        return view('admin.orders.index', compact('orders', 'stats'));
    }

    public function show(Order $order): View
    {
        $order->load([
            'user',
            'items.product',
            'items.variant',
            'statusHistory',
            'shipments',
            'coupon',
            'deliveryPartner.user',
        ]);

        $trackingSteps = $order->getTrackingSteps();
        $latestShipment = $order->shipments->first();
        $activePartners = DeliveryPartner::with('user')->where('is_active', true)->get();
        $shiprocketEnabled = ShiprocketService::isEnabled();

        return view('admin.orders.show', compact('order', 'trackingSteps', 'latestShipment', 'activePartners', 'shiprocketEnabled'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,processing,packed,shipped,out_for_delivery,delivered,cancelled,returned'],
            'comment' => ['nullable', 'string', 'max:500'],
            'carrier' => ['nullable', 'required_if:status,shipped', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'required_if:status,shipped', 'string', 'max:100'],
        ]);

        $oldStatus = $order->status;

        // Validate state transitions
        $allowedTransitions = [
            'confirmed' => ['processing', 'cancelled'],
            'processing' => ['packed', 'cancelled'],
            'packed' => ['shipped', 'cancelled'],
            'shipped' => ['out_for_delivery', 'returned'],
            'out_for_delivery' => ['delivered', 'returned'],
            'delivered' => ['returned'],
            'cancelled' => [],
            'returned' => [],
        ];

        $allowed = $allowedTransitions[$oldStatus] ?? [];
        if (!in_array($validated['status'], $allowed)) {
            return back()->with('error', "Cannot change status from \"{$oldStatus}\" to \"{$validated['status']}\".");
        }

        // Auto-push to Shiprocket when moving to "processing" (or "packed")
        $shiprocketPushed = false;
        if (ShiprocketService::isEnabled() && in_array($validated['status'], ['processing', 'packed'])) {
            $hasShiprocket = !empty($order->metadata['shiprocket_order_id']);
            if (!$hasShiprocket) {
                try {
                    $shiprocket = new ShiprocketService();
                    $result = $shiprocket->pushOrder($order);
                    $shiprocketPushed = true;
                } catch (\Exception $e) {
                    return back()->with('error', 'Shiprocket: ' . $e->getMessage());
                }
            }
        }

        // If shipping, create shipment record (only if not already created by Shiprocket)
        if ($validated['status'] === 'shipped' && !empty($validated['tracking_number'])) {
            $existingShipment = $order->shipments()->where('carrier_code', 'shiprocket')->first();
            if (!$existingShipment) {
                $order->shipments()->create([
                    'carrier' => $validated['carrier'],
                    'tracking_number' => $validated['tracking_number'],
                    'status' => 'in_transit',
                    'shipped_at' => now(),
                ]);
            }
        }

        // Update shipment status for out_for_delivery and delivered
        if (in_array($validated['status'], ['out_for_delivery', 'delivered'])) {
            $shipment = $order->shipments()->latest()->first();
            if ($shipment) {
                $shipmentStatus = $validated['status'] === 'out_for_delivery' ? 'out_for_delivery' : 'delivered';
                $shipment->update(['status' => $shipmentStatus]);
                if ($validated['status'] === 'delivered') {
                    $shipment->update(['delivered_at' => now()]);
                }
            }
        }

        $order->updateStatus($validated['status'], auth('admin')->id(), $validated['comment'] ?? null);

        OrderStatusChanged::dispatch($order, $oldStatus, $validated['status']);

        if ($validated['status'] === 'shipped') {
            $trackingNumber = $validated['tracking_number'] ?? $order->metadata['shiprocket_awb'] ?? null;
            OrderShipped::dispatch($order, $trackingNumber);
        } elseif ($validated['status'] === 'delivered') {
            OrderDelivered::dispatch($order);
        }

        $msg = "Order status updated from {$oldStatus} to {$validated['status']}";
        if ($shiprocketPushed) {
            $msg .= '. Pushed to Shiprocket automatically.';
        }

        return back()->with('success', $msg);
    }

    public function ship(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'carrier' => ['required', 'string', 'max:100'],
            'tracking_number' => ['required', 'string', 'max:100'],
        ]);

        $order->shipments()->create([
            'carrier' => $validated['carrier'],
            'tracking_number' => $validated['tracking_number'],
            'status' => 'in_transit',
            'shipped_at' => now(),
        ]);

        $order->updateStatus('shipped', auth('admin')->id(), "Shipped via {$validated['carrier']} - Tracking: {$validated['tracking_number']}");

        OrderShipped::dispatch($order, $validated['tracking_number']);

        return back()->with('success', 'Order marked as shipped');
    }

    public function invoice(Order $order): View
    {
        $order->load(['user', 'items.product']);

        return view('admin.orders.invoice', compact('order'));
    }

    public function assignPartner(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_partner_id' => 'nullable|exists:delivery_partners,id',
        ]);

        $order->update(['delivery_partner_id' => $validated['delivery_partner_id']]);

        // Also update latest shipment
        $shipment = $order->shipments()->latest()->first();
        if ($shipment) {
            $shipment->update(['delivery_partner_id' => $validated['delivery_partner_id']]);
        }

        if ($validated['delivery_partner_id']) {
            $partner = DeliveryPartner::with('user')->find($validated['delivery_partner_id']);
            if ($partner && $partner->user) {
                $order->statusHistory()->create([
                    'status' => $order->status,
                    'comment' => "Delivery partner assigned: {$partner->user->full_name} ({$partner->partner_id})",
                    'created_by' => auth('admin')->id(),
                ]);
            }
        }

        return back()->with('success', 'Delivery partner assigned successfully.');
    }

    public function setExpectedDelivery(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',
        ]);

        $order->update(['expected_delivery_date' => $request->expected_delivery_date ?: null]);

        return back()->with('success', $request->expected_delivery_date
            ? 'Expected delivery date set to ' . \Carbon\Carbon::parse($request->expected_delivery_date)->format('M d, Y') . '.'
            : 'Expected delivery date cleared.');
    }

    public function packingSlip(Order $order): View
    {
        $order->load(['items.product']);

        return view('admin.orders.packing-slip', compact('order'));
    }

    /**
     * Manually push order to Shiprocket.
     */
    public function pushToShiprocket(Order $order): RedirectResponse
    {
        if (!ShiprocketService::isEnabled()) {
            return back()->with('error', 'Shiprocket is not enabled.');
        }

        if (!empty($order->metadata['shiprocket_order_id'])) {
            return back()->with('error', 'Order is already on Shiprocket (ID: ' . $order->metadata['shiprocket_order_id'] . ')');
        }

        try {
            $shiprocket = new ShiprocketService();
            $result = $shiprocket->pushOrder($order);

            $msg = 'Order pushed to Shiprocket.';
            if (!empty($result['awb_code'])) {
                $msg .= ' AWB: ' . $result['awb_code'] . ' via ' . ($result['courier_name'] ?? 'auto');
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Shiprocket: ' . $e->getMessage());
        }
    }

    /**
     * Sync tracking from Shiprocket.
     */
    public function syncShiprocketTracking(Order $order): RedirectResponse
    {
        if (empty($order->metadata['shiprocket_shipment_id'])) {
            return back()->with('error', 'No Shiprocket shipment found for this order.');
        }

        try {
            $shiprocket = new ShiprocketService();
            $tracking = $shiprocket->syncTracking($order);

            if ($tracking) {
                return back()->with('success', 'Tracking synced from Shiprocket.');
            }
            return back()->with('error', 'No tracking data available yet.');
        } catch (\Exception $e) {
            return back()->with('error', 'Shiprocket tracking: ' . $e->getMessage());
        }
    }

    /**
     * Cancel order on Shiprocket.
     */
    public function cancelShiprocket(Order $order): RedirectResponse
    {
        $shiprocketOrderId = $order->metadata['shiprocket_order_id'] ?? null;
        if (!$shiprocketOrderId) {
            return back()->with('error', 'Order is not on Shiprocket.');
        }

        try {
            $shiprocket = new ShiprocketService();
            $shiprocket->cancelOrder($shiprocketOrderId);

            return back()->with('success', 'Order cancelled on Shiprocket.');
        } catch (\Exception $e) {
            return back()->with('error', 'Shiprocket cancel: ' . $e->getMessage());
        }
    }
}
