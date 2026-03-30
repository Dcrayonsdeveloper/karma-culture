<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackOrderController extends Controller
{
    public function index(): View
    {
        return view('track-order.index');
    }

    public function track(Request $request): View
    {
        if (auth()->check()) {
            // Logged-in user: only needs the order number
            $validated = $request->validate([
                'order_number' => 'required|string',
            ]);

            $order = Order::where('order_number', $validated['order_number'])
                ->where('user_id', auth()->id())
                ->with(['items.product', 'shipments', 'statusHistory', 'deliveryPartner.user'])
                ->first();

            $errorMsg = 'Order not found. Please check your order number.';
        } else {
            // Guest: needs order number + email
            $validated = $request->validate([
                'order_number' => 'required|string',
                'email'        => 'required|email',
            ]);

            $order = Order::where('order_number', $validated['order_number'])
                ->whereHas('user', fn($q) => $q->where('email', $validated['email']))
                ->with(['items.product', 'shipments', 'statusHistory', 'deliveryPartner.user'])
                ->first();

            $errorMsg = 'Order not found. Please check your order number and email.';
        }

        if (!$order) {
            return view('track-order.index', ['error' => $errorMsg]);
        }

        $latestShipment = $order->shipments->first();

        return view('track-order.show', compact('order', 'latestShipment'));
    }
}
