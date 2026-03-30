<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnController extends Controller
{
    public function index(Request $request): View
    {
        $returns = OrderReturn::whereHas('order', fn($q) => $q->where('seller_id', $request->user()->seller->id))
            ->with(['order', 'items.orderItem.product'])
            ->latest()
            ->paginate(20);

        return view('seller.returns.index', compact('returns'));
    }

    public function show(Request $request, OrderReturn $return): View
    {
        $seller = $request->user()->seller;

        // Verify seller owns this return's order
        abort_if(!$return->order || $return->order->seller_id !== $seller->id, 403);

        $return->load(['order', 'user', 'items.orderItem.product']);

        return view('seller.returns.show', compact('return'));
    }

    public function updateStatus(Request $request, OrderReturn $return): RedirectResponse
    {
        $seller = $request->user()->seller;

        // Verify seller owns this return's order
        abort_if(!$return->order || $return->order->seller_id !== $seller->id, 403);

        $validated = $request->validate([
            'status' => 'required|in:requested,approved,rejected,received',
        ]);

        $updates = ['status' => $validated['status']];

        if ($validated['status'] === 'approved' && !$return->approved_at) {
            $updates['approved_at'] = now();
        }

        $return->update($updates);

        return back()->with('success', 'Return status updated');
    }
}
