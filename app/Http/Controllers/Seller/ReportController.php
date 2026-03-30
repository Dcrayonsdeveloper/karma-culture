<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        $sellerId = $request->user()->seller->id;
        $startDate = $request->start_date ?? now()->subDays(30);
        $endDate = $request->end_date ?? now();

        $orders = Order::where('seller_id', $sellerId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $revenueOrders = $orders->whereNotIn('status', ['cancelled', 'returned']);

        $stats = [
            'total_sales' => $revenueOrders->sum('total'),
            'total_orders' => $orders->count(),
            'average_order' => $revenueOrders->avg('total') ?? 0,
        ];

        return view('seller.reports.sales', compact('orders', 'stats', 'startDate', 'endDate'));
    }

    public function products(Request $request): View
    {
        $products = Product::where('seller_id', $request->user()->seller->id)
            ->orderBy('sales_count', 'desc')
            ->paginate(30);

        return view('seller.reports.products', compact('products'));
    }

    public function traffic(Request $request): View
    {
        $products = Product::where('seller_id', $request->user()->seller->id)
            ->orderBy('view_count', 'desc')
            ->paginate(30);

        return view('seller.reports.traffic', compact('products'));
    }

    public function export(Request $request, string $type): Response
    {
        $sellerId = $request->user()->seller->id;
        $data = match ($type) {
            'orders' => Order::where('seller_id', $sellerId)->get(),
            'products' => Product::where('seller_id', $sellerId)->get(),
            default => collect(),
        };

        $csv = $data->map(fn($item) => $item->toArray())->toArray();

        return response(implode("\n", array_map(fn($row) => implode(',', $row), $csv)))
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename={$type}-export.csv");
    }
}
