<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        // Recent orders
        $recentOrders = Order::query()
            ->where('user_id', $user->id)
            ->with(['items.product'])
            ->latest()
            ->take(5)
            ->get();

        // Order statistics. Counted once per status and then bucketed, rather
        // than one query per tile matching a literal status: the old version
        // asked only about "confirmed", "processing" and "delivered", so an
        // order in any of the other seven statuses reached the total and none
        // of the tiles. Order::STATUS_BUCKETS covers the enum exhaustively, so
        // the four tiles always add back up to the total.
        $perStatus = Order::query()
            ->where('user_id', $user->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $orderStats = ['total' => (int) $perStatus->sum()];

        foreach (Order::STATUS_BUCKETS as $bucket => $statuses) {
            $orderStats[$bucket] = (int) $perStatus->only($statuses)->sum();
        }

        // No wishlist count here on purpose. The wishlist lives in the
        // kk_wishlist browser cookie (so that guests get one too) and nothing
        // writes the wishlists table, so counting rows there rendered
        // "Wishlist (0)" next to a header badge reading the real number. The
        // tile now reads the same Alpine store the header badge does.

        return view('account.dashboard', compact('user', 'recentOrders', 'orderStats'));
    }
}
