<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Paginated product list for the POS grid.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('status', 'approved')
            ->where('is_active', true)
            ->with(['primaryImage', 'category:id,name', 'variants:id,product_id,name,sku,barcode,price,stock_quantity,attributes']);

        // Category filter
        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        // Stock filter (default: show all, but highlight OOS)
        if ($request->input('in_stock_only')) {
            $query->where('stock_quantity', '>', 0);
        }

        $products = $query->orderBy('name')
            ->paginate($request->input('per_page', 24));

        return response()->json([
            'products' => $products->getCollection()->map(fn ($p) => $this->formatProduct($p))->values(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    /**
     * Search products by name, SKU, or barcode.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['products' => []]);
        }

        $products = Product::query()
            ->where('status', 'approved')
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%")
                  ->orWhereHas('variants', fn ($vq) => $vq->where('barcode', 'like', "%{$query}%")->orWhere('sku', 'like', "%{$query}%"));
            })
            ->with(['primaryImage', 'category:id,name', 'variants:id,product_id,name,sku,barcode,price,stock_quantity,attributes'])
            ->orderByDesc('sales_count')
            ->limit(20)
            ->get();

        return response()->json([
            'products' => $products->map(fn ($p) => $this->formatProduct($p)),
        ]);
    }

    /**
     * Lookup product by barcode (USB scanner or camera).
     */
    public function barcodeLookup(Request $request, string $code): JsonResponse
    {
        // Validate barcode format: 4-128 alphanumeric characters (covers EAN-8/13, UPC-A/E, Code128, QR, etc.)
        $code = trim($code);
        if (! preg_match('/^[A-Za-z0-9\-\.\/\+\s]{4,128}$/', $code)) {
            return response()->json(['found' => false, 'message' => 'Invalid barcode format.'], 422);
        }

        // Normalise to uppercase for case-insensitive matching
        $codeUpper = strtoupper($code);

        // Check product barcode
        $product = Product::whereRaw('UPPER(barcode) = ?', [$codeUpper])
            ->where('status', 'approved')
            ->where('is_active', true)
            ->with(['primaryImage', 'category:id,name', 'variants:id,product_id,name,sku,barcode,price,stock_quantity,attributes'])
            ->first();

        if ($product) {
            return response()->json([
                'found'   => true,
                'product' => $this->formatProduct($product),
            ]);
        }

        // Check variant barcode
        $variant = ProductVariant::whereRaw('UPPER(barcode) = ?', [$codeUpper])
            ->where('is_active', true)
            ->with(['product' => fn ($q) => $q->with(['primaryImage', 'category:id,name'])])
            ->first();

        if ($variant && $variant->product) {
            return response()->json([
                'found'      => true,
                'product'    => $this->formatProduct($variant->product),
                'variant_id' => $variant->id,
            ]);
        }

        // Check barcodes table
        $barcode = \App\Models\Barcode::whereRaw('UPPER(barcode) = ?', [$codeUpper])->first();
        if ($barcode) {
            $product = Product::where('id', $barcode->product_id)
                ->where('status', 'approved')
                ->where('is_active', true)
                ->with(['primaryImage', 'category:id,name', 'variants:id,product_id,name,sku,barcode,price,stock_quantity,attributes'])
                ->first();

            if ($product) {
                return response()->json([
                    'found'      => true,
                    'product'    => $this->formatProduct($product),
                    'variant_id' => $barcode->variant_id,
                ]);
            }
        }

        return response()->json([
            'found'   => false,
            'message' => "No product found for barcode: {$code}",
        ], 404);
    }

    /**
     * Get active categories for the filter tabs.
     */
    public function categories(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id') // top-level only
            ->withCount(['products' => fn ($q) => $q->where('status', 'approved')->where('is_active', true)])
            ->orderBy('position')
            ->get()
            ->map(fn ($c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'products_count' => $c->products_count,
            ]);

        return response()->json(['categories' => $categories]);
    }

    /**
     * Format a product for POS display.
     */
    private function formatProduct(Product $product): array
    {
        $hasVariants = $product->variants->count() > 0;
        $totalStock  = $hasVariants
            ? $product->variants->sum('stock_quantity')
            : $product->stock_quantity;

        return [
            'id'             => $product->id,
            'name'           => $product->name,
            'sku'            => $product->sku,
            'barcode'        => $product->barcode,
            'price'          => (float) $product->price,
            'mrp'            => (float) ($product->mrp ?? $product->price),
            'cost_price'     => (float) ($product->cost_price ?? 0),
            'tax_rate'       => (float) ($product->tax_rate ?? 0),
            'hsn_code'       => $product->hsn_code,
            'stock'          => (int) $totalStock,
            'low_stock'      => $totalStock > 0 && $totalStock <= ($product->low_stock_threshold ?? 10),
            'in_stock'       => $totalStock > 0,
            'image'          => $product->primary_image_url,
            'category'       => $product->category?->name,
            'has_variants'   => $hasVariants,
            'variants'       => $hasVariants ? $product->variants->map(fn ($v) => [
                'id'         => $v->id,
                'name'       => $v->name,
                'sku'        => $v->sku,
                'barcode'    => $v->barcode,
                'price'      => (float) ($v->price ?? $product->price),
                'stock'      => (int) $v->stock_quantity,
                'in_stock'   => $v->stock_quantity > 0,
                'attributes' => $v->attributes ?? [],
            ]) : [],
        ];
    }
}
