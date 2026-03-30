<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::whereNull('deleted_at')
            ->with(['category', 'brand']);

        if ($request->filled('stock_status')) {
            match ($request->stock_status) {
                'out_of_stock' => $query->where('stock_quantity', 0),
                'low_stock' => $query->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10),
                'in_stock' => $query->where('stock_quantity', '>', 10),
                default => null,
            };
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort', 'stock_quantity');
        $sortDir = $request->input('dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $products = $query->paginate(20)->withQueryString();

        $stats = [
            'total_products' => Product::whereNull('deleted_at')->count(),
            'active_products' => Product::where('is_active', true)->whereNull('deleted_at')->count(),
            'out_of_stock' => Product::where('stock_quantity', 0)->whereNull('deleted_at')->count(),
            'low_stock' => Product::where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10)->whereNull('deleted_at')->count(),
            'total_stock_value' => Product::whereNull('deleted_at')->selectRaw('SUM(stock_quantity * price) as value')->value('value') ?? 0,
        ];

        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Dead stock: products with zero sales in last 90 days
        $deadStock = Product::where('is_active', true)
            ->whereNull('deleted_at')
            ->where('stock_quantity', '>', 0)
            ->where(function ($q) {
                $q->where('sales_count', 0)
                  ->orWhere('updated_at', '<', now()->subDays(90));
            })
            ->orderBy('sales_count')
            ->limit(10)
            ->get(['id', 'name', 'sku', 'stock_quantity', 'price', 'sales_count']);

        return view('admin.reports.inventory', compact('products', 'stats', 'categories', 'deadStock'));
    }

    public function export(Request $request): StreamedResponse
    {
        $exportService = new ReportExportService();

        $query = Product::whereNull('deleted_at')->with(['category', 'brand']);

        if ($request->filled('stock_status')) {
            match ($request->stock_status) {
                'out_of_stock' => $query->where('stock_quantity', 0),
                'low_stock' => $query->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10),
                'in_stock' => $query->where('stock_quantity', '>', 10),
                default => null,
            };
        }

        $headers = ['Product', 'SKU', 'Category', 'Brand', 'Stock', 'Price', 'Stock Value', 'Sales Count', 'Status'];

        $rows = $query->orderBy('stock_quantity')->get()->map(fn ($p) => [
            $p->name,
            $p->sku,
            $p->category?->name ?? '-',
            $p->brand?->name ?? '-',
            $p->stock_quantity,
            $p->price,
            $p->stock_quantity * $p->price,
            $p->sales_count,
            $p->stock_quantity === 0 ? 'Out of Stock' : ($p->stock_quantity <= 10 ? 'Low Stock' : 'In Stock'),
        ]);

        $format = $request->input('format', 'csv');

        if ($format === 'excel') {
            return $exportService->exportExcel($headers, $rows, 'inventory_report_' . now()->format('Y-m-d') . '.xlsx', 'Inventory');
        }

        return $exportService->exportCsv($headers, $rows, 'inventory_report_' . now()->format('Y-m-d') . '.csv');
    }
}
