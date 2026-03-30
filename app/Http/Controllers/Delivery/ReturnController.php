<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnController extends Controller
{
    public function index(Request $request): View
    {
        $partner = $request->user('delivery')->deliveryPartner;

        $query = OrderReturn::where('pickup_partner_id', $partner->id)
            ->with(['order', 'order.user', 'items.orderItem.product']);

        $tab = $request->get('tab', 'active');

        $stats = [
            'active' => OrderReturn::where('pickup_partner_id', $partner->id)
                ->whereIn('status', ['approved', 'pickup_scheduled', 'picked_up'])
                ->count(),
            'picked_up' => OrderReturn::where('pickup_partner_id', $partner->id)
                ->where('status', 'picked_up')
                ->count(),
            'completed' => OrderReturn::where('pickup_partner_id', $partner->id)
                ->whereIn('status', ['received', 'processed', 'completed'])
                ->count(),
        ];

        $returns = match ($tab) {
            'completed' => $query->clone()->whereIn('status', ['received', 'processed', 'completed'])->latest()->get(),
            'all' => $query->clone()->latest()->get(),
            default => $query->clone()->whereIn('status', ['approved', 'pickup_scheduled', 'picked_up'])->latest()->get(),
        };

        return view('delivery.returns.index', compact('partner', 'returns', 'stats', 'tab'));
    }

    public function show(Request $request, OrderReturn $return): View
    {
        $partner = $request->user('delivery')->deliveryPartner;

        abort_unless($return->pickup_partner_id === $partner->id, 403);

        $return->load(['order', 'order.user', 'items.orderItem.product']);

        return view('delivery.returns.show', compact('return', 'partner'));
    }

    public function updateStatus(Request $request, OrderReturn $return): RedirectResponse
    {
        $partner = $request->user('delivery')->deliveryPartner;

        abort_unless($return->pickup_partner_id === $partner->id, 403);

        $request->validate([
            'status' => ['required', 'in:pickup_scheduled,picked_up,received'],
        ]);

        $newStatus = $request->status;

        $allowedTransitions = [
            'approved' => ['pickup_scheduled'],
            'pickup_scheduled' => ['picked_up'],
            'picked_up' => ['received'],
        ];

        $allowed = $allowedTransitions[$return->status] ?? [];
        if (!in_array($newStatus, $allowed)) {
            return back()->with('error', 'Invalid status transition.');
        }

        $updates = ['status' => $newStatus];

        if ($newStatus === 'pickup_scheduled') {
            $updates['pickup_scheduled_at'] = now();
        } elseif ($newStatus === 'picked_up') {
            $updates['picked_up_at'] = now();
        }

        $return->update($updates);

        $statusLabel = str_replace('_', ' ', $newStatus);

        return back()->with('success', "Return marked as {$statusLabel}.");
    }
}
