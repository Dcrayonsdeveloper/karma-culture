<?php

namespace App\Http\Controllers\Admin;

use App\Events\RefundProcessed;
use App\Events\ReturnRequested;
use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\DeliveryPartner;
use App\Models\OrderReturn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnController extends Controller
{
    public function index(Request $request): View
    {
        $query = OrderReturn::with(['order', 'order.user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhereHas('order', fn($oq) => $oq->where('order_number', 'like', "%{$search}%"))
                  ->orWhereHas('order.user', fn($uq) => $uq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $perPage = $request->input('per_page', 10);
        $returns = $query->latest()->paginate($perPage)->withQueryString();

        $stats = [
            'total' => OrderReturn::count(),
            'requested' => OrderReturn::where('status', 'requested')->count(),
            'approved' => OrderReturn::where('status', 'approved')->count(),
            'received' => OrderReturn::where('status', 'received')->count(),
            'completed' => OrderReturn::where('status', 'completed')->count(),
            'rejected' => OrderReturn::where('status', 'rejected')->count(),
        ];

        return view('admin.returns.index', compact('returns', 'stats'));
    }

    public function show(OrderReturn $return): View
    {
        $return->load(['order', 'order.user', 'items.orderItem.product', 'pickupPartner.user', 'creditNote']);

        $activePartners = DeliveryPartner::with('user')->where('is_active', true)->get();

        return view('admin.returns.show', compact('return', 'activePartners'));
    }

    public function updateStatus(Request $request, OrderReturn $return): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:requested,approved,rejected,pickup_scheduled,picked_up,received,processed,completed',
        ]);

        $updates = ['status' => $validated['status']];

        if ($validated['status'] === 'approved' && !$return->approved_at) {
            $updates['approved_at'] = now();
        } elseif ($validated['status'] === 'pickup_scheduled' && !$return->pickup_scheduled_at) {
            $updates['pickup_scheduled_at'] = now();
        } elseif ($validated['status'] === 'picked_up' && !$return->picked_up_at) {
            $updates['picked_up_at'] = now();
        } elseif ($validated['status'] === 'completed' && !$return->completed_at) {
            $updates['completed_at'] = now();
            $updates['processed_by'] = auth()->id();
        }

        $return->update($updates);

        ReturnRequested::dispatch($return);

        return back()->with('success', 'Return status updated');
    }

    public function assignPartner(Request $request, OrderReturn $return): RedirectResponse
    {
        $validated = $request->validate([
            'pickup_partner_id' => 'nullable|exists:delivery_partners,id',
        ]);

        $return->update(['pickup_partner_id' => $validated['pickup_partner_id']]);

        if ($validated['pickup_partner_id']) {
            $partner = DeliveryPartner::with('user')->find($validated['pickup_partner_id']);
            $name = $partner?->user?->full_name ?? 'partner';
            return back()->with('success', "Pickup partner assigned: {$name}");
        }

        return back()->with('success', 'Pickup partner removed');
    }

    public function processRefund(Request $request, OrderReturn $return): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'refund_method' => 'required|in:wallet,original,bank',
            'notes' => 'nullable|string',
        ]);

        $return->update([
            'refund_amount' => $validated['amount'],
            'refund_method' => $validated['refund_method'],
            'description' => $validated['notes'] ? ($return->description ? $return->description . "\n\nRefund notes: " . $validated['notes'] : 'Refund notes: ' . $validated['notes']) : $return->description,
            'status' => 'completed',
            'completed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        // Credit the refund amount to customer's wallet (credit note)
        $customer = $return->order->user;
        if ($customer && $validated['amount'] > 0) {
            CreditNote::create([
                'user_id' => $customer->id,
                'return_id' => $return->id,
                'order_id' => $return->order_id,
                'amount' => $validated['amount'],
                'status' => 'active',
            ]);
        }

        RefundProcessed::dispatch($return, (float) $validated['amount'], $validated['refund_method']);

        return back()->with('success', "Refund of " . format_price($validated['amount']) . " credited to customer's account");
    }
}
