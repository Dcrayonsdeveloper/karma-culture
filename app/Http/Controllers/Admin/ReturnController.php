<?php

namespace App\Http\Controllers\Admin;

use App\Events\RefundProcessed;
use App\Events\ReturnRequested;
use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\OrderReturn;
use App\Services\StoreCreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $return->load(['order', 'order.user', 'items.orderItem.product', 'pickupPartner.user', 'refundCoupon']);

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

        $previousStatus = $return->status;

        $return->update($updates);

        // Only on an actual transition. The listener behind this event mails
        // "Return Request Approved" whenever the return *is* approved rather
        // than when it *becomes* approved, and this dispatch fired on every
        // submit - so a double-click, a browser refresh of the PUT, or saving
        // the form again after assigning a pickup partner sent the customer
        // another approval email each time. Order::updateStatus has guarded its
        // own event this way all along; this is the same guard.
        if ($previousStatus !== $validated['status']) {
            ReturnRequested::dispatch($return);
        }

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

    public function processRefund(Request $request, OrderReturn $return, StoreCreditService $credit): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'refund_method' => 'required|in:wallet,original,bank,coupon',
            'notes' => 'nullable|string',
        ]);

        // The customer's stated preference wins over the posted field.
        //
        // Above the order threshold the store told them the money would come
        // back as credit and gave them no other option, so paying it out to a
        // card instead would quietly break the promise the form made. The
        // single-option select on the admin screen is honesty about that, not
        // the thing that enforces it - a stale form, or a hand-made POST, must
        // reach the same answer.
        $method = $return->wantsCoupon() ? 'coupon' : $validated['refund_method'];

        // 'notes' is nullable, and a validated array only carries the keys that
        // were actually sent - so reading it directly raised "Undefined array
        // key" and turned any post without the field into a 500. The admin form
        // always sends it, which is why the shop never saw this; anything else
        // that refunds a return did.
        $notes = trim((string) ($validated['notes'] ?? ''));

        $description = $notes === ''
            ? $return->description
            : ($return->description
                ? $return->description."\n\nRefund notes: ".$notes
                : 'Refund notes: '.$notes);

        $coupon = null;

        DB::transaction(function () use ($return, $validated, $method, $description, $credit, &$coupon) {
            $return->update([
                'refund_amount' => $validated['amount'],
                'refund_method' => $method,
                'description' => $description,
                'status' => 'completed',
                'completed_at' => now(),
                'processed_by' => auth()->id(),
            ]);

            // Minted in the same transaction as the row that says the refund
            // happened, so the shop can never record a completed credit refund
            // with no credit behind it. issueFor() is idempotent, so a double
            // submit re-uses the voucher rather than paying twice.
            if ($method === 'coupon' && $validated['amount'] > 0) {
                $coupon = $credit->issueFor($return, (float) $validated['amount']);
            }
        });

        // Dispatched after the commit: the listeners mail the customer, and a
        // message naming a coupon code that a rolled-back transaction never
        // created would be worse than a late one.
        RefundProcessed::dispatch($return->refresh(), (float) $validated['amount'], $method);

        if ($coupon) {
            return back()->with('success',
                'Store credit of '.format_price($validated['amount']).' issued as coupon '.$coupon->code.'.');
        }

        return back()->with('success', "Refund of " . format_price($validated['amount']) . " credited to customer's account");
    }
}
