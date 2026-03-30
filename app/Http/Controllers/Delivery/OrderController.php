<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $partner = $request->user('delivery')->deliveryPartner;

        abort_unless($order->delivery_partner_id === $partner->id, 403);

        $order->load(['user', 'items.product', 'shipments', 'statusHistory' => fn($q) => $q->latest()]);

        $nextStatus = match ($order->status) {
            'shipped' => 'out_for_delivery',
            'out_for_delivery' => 'delivered',
            default => null,
        };

        return view('delivery.orders.show', compact('order', 'partner', 'nextStatus'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $partner = $request->user('delivery')->deliveryPartner;

        abort_unless($order->delivery_partner_id === $partner->id, 403);

        $request->validate([
            'status' => ['required', 'in:out_for_delivery,delivered'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus = $request->status;

        $allowedTransitions = [
            'shipped' => 'out_for_delivery',
            'out_for_delivery' => 'delivered',
        ];

        if (!isset($allowedTransitions[$order->status]) || $allowedTransitions[$order->status] !== $newStatus) {
            return back()->with('error', 'Invalid status transition.');
        }

        $comment = $request->comment
            ? $request->comment . ' (by delivery partner: ' . $partner->user->full_name . ')'
            : 'Status updated by delivery partner: ' . $partner->user->full_name;

        $order->updateStatus($newStatus, $request->user('delivery')->id, $comment);

        // Update shipment status too
        $shipment = $order->shipments()->latest()->first();
        if ($shipment) {
            $shipmentStatus = match ($newStatus) {
                'out_for_delivery' => 'out_for_delivery',
                'delivered' => 'delivered',
                default => $shipment->status,
            };
            $shipment->update(['status' => $shipmentStatus]);
        }

        $statusLabel = str_replace('_', ' ', $newStatus);

        return back()->with('success', "Order marked as {$statusLabel}.");
    }

    public function collectPayment(Request $request, Order $order): RedirectResponse
    {
        $partner = $request->user('delivery')->deliveryPartner;

        abort_unless($order->delivery_partner_id === $partner->id, 403);

        if ($order->payment_collected) {
            return back()->with('error', 'Payment has already been collected for this order.');
        }

        $order->update([
            'payment_collected' => true,
            'payment_collected_at' => now(),
            'payment_collected_by' => $partner->id,
            'payment_status' => 'paid',
            'paid_amount' => $order->total,
        ]);

        $order->statusHistory()->create([
            'status' => $order->status,
            'comment' => 'Payment collected by delivery partner: ' . $partner->user->full_name . ' (' . $partner->partner_id . ')',
            'created_by' => $request->user('delivery')->id,
        ]);

        return back()->with('success', 'Payment of ' . format_price($order->total) . ' collected successfully.');
    }
}
