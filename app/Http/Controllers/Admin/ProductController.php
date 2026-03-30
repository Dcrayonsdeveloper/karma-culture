<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Seller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::with(['category', 'seller']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by seller
        if ($request->filled('seller')) {
            $query->where('seller_id', $request->seller);
        }

        // Filter by stock status
        if ($request->filled('stock')) {
            if ($request->stock === 'out') {
                $query->where('stock_quantity', '<=', 0);
            } elseif ($request->stock === 'low') {
                $query->whereBetween('stock_quantity', [1, 10]);
            }
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $products = $query->latest()->paginate($perPage)->withQueryString();

        $categories = Category::orderBy('name')->get();
        $sellers = Seller::with('user')->orderBy('store_name')->get();

        // Stats
        $stats = [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'inactive' => Product::where('is_active', false)->count(),
            'out_of_stock' => Product::where('stock_quantity', '<=', 0)->count(),
        ];

        return view('admin.products.index', compact('products', 'categories', 'sellers', 'stats'));
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,approve,delete',
            'ids' => 'required|string',
        ]);

        $ids = json_decode($validated['ids'], true);

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'No products selected.');
        }

        $products = Product::whereIn('id', $ids);
        $count = $products->count();

        match ($validated['action']) {
            'activate' => $products->update(['is_active' => true, 'status' => 'approved']),
            'deactivate' => $products->update(['is_active' => false]),
            'approve' => $products->update(['status' => 'approved']),
            'delete' => $products->delete(),
        };

        $actionLabel = match ($validated['action']) {
            'activate' => 'activated',
            'deactivate' => 'deactivated',
            'approve' => 'approved',
            'delete' => 'deleted',
        };

        return back()->with('success', "{$count} product(s) {$actionLabel} successfully.");
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $sellers = Seller::with('user')->orderBy('store_name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $attributes = Attribute::with('values')->orderBy('name')->get();

        return view('admin.products.create', compact('categories', 'sellers', 'brands', 'attributes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'sku' => 'required|string|max:100|unique:products',
            'barcode' => 'nullable|string|max:128',
            'price' => 'required|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0|gte:price',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'seller_id' => 'nullable|exists:sellers,id',
            'brand_id' => 'nullable|exists:brands,id',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'main_image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:2048',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp,gif|max:2048',
            'product_attributes' => 'nullable|array',
            'product_attributes.*' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['seller_id'] = $validated['seller_id'] ?: null;
        $validated['brand_id'] = $validated['brand_id'] ?: null;

        // Save attributes as JSON
        $productAttributes = collect($request->input('product_attributes', []))
            ->filter(fn($value) => $value !== null && $value !== '')
            ->toArray();
        $validated['attributes'] = !empty($productAttributes) ? $productAttributes : null;

        unset($validated['images'], $validated['main_image'], $validated['product_attributes']);

        $product = Product::create($validated);

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'url' => '/storage/' . $path,
                'is_primary' => true,
                'position' => 0,
            ]);
        }

        // Handle gallery image uploads
        if ($request->hasFile('images')) {
            $startPosition = $product->images()->max('position') ?? 0;
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => '/storage/' . $path,
                    'is_primary' => false,
                    'position' => $startPosition + $index + 1,
                ]);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'seller.user', 'images', 'variants', 'reviews.user']);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();
        $sellers = Seller::with('user')->orderBy('store_name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $attributes = Attribute::with('values')->orderBy('name')->get();
        $product->load(['images', 'variants']);

        return view('admin.products.edit', compact('product', 'categories', 'sellers', 'brands', 'attributes'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:128',
            'price' => 'required|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0|gte:price',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'seller_id' => 'nullable|exists:sellers,id',
            'brand_id' => 'nullable|exists:brands,id',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'hsn_code' => 'nullable|string|max:20',
            'main_image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:2048',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp,gif|max:2048',
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'integer|exists:product_images,id',
            'product_attributes' => 'nullable|array',
            'product_attributes.*' => 'nullable|string|max:255',
            'variants' => 'nullable|array',
            'variants.*.id' => 'required|integer|exists:product_variants,id',
            'variants.*.sku' => 'nullable|string|max:100',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.mrp' => 'nullable|numeric|min:0',
            'variants.*.stock_quantity' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_taxable'] = $request->boolean('is_taxable');
        $validated['seller_id'] = $validated['seller_id'] ?: null;
        $validated['brand_id'] = $validated['brand_id'] ?: null;

        // Save attributes as JSON
        $productAttributes = collect($request->input('product_attributes', []))
            ->filter(fn($value) => $value !== null && $value !== '')
            ->toArray();
        $validated['attributes'] = !empty($productAttributes) ? $productAttributes : null;

        // Extract variants data before unsetting
        $variantsData = $validated['variants'] ?? null;
        unset($validated['images'], $validated['main_image'], $validated['delete_images'], $validated['product_attributes'], $validated['variants']);

        $product->update($validated);

        // Update variants (inline edit from table)
        if ($variantsData) {
            foreach ($variantsData as $variantData) {
                $variant = $product->variants()->find($variantData['id']);
                if ($variant) {
                    $variant->update([
                        'sku' => $variantData['sku'] ?? $variant->sku,
                        'price' => $variantData['price'] ?? $variant->price,
                        'mrp' => $variantData['mrp'] ?? $variant->mrp,
                        'stock_quantity' => $variantData['stock_quantity'] ?? $variant->stock_quantity,
                        'is_active' => isset($variantData['is_active']),
                    ]);
                }
            }
        }

        // Delete selected gallery images
        if ($request->filled('delete_images')) {
            $imagesToDelete = ProductImage::whereIn('id', $request->delete_images)
                ->where('product_id', $product->id)
                ->get();

            foreach ($imagesToDelete as $image) {
                $storagePath = str_replace('/storage/', '', $image->url);
                Storage::disk('public')->delete($storagePath);
                $image->delete();
            }
        }

        // Replace main image if new one uploaded
        if ($request->hasFile('main_image')) {
            // Delete old primary image
            $oldPrimary = $product->images()->where('is_primary', true)->first();
            if ($oldPrimary) {
                $storagePath = str_replace('/storage/', '', $oldPrimary->url);
                Storage::disk('public')->delete($storagePath);
                $oldPrimary->delete();
            }

            $path = $request->file('main_image')->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'url' => '/storage/' . $path,
                'is_primary' => true,
                'position' => 0,
            ]);
        }

        // Upload new gallery images
        if ($request->hasFile('images')) {
            $maxPosition = $product->images()->max('position') ?? 0;
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => '/storage/' . $path,
                    'is_primary' => false,
                    'position' => $maxPosition + $index + 1,
                ]);
            }
        }

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update(['is_active' => !$product->is_active]);

        $status = $product->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Product {$status} successfully.");
    }

    public function toggleFeatured(Product $product): RedirectResponse
    {
        $product->update(['is_featured' => !$product->is_featured]);

        $status = $product->is_featured ? 'marked as featured' : 'removed from featured';

        return back()->with('success', "Product {$status}.");
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Product::with(['category', 'seller', 'images']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('seller')) {
            $query->where('seller_id', $request->seller);
        }

        $products = $query->orderBy('name')->get();

        $filename = 'products-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'name', 'sku', 'slug', 'category', 'seller', 'price', 'sale_price',
                'cost_price', 'stock_quantity', 'short_description', 'description',
                'is_active', 'is_featured', 'image_url', 'meta_title', 'meta_description',
            ]);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->name,
                    $product->sku,
                    $product->slug,
                    $product->category->name ?? '',
                    $product->seller->store_name ?? '',
                    $product->price,
                    $product->sale_price,
                    $product->cost_price,
                    $product->stock_quantity,
                    $product->short_description,
                    strip_tags($product->description),
                    $product->is_active ? '1' : '0',
                    $product->is_featured ? '1' : '0',
                    $product->primary_image_url ?? '',
                    $product->meta_title,
                    $product->meta_description,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return back()->with('error', 'Could not read the uploaded file.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'CSV file is empty or has no header row.');
        }

        $header = array_map(fn ($col) => strtolower(trim($col)), $header);

        $requiredColumns = ['name', 'sku', 'price'];
        $missingColumns = array_diff($requiredColumns, $header);
        if (!empty($missingColumns)) {
            fclose($handle);
            return back()->with('error', 'Missing required columns: ' . implode(', ', $missingColumns));
        }

        $categories = Category::pluck('id', 'name')->toArray();
        $categoriesLower = [];
        foreach ($categories as $name => $id) {
            $categoriesLower[strtolower($name)] = $id;
        }

        $sellers = Seller::pluck('id', 'store_name')->toArray();
        $sellersLower = [];
        foreach ($sellers as $name => $id) {
            $sellersLower[strtolower($name)] = $id;
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $row = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            if (count($data) !== count($header)) {
                $errors[] = "Row {$row}: Column count mismatch.";
                $skipped++;
                continue;
            }

            $record = array_combine($header, $data);

            $name = trim($record['name'] ?? '');
            $sku = trim($record['sku'] ?? '');
            $price = $record['price'] ?? '';

            if (empty($name) || empty($sku) || !is_numeric($price)) {
                $errors[] = "Row {$row}: Missing name, SKU, or invalid price.";
                $skipped++;
                continue;
            }

            if (Product::where('sku', $sku)->exists()) {
                $errors[] = "Row {$row}: SKU '{$sku}' already exists.";
                $skipped++;
                continue;
            }

            $categoryId = null;
            if (!empty($record['category'])) {
                $categoryId = $categoriesLower[strtolower(trim($record['category']))] ?? null;
            }

            $sellerId = null;
            if (!empty($record['seller'])) {
                $sellerId = $sellersLower[strtolower(trim($record['seller']))] ?? null;
            }

            $product = Product::create([
                'name' => $name,
                'sku' => $sku,
                'slug' => !empty($record['slug']) ? trim($record['slug']) : Str::slug($name),
                'price' => (float) $price,
                'sale_price' => is_numeric($record['sale_price'] ?? null) ? (float) $record['sale_price'] : null,
                'cost_price' => is_numeric($record['cost_price'] ?? null) ? (float) $record['cost_price'] : null,
                'stock_quantity' => (int) ($record['stock_quantity'] ?? 0),
                'category_id' => $categoryId,
                'seller_id' => $sellerId,
                'short_description' => $record['short_description'] ?? null,
                'description' => $record['description'] ?? $name,
                'is_active' => (bool) ($record['is_active'] ?? 1),
                'is_featured' => (bool) ($record['is_featured'] ?? 0),
                'meta_title' => $record['meta_title'] ?? null,
                'meta_description' => $record['meta_description'] ?? null,
            ]);

            // Handle image URL
            $imageUrl = trim($record['image_url'] ?? '');
            if (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                try {
                    $imageContents = @file_get_contents($imageUrl);
                    if ($imageContents) {
                        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                        $extension = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp', 'gif']) ? $extension : 'jpg';
                        $path = 'products/' . Str::uuid() . '.' . $extension;
                        Storage::disk('public')->put($path, $imageContents);

                        ProductImage::create([
                            'product_id' => $product->id,
                            'url' => asset('storage/' . $path),
                            'is_primary' => true,
                            'position' => 0,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Image download failed, skip silently
                }
            }

            $imported++;
        }

        fclose($handle);

        $message = "{$imported} product(s) imported successfully.";
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) skipped.";
        }

        if (!empty($errors)) {
            $errorSummary = implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $errorSummary .= ' ... and ' . (count($errors) - 5) . ' more.';
            }
            return back()
                ->with('warning', $message)
                ->with('error', $errorSummary);
        }

        return back()->with('success', $message);
    }
}
