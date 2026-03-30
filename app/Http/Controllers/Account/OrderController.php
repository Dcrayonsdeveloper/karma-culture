<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->orders()->with('items.product');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        return view('account.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        // Ensure user owns this order
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->load(['items.product', 'statusHistory', 'coupon', 'deliveryPartner.user']);

        return view('account.orders.show', compact('order'));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        // Ensure user owns this order
        abort_if($order->user_id !== $request->user()->id, 403);

        // Can only cancel confirmed/processing orders
        if (!in_array($order->status, ['confirmed', 'processing'])) {
            return back()->with('error', 'This order cannot be cancelled.');
        }

        $order->update(['status' => 'cancelled']);

        // Add to status history
        $order->statusHistory()->create([
            'status' => 'cancelled',
            'comment' => 'Cancelled by customer',
        ]);

        return back()->with('success', 'Order cancelled successfully.');
    }

    public function invoice(Request $request, Order $order): View
    {
        // Ensure user owns this order
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->load(['items.product', 'user']);

        return view('account.orders.invoice', compact('order'));
    }

    public function track(Request $request, Order $order): View
    {
        // Ensure user owns this order
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->load(['statusHistory', 'shipments', 'items.product', 'deliveryPartner.user']);

        $trackingSteps = $order->getTrackingSteps();
        $latestShipment = $order->shipments->first();

        return view('account.orders.track', compact('order', 'trackingSteps', 'latestShipment'));
    }

    public function reorder(Request $request, Order $order): RedirectResponse
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->load('items.product');

        $cart = Cart::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['session_id' => session()->getId()]
        );

        $added = 0;
        $unavailable = [];

        foreach ($order->items as $item) {
            if (!$item->product || !$item->product->isInStock() || !$item->product->is_active) {
                $unavailable[] = $item->product_name;
                continue;
            }

            $existing = $cart->items()->where('product_id', $item->product_id)
                ->where('variant_id', $item->variant_id)
                ->first();

            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $item->quantity]);
            } else {
                $cart->items()->create([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'total' => $item->product->price * $item->quantity,
                ]);
            }
            $added++;
        }

        $cart->recalculate();

        if ($added === 0) {
            return redirect()->route('cart.index')->with('error', 'None of the items from this order are currently available.');
        }

        $msg = $added . ' ' . str($added === 1 ? 'item' : 'items') . ' added to your cart.';
        if (count($unavailable) > 0) {
            $msg .= ' ' . count($unavailable) . ' item(s) were unavailable: ' . implode(', ', $unavailable);
        }

        return redirect()->route('cart.index')->with('success', $msg);
    }
}
