<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $partner = $request->user('delivery')->deliveryPartner;
        abort_unless($partner, 403, 'Delivery partner account not found.');

        $activeOrders = $partner->orders()
            ->with(['user', 'items'])
            ->whereIn('status', ['shipped', 'out_for_delivery'])
            ->latest()
            ->get();

        $deliveredOrders = $partner->orders()
            ->with(['user', 'items'])
            ->where('status', 'delivered')
            ->latest()
            ->get();

        $activeReturns = OrderReturn::where('pickup_partner_id', $partner->id)
            ->whereIn('status', ['approved', 'pickup_scheduled', 'picked_up'])
            ->count();

        $stats = [
            'active' => $activeOrders->count(),
            'delivered_today' => $partner->orders()
                ->where('status', 'delivered')
                ->whereDate('delivered_at', today())
                ->count(),
            'total_delivered' => $deliveredOrders->count(),
            'active_returns' => $activeReturns,
        ];

        $tab = $request->get('tab', 'active');

        $orders = match ($tab) {
            'delivered' => $deliveredOrders,
            'all' => $partner->orders()->with(['user', 'items'])->latest()->get(),
            default => $activeOrders,
        };

        return view('delivery.dashboard', compact('partner', 'orders', 'stats', 'tab'));
    }
}
