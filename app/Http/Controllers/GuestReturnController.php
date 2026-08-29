<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\ReturnItem;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Returns for customers who checked out as guests.
 *
 * Ownership is proved by the order-number-plus-mobile lookup on the tracking
 * page, which records the verified order in the session. Signed-in customers
 * keep using Account\ReturnController.
 */
class GuestReturnController extends Controller
{
    public function create(Request $request, Order $order): View|RedirectResponse
    {
        if (! $this->verified($request, $order)) {
            return $this->reject();
        }

        if (! $this->withinWindow($order)) {
            return redirect()->route('track-order')
                ->with('error', 'This order is outside the return window.');
        }

        $order->load('items.product');

        return view('track-order.return', [
            'order' => $order,
            'items' => $order->items->reject(fn ($item) => $this->alreadyRequested($item->id))->values(),
        ]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        if (! $this->verified($request, $order)) {
            return $this->reject();
        }

        if (! $this->withinWindow($order)) {
            return redirect()->route('track-order')
                ->with('error', 'This order is outside the return window.');
        }

        // Unchecked rows still post their hidden id, and their disabled inputs
        // post nothing, so drop them before validating.
        $request->merge([
            'items' => array_values(array_filter(
                (array) $request->input('items', []),
                fn ($row) => filter_var($row['selected'] ?? false, FILTER_VALIDATE_BOOLEAN)
            )),
        ]);

        $validated = $request->validate([
            'type' => 'required|in:return,exchange',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.condition' => 'required|in:unopened,opened,damaged',
        ], [
            'items.required' => 'Select at least one item to return.',
        ]);

        $orderItems = $order->items()
            ->whereIn('id', collect($validated['items'])->pluck('order_item_id'))
            ->get()
            ->keyBy('id');

        foreach ($validated['items'] as $item) {
            $orderItem = $orderItems[$item['order_item_id']] ?? null;

            // An item must belong to this order, must not exceed what was
            // bought, and must not already be under a live return request.
            if (! $orderItem) {
                return back()->withInput()->withErrors(['items' => 'One or more items do not belong to this order.']);
            }
            if ($item['quantity'] > $orderItem->quantity) {
                return back()->withInput()->withErrors(['items' => 'Return quantity cannot exceed the quantity ordered.']);
            }
            if ($this->alreadyRequested($orderItem->id)) {
                return back()->withInput()->withErrors(['items' => 'One or more items already have a return request.']);
            }
        }

        $return = OrderReturn::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,   // null for a guest order
            'type' => $validated['type'],
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'requested',
        ]);

        foreach ($validated['items'] as $item) {
            $return->items()->create([
                'order_item_id' => $item['order_item_id'],
                'quantity' => $item['quantity'],
                'condition' => $item['condition'],
            ]);
        }

        return redirect()->route('track-order')
            ->with('success', "Return request {$return->return_number} submitted. We will contact you on the mobile number for this order.");
    }

    /**
     * The tracking page records every order the visitor has proved ownership of.
     */
    private function verified(Request $request, Order $order): bool
    {
        if ($request->user() && $order->user_id === $request->user()->id) {
            return true;
        }

        return in_array($order->id, session('tracked_orders', []), true);
    }

    private function reject(): RedirectResponse
    {
        return redirect()->route('track-order')
            ->with('error', 'Please look up your order first to request a return.');
    }

    /**
     * Same window the account-side return form uses.
     */
    private function withinWindow(Order $order): bool
    {
        if ($order->status !== 'delivered' || ! $order->delivered_at) {
            return false;
        }

        $windowDays = (int) Setting::get('return_window_days', 7);
        $minHours = (int) Setting::get('return_min_hours', 24);

        return $order->delivered_at->gte(now()->subDays($windowDays))
            && $order->delivered_at->lte(now()->subHours($minHours));
    }

    private function alreadyRequested(int $orderItemId): bool
    {
        return ReturnItem::where('order_item_id', $orderItemId)
            ->whereHas('return', fn ($q) => $q->where('status', '!=', 'rejected'))
            ->exists();
    }
}
