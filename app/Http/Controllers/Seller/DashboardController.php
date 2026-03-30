<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $seller = auth()->user()->seller;
        abort_unless($seller, 403, 'Seller account not found.');

        // Today's stats
        $todayOrders = OrderItem::where('seller_id', $seller->id)
            ->whereDate('created_at', today())
            ->distinct('order_id')
            ->count('order_id');

        $excludedStatuses = ['cancelled', 'returned'];

        $todayRevenue = OrderItem::where('seller_id', $seller->id)
            ->whereDate('created_at', today())
            ->whereHas('order', fn($q) => $q->whereNotIn('status', $excludedStatuses))
            ->sum('total');

        // Overall stats
        $totalProducts = Product::where('seller_id', $seller->id)->count();
        $activeProducts = Product::where('seller_id', $seller->id)->where('is_active', true)->count();
        $totalOrders = OrderItem::where('seller_id', $seller->id)->distinct('order_id')->count('order_id');
        $totalRevenue = OrderItem::where('seller_id', $seller->id)
            ->whereHas('order', fn($q) => $q->whereNotIn('status', $excludedStatuses))
            ->sum('total');
        $pendingOrders = OrderItem::where('seller_id', $seller->id)
            ->whereHas('order', fn($q) => $q->where('status', 'confirmed'))
            ->distinct('order_id')
            ->count('order_id');

        // Average rating
        $averageRating = Review::whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
            ->where('is_approved', true)
            ->avg('rating') ?? 0;

        // Recent orders
        $recentOrders = Order::whereHas('items', fn($q) => $q->where('seller_id', $seller->id))
            ->with(['user', 'items' => fn($q) => $q->where('seller_id', $seller->id)])
            ->latest()
            ->take(10)
            ->get();

        // Low stock products
        $lowStockProducts = Product::where('seller_id', $seller->id)
            ->where('stock_quantity', '<=', 10)
            ->where('stock_quantity', '>', 0)
            ->orderBy('stock_quantity')
            ->take(5)
            ->get();

        // Top selling products
        $topProducts = Product::where('seller_id', $seller->id)
            ->orderBy('sales_count', 'desc')
            ->take(5)
            ->get();

        return view('seller.dashboard.index', compact(
            'seller',
            'todayOrders',
            'todayRevenue',
            'totalProducts',
            'activeProducts',
            'totalOrders',
            'totalRevenue',
            'pendingOrders',
            'averageRating',
            'recentOrders',
            'lowStockProducts',
            'topProducts'
        ));
    }
}
