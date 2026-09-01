<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Wishlist;
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

        // Order statistics
        $orderStats = [
            'total' => Order::where('user_id', $user->id)->count(),
            'confirmed' => Order::where('user_id', $user->id)->where('status', 'confirmed')->count(),
            'processing' => Order::where('user_id', $user->id)->where('status', 'processing')->count(),
            'completed' => Order::where('user_id', $user->id)->where('status', 'delivered')->count(),
        ];

        // Wishlist count
        $wishlistCount = Wishlist::where('user_id', $user->id)->count();

        return view('account.dashboard', compact('user', 'recentOrders', 'orderStats', 'wishlistCount'));
    }
}
