<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use App\Models\ReturnItem;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnController extends Controller
{
    public function index(Request $request): View
    {
        $returns = OrderReturn::whereHas('order', fn($q) => $q->where('user_id', $request->user()->id))
            ->with(['order:id,order_number', 'items.orderItem.product:id,name,slug'])
            ->latest()
            ->paginate(10);

        return view('account.returns.index', compact('returns'));
    }

    public function create(Request $request): View
    {
        // Get IDs of order items that already have a return request (any status except rejected)
        $returnedItemIds = ReturnItem::whereHas('return', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id)
              ->where('status', '!=', 'rejected');
        })->pluck('order_item_id')->toArray();

        $returnWindowDays = (int) Setting::get('return_window_days', 7);
        $returnMinHours = (int) Setting::get('return_min_hours', 24);

        $orders = $request->user()->orders()
            ->where('status', 'delivered')
            ->where('delivered_at', '>=', now()->subDays($returnWindowDays))
            ->where('delivered_at', '<=', now()->subHours($returnMinHours))
            ->with('items.product:id,name,slug')
            ->get();

        // Filter out items that already have return requests
        $orders->each(function ($order) use ($returnedItemIds) {
            $order->setRelation('items', $order->items->reject(fn ($item) => in_array($item->id, $returnedItemIds)));
        });

        // Remove orders with no returnable items left
        $orders = $orders->filter(fn ($order) => $order->items->isNotEmpty());

        return view('account.returns.create', compact('orders', 'returnWindowDays', 'returnMinHours'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'type' => 'required|in:return,exchange',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'nullable|string|max:500',
            'items.*.condition' => 'required|in:unopened,opened,damaged',
        ]);

        // Verify the order belongs to the authenticated user
        $order = \App\Models\Order::where('id', $validated['order_id'])
            ->where('user_id', auth()->id())
            ->where('status', 'delivered')
            ->firstOrFail();

        // Verify each item belongs to this order and return qty doesn't exceed ordered qty
        $submittedItemIds = collect($validated['items'])->pluck('order_item_id')->toArray();
        $orderItems = $order->items()->whereIn('id', $submittedItemIds)->get()->keyBy('id');

        foreach ($validated['items'] as $item) {
            if (! isset($orderItems[$item['order_item_id']])) {
                abort(403, 'One or more items do not belong to this order.');
            }
            if ($item['quantity'] > $orderItems[$item['order_item_id']]->quantity) {
                return back()->withInput()->withErrors(['items' => 'Return quantity cannot exceed the ordered quantity.']);
            }
        }

        // Check for duplicate return requests on the submitted items
        $alreadyReturned = ReturnItem::whereIn('order_item_id', $submittedItemIds)
            ->whereHas('return', fn ($q) => $q->where('status', '!=', 'rejected'))
            ->exists();

        if ($alreadyReturned) {
            return back()->withInput()->withErrors(['items' => 'One or more selected items already have a return request.']);
        }

        $return = OrderReturn::create([
            'order_id' => $validated['order_id'],
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'requested',
        ]);

        foreach ($validated['items'] as $item) {
            $return->items()->create([
                'order_item_id' => $item['order_item_id'],
                'quantity' => $item['quantity'],
                'reason' => $item['reason'] ?? null,
                'condition' => $item['condition'],
            ]);
        }

        return redirect()->route('account.returns.show', $return)
            ->with('success', 'Return request submitted successfully.');
    }

    public function show(Request $request, OrderReturn $return): View
    {
        if ($return->order->user_id !== $request->user()->id) {
            abort(403);
        }

        $return->load(['order', 'items.orderItem.product:id,name,slug', 'pickupPartner.user']);

        return view('account.returns.show', compact('return'));
    }
}
