<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $seller = $request->user()->seller;

        $query = Order::whereHas('items', function ($q) use ($seller) {
            $q->whereHas('product', function ($pq) use ($seller) {
                $pq->where('seller_id', $seller->id);
            });
        })->with(['user', 'items' => function ($q) use ($seller) {
            $q->whereHas('product', function ($pq) use ($seller) {
                $pq->where('seller_id', $seller->id);
            });
        }]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'confirmed' => Order::whereHas('items.product', fn ($q) => $q->where('seller_id', $seller->id))
                ->where('status', 'confirmed')->count(),
            'processing' => Order::whereHas('items.product', fn ($q) => $q->where('seller_id', $seller->id))
                ->where('status', 'processing')->count(),
            'shipped' => Order::whereHas('items.product', fn ($q) => $q->where('seller_id', $seller->id))
                ->where('status', 'shipped')->count(),
        ];

        return view('seller.orders.index', compact('orders', 'stats'));
    }

    public function show(Request $request, Order $order): View
    {
        $seller = $request->user()->seller;

        // Verify seller has items in this order
        $hasItems = $order->items()->whereHas('product', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })->exists();

        abort_if(!$hasItems, 403);

        $order->load(['user', 'items' => function ($q) use ($seller) {
            $q->whereHas('product', function ($pq) use ($seller) {
                $pq->where('seller_id', $seller->id);
            })->with('product');
        }, 'statusHistory', 'shipments']);

        // Calculate seller's portion
        $sellerTotal = $order->items->sum('total');

        return view('seller.orders.show', compact('order', 'sellerTotal'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:processing,shipped',
            'tracking_number' => 'required_if:status,shipped|nullable|string|max:255',
            'carrier' => 'required_if:status,shipped|nullable|string|max:100',
        ]);

        $seller = $request->user()->seller;

        // Verify seller has items in this order
        $hasItems = $order->items()->whereHas('product', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })->exists();

        abort_if(!$hasItems, 403);

        $updates = ['status' => $request->status];

        if ($request->status === 'shipped') {
            $updates['shipped_at'] = now();

            // Create shipment record with tracking info
            $order->shipments()->create([
                'tracking_number' => $request->tracking_number,
                'carrier' => $request->carrier,
                'status' => 'shipped',
            ]);
        }

        $order->update($updates);

        $order->statusHistory()->create([
            'status' => $request->status,
            'comment' => 'Updated by seller',
        ]);

        return back()->with('success', 'Order status updated successfully.');
    }
}
