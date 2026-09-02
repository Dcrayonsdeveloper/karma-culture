<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Rules\NoHtml;
use App\Rules\ValidationRules as V;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    /**
     * Field shapes shared by store() and update(), so the two forms cannot
     * drift apart and let a value through on edit that create refuses.
     *
     * Each is a plain rule array; append the per-action bits by spreading:
     * `[...self::SKU_RULES, 'required', 'unique:products,sku']`.
     */
    private const SLUG_RULES = ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];

    /** products.sku / product_variants.sku are both varchar(50). */
    private const SKU_RULES = ['string', 'max:50', 'regex:/^[A-Za-z0-9._\/-]+$/'];

    /** stock_quantity is an UNSIGNED INT column - a negative value is a DB error, not a 422. */
    private const STOCK_RULES = ['required', 'integer', 'min:0', 'max:1000000'];

    /** A #rrggbb swatch, which is all <input type="color"> ever posts. */
    private const HEX_RULES = ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];

    /**
     * `extensions` checks the filename and `mimetypes` sniffs the bytes; both
     * are needed, since either one alone is trivially spoofed.
     */
    private const VIDEO_RULES = ['file', 'extensions:mp4,webm,mov', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:51200'];

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

        $categories = Category::optionsWithPath();
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
            'action' => ['required', Rule::in(['activate', 'deactivate', 'approve', 'delete'])],
            // A JSON array of ids built by the page's checkboxes. Bounded so a
            // crafted request cannot post a megabyte of payload to decode.
            'ids' => ['required', 'string', 'max:20000'],
        ]);

        $decoded = json_decode($validated['ids'], true);

        if (! is_array($decoded)) {
            return back()->with('error', 'No products selected.');
        }

        // The decoded values are client-supplied: keep the positive integers and
        // discard everything else rather than handing arbitrary types to the
        // query builder.
        $ids = collect($decoded)
            ->filter(fn ($id) => is_int($id) || (is_string($id) && ctype_digit($id)))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->take(500)
            ->values()
            ->all();

        if (empty($ids)) {
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
        $categories = Category::assignableOptions();
        $sellers = Seller::with('user')->orderBy('store_name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $attributes = Attribute::with('values')->orderBy('name')->get();

        return view('admin.products.create', compact('categories', 'sellers', 'brands', 'attributes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => V::text(max: 255, min: 2),
            'slug' => [...self::SLUG_RULES, 'unique:products,slug'],
            // CKEditor output - HTML by design, so NoHtml deliberately does not
            // apply here. The `description` column is TEXT (65535 bytes).
            'description' => ['required', 'string', 'max:65535'],
            'short_description' => V::textarea(required: false, max: 500),
            // products.sku is varchar(50) and unique - a longer value used to be
            // accepted by validation and then truncated or rejected by MySQL.
            'sku' => [...self::SKU_RULES, 'required', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9-]+$/', 'unique:products,barcode'],
            'price' => V::money(),
            'mrp' => [...V::money(required: false), 'gte:price'],
            'cost_price' => V::money(required: false),
            'stock_quantity' => self::STOCK_RULES,
            'category_id' => V::foreignId('categories'),
            'seller_id' => V::foreignId('sellers', required: false),
            'brand_id' => V::foreignId('brands', required: false),
            'is_active' => V::boolean(),
            'is_featured' => V::boolean(),
            'meta_title' => V::text(required: false, max: 255),
            'meta_description' => V::textarea(required: false, max: 500),
            'main_image' => V::image(required: false, maxKb: 2048, allowGif: true),
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => V::image(maxKb: 2048, allowGif: true),
            'videos' => ['nullable', 'array', 'max:5'],
            'videos.*' => self::VIDEO_RULES,
            'product_attributes' => ['nullable', 'array', 'max:50'],
            // A value may be a single string (text attributes) or an array of
            // checked values (size, colour, …) so one product can offer several.
            'product_attributes.*' => 'nullable',
            'product_attributes.*.*' => ['nullable', 'string', 'max:255', new NoHtml],
            // Read straight off the request further down, so it has to be
            // validated here or it reaches the JSON column unchecked.
            'colours' => ['nullable', 'array', 'max:50'],
            'colours.*.name' => ['nullable', 'string', 'max:60', new NoHtml],
            'colours.*.hex' => self::HEX_RULES,
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['seller_id'] = $validated['seller_id'] ?: null;
        $validated['brand_id'] = $validated['brand_id'] ?: null;
        // `mrp` column is NOT NULL - default it to price when the form omits it
        // (admin form currently only shows a single price field).
        $validated['mrp'] = $validated['mrp'] ?? $validated['price'];

        // Save attributes as JSON
        $productAttributes = collect($request->input('product_attributes', []))
            ->map(fn ($value) => is_array($value)
                ? array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''))
                : $value)
            ->filter(fn ($value) => is_array($value)
                ? count($value) > 0
                : ($value !== null && $value !== ''))
            ->toArray();
        // Colour list lives alongside any other product attributes.
        $colours = collect($request->input('colours', []))
            ->map(fn ($c) => [
                'name' => trim((string) ($c['name'] ?? '')),
                'hex' => trim((string) ($c['hex'] ?? '')) ?: '#000000',
            ])
            ->filter(fn ($c) => $c['name'] !== '')
            ->unique('name')
            ->values()
            ->all();
        if ($colours) {
            $productAttributes['Colours'] = $colours;
        } else {
            unset($productAttributes['Colours']);
        }

        $validated['attributes'] = ! empty($productAttributes) ? $productAttributes : null;

        unset(
            $validated['images'],
            $validated['videos'],
            $validated['main_image'],
            $validated['product_attributes'],
            $validated['colours'],
        );

        $product = Product::create($validated);

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'url' => '/storage/'.$path,
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
                    'media_type' => 'image',
                    'url' => '/storage/'.$path,
                    'is_primary' => false,
                    'position' => $startPosition + $index + 1,
                ]);
            }
        }

        // Handle gallery video uploads
        if ($request->hasFile('videos')) {
            $startPosition = $product->images()->max('position') ?? 0;
            foreach ($request->file('videos') as $index => $file) {
                $path = $file->store('products/videos', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'media_type' => 'video',
                    'url' => '/storage/'.$path,
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
        $categories = Category::assignableOptions($product->category_id);
        $sellers = Seller::with('user')->orderBy('store_name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $attributes = Attribute::with('values')->orderBy('name')->get();
        $product->load(['images', 'variants']);

        return view('admin.products.edit', compact('product', 'categories', 'sellers', 'brands', 'attributes'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => V::text(max: 255, min: 2),
            'slug' => [...self::SLUG_RULES, Rule::unique('products', 'slug')->ignore($product->id)],
            // CKEditor output - HTML by design, so NoHtml deliberately does not
            // apply here. The `description` column is TEXT (65535 bytes).
            'description' => ['required', 'string', 'max:65535'],
            'short_description' => V::textarea(required: false, max: 500),
            'sku' => [...self::SKU_RULES, 'required', Rule::unique('products', 'sku')->ignore($product->id)],
            'barcode' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique('products', 'barcode')->ignore($product->id)],
            'price' => V::money(),
            'mrp' => [...V::money(required: false), 'gte:price'],
            'cost_price' => V::money(required: false),
            'stock_quantity' => self::STOCK_RULES,
            'category_id' => V::foreignId('categories'),
            'seller_id' => V::foreignId('sellers', required: false),
            'brand_id' => V::foreignId('brands', required: false),
            'is_active' => V::boolean(),
            'is_featured' => V::boolean(),
            'is_taxable' => V::boolean(),
            'meta_title' => V::text(required: false, max: 255),
            'meta_description' => V::textarea(required: false, max: 500),
            // weight/length/width/height are decimal(8,2) columns.
            'weight' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
            'length' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
            'width' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
            'height' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
            // An Indian HSN code is 4, 6 or 8 digits.
            'hsn_code' => ['nullable', 'string', 'regex:/^[0-9]{4,8}$/'],
            'main_image' => V::image(required: false, maxKb: 2048, allowGif: true),
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => V::image(maxKb: 2048, allowGif: true),
            'videos' => ['nullable', 'array', 'max:5'],
            'videos.*' => self::VIDEO_RULES, // 50 MB each
            'delete_images' => ['nullable', 'array', 'max:200'],
            // Scoped to this product: `exists` alone would let a crafted request
            // delete another product's images.
            'delete_images.*' => ['integer', Rule::exists('product_images', 'id')->where('product_id', $product->id)],
            'product_attributes' => ['nullable', 'array', 'max:50'],
            // A value may be a single string (text attributes) or an array of
            // checked values (size, colour, …) so one product can offer several.
            'product_attributes.*' => 'nullable',
            'product_attributes.*.*' => ['nullable', 'string', 'max:255', new NoHtml],
            'variants' => ['nullable', 'array', 'max:100'],
            // id is absent on rows added with the "Add size" button, so a new row
            // is created rather than rejected by validation. When present it must
            // belong to THIS product.
            'variants.*.id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $product->id)],
            'variants.*.name' => ['nullable', 'string', 'max:100', new NoHtml],
            'variants.*.measurements' => ['nullable', 'string', 'max:160', new NoHtml],
            'variants.*.colour' => ['nullable', 'string', 'max:60', new NoHtml],
            'variants.*.colour_hex' => self::HEX_RULES,
            // product_variants.sku is UNIQUE. Without this a duplicate reached
            // MySQL and blew up with a 500 instead of a field error.
            'variants.*.sku' => [...self::SKU_RULES, 'nullable', 'distinct:ignore_case', $this->uniqueVariantSku($request)],
            'variants.*.price' => V::money(required: false),
            'variants.*.mrp' => [...V::money(required: false), $this->variantMrpAtLeastPrice($request)],
            'variants.*.stock_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'variants.*.delete' => ['nullable', 'boolean'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            // Colours are a product-level list, not a per-size value, so one
            // colour is entered once instead of on every size row.
            'colours' => ['nullable', 'array', 'max:50'],
            'colours.*.name' => ['nullable', 'string', 'max:60', new NoHtml],
            'colours.*.hex' => self::HEX_RULES,
            // Both models land on the PUBLIC disk, so the extension has to be
            // pinned: `file|max:10240` alone accepted a .php upload into a
            // web-served directory.
            'model_glb' => ['nullable', 'file', 'extensions:glb,gltf', 'mimetypes:model/gltf-binary,model/gltf+json,application/octet-stream', 'max:10240'],
            'model_usdz' => ['nullable', 'file', 'extensions:usdz', 'mimetypes:model/vnd.usdz+zip,application/zip,application/octet-stream', 'max:10240'],
            'delete_model_glb' => ['nullable', 'boolean'],
            'delete_model_usdz' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_taxable'] = $request->boolean('is_taxable');
        $validated['seller_id'] = $validated['seller_id'] ?: null;
        $validated['brand_id'] = $validated['brand_id'] ?: null;

        // Save attributes as JSON
        $productAttributes = collect($request->input('product_attributes', []))
            ->map(fn ($value) => is_array($value)
                ? array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''))
                : $value)
            ->filter(fn ($value) => is_array($value)
                ? count($value) > 0
                : ($value !== null && $value !== ''))
            ->toArray();
        // Colour list lives alongside any other product attributes.
        $colours = collect($request->input('colours', []))
            ->map(fn ($c) => [
                'name' => trim((string) ($c['name'] ?? '')),
                'hex' => trim((string) ($c['hex'] ?? '')) ?: '#000000',
            ])
            ->filter(fn ($c) => $c['name'] !== '')
            ->unique('name')
            ->values()
            ->all();
        if ($colours) {
            $productAttributes['Colours'] = $colours;
        } else {
            unset($productAttributes['Colours']);
        }

        $validated['attributes'] = ! empty($productAttributes) ? $productAttributes : null;

        // Extract variants data before unsetting
        $variantsData = $validated['variants'] ?? null;
        unset(
            $validated['images'],
            $validated['main_image'],
            $validated['delete_images'],
            $validated['product_attributes'],
            $validated['colours'],
            $validated['variants'],
            $validated['model_glb'],
            $validated['model_usdz'],
            $validated['delete_model_glb'],
            $validated['delete_model_usdz'],
        );

        // Handle 3D model uploads (.glb / .usdz)
        if ($request->boolean('delete_model_glb') && $product->model_glb_path) {
            if (! str_starts_with($product->model_glb_path, 'http')) {
                Storage::disk('public')->delete(ltrim($product->model_glb_path, '/'));
            }
            $validated['model_glb_path'] = null;
        }
        if ($request->hasFile('model_glb')) {
            $path = $request->file('model_glb')->store('models', 'public');
            $validated['model_glb_path'] = $path;
        }
        if ($request->boolean('delete_model_usdz') && $product->model_usdz_path) {
            if (! str_starts_with($product->model_usdz_path, 'http')) {
                Storage::disk('public')->delete(ltrim($product->model_usdz_path, '/'));
            }
            $validated['model_usdz_path'] = null;
        }
        if ($request->hasFile('model_usdz')) {
            $path = $request->file('model_usdz')->store('models', 'public');
            $validated['model_usdz_path'] = $path;
        }

        $product->update($validated);

        // Sizes & pricing rows. Each row is one purchasable size of this product,
        // optionally in a specific colour, with its own price and stock. Rows
        // without an id are new, rows flagged `delete` are removed.
        if (is_array($variantsData)) {
            foreach ($variantsData as $variantData) {
                $id = $variantData['id'] ?? null;
                $variant = $id ? $product->variants()->find($id) : null;

                if (filter_var($variantData['delete'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $variant?->delete();
                    continue;
                }

                $size = trim((string) ($variantData['name'] ?? ''));
                if ($size === '' && ! $variant) {
                    continue; // blank new row - nothing to save
                }

                $colour = trim((string) ($variantData['colour'] ?? ''));
                $hex = trim((string) ($variantData['colour_hex'] ?? ''));
                // Measurements ride in the variant attributes so the assistant can
                // advise on fit without a schema change per garment type.
                $measurements = trim((string) ($variantData['measurements'] ?? ''));
                $attributes = array_filter([
                    'Colour' => $colour !== '' ? $colour : null,
                    'colour_hex' => $colour !== '' && $hex !== '' ? $hex : null,
                    'measurements' => $measurements !== '' ? $measurements : null,
                ]);

                $payload = [
                    'name' => $size !== '' ? $size : $variant->name,
                    'price' => $variantData['price'] ?? $variant?->price ?? $product->price,
                    'mrp' => $variantData['mrp'] ?? $variant?->mrp ?? $product->mrp,
                    'stock_quantity' => $variantData['stock_quantity'] ?? $variant?->stock_quantity ?? 0,
                    'is_active' => filter_var($variantData['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'attributes' => $attributes ?: null,
                ];

                // sku is NOT NULL and unique, so derive one when left blank.
                $sku = trim((string) ($variantData['sku'] ?? ''));
                if ($sku === '') {
                    $sku = $variant?->sku ?: Str::upper(Str::slug(
                        ($product->sku ?: 'P' . $product->id) . '-' . $payload['name'] . ($colour !== '' ? '-' . $colour : '')
                    ));
                }
                $payload['sku'] = $sku;

                if ($variant) {
                    $variant->update($payload);
                } else {
                    $product->variants()->create($payload);
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
                'url' => '/storage/'.$path,
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
                    'media_type' => 'image',
                    'url' => '/storage/'.$path,
                    'is_primary' => false,
                    'position' => $maxPosition + $index + 1,
                ]);
            }
        }

        // Upload new gallery videos
        if ($request->hasFile('videos')) {
            $maxPosition = $product->images()->max('position') ?? 0;
            foreach ($request->file('videos') as $index => $file) {
                $path = $file->store('products/videos', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'media_type' => 'video',
                    'url' => '/storage/'.$path,
                    'is_primary' => false,
                    'position' => $maxPosition + $index + 1,
                ]);
            }
        }

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Is this CSV `image_url` safe for the server to fetch?
     *
     * The importer downloads whatever URL a row names, which makes the server a
     * proxy for whoever wrote the CSV. Two things are required: an http(s)
     * scheme, so file:// and php:// wrappers cannot read local files; and a
     * public destination address, so the cloud metadata endpoint and anything
     * on the private network are out of reach.
     */
    private function isFetchableImageUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return false;
        }

        $host = $parts['host'] ?? '';

        if ($host === '') {
            return false;
        }

        // A literal IP is checked as written; a name is resolved first, because
        // "internal.example.com" can point straight at 10.0.0.1.
        $address = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

        return (bool) filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * A size's MRP is the struck-through price, so it may not sit below what the
     * customer actually pays.
     *
     * `gte:variants.*.price` would express this, but it also fires when the row
     * leaves Price blank - a legitimate case, since a blank price falls back to
     * the product's. This compares the two only when both are filled in.
     */
    private function variantMrpAtLeastPrice(Request $request): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($request): void {
            $index = explode('.', $attribute)[1] ?? null;
            $price = $request->input("variants.{$index}.price");

            if (! is_numeric($price) || ! is_numeric($value)) {
                return;
            }

            if ((float) $value < (float) $price) {
                $fail('The MRP for this size must not be less than its price.');
            }
        };
    }

    /**
     * product_variants.sku is UNIQUE across the whole table, so a row may not
     * take a SKU another row already holds - including a variant of a different
     * product. The row's own id (when it has one) is excluded, otherwise saving
     * a size without touching its SKU would report a clash with itself.
     *
     * A closure rather than `Rule::unique(...)->ignore()`: the id to ignore
     * differs per row, and an array rule cannot vary its parameters by index.
     */
    private function uniqueVariantSku(Request $request): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($request): void {
            $index = explode('.', $attribute)[1] ?? null;
            $variantId = $request->input("variants.{$index}.id");

            $clash = ProductVariant::where('sku', $value)
                ->when($variantId, fn ($query) => $query->whereKeyNot($variantId))
                ->exists();

            if ($clash) {
                $fail("The SKU \"{$value}\" is already used by another size.");
            }
        };
    }

    /**
     * Reorder a product's media (images + videos) from a drag-and-drop list.
     * Mirrors the hero-banners reorder pattern.
     */
    public function reorderImages(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'max:500'],
            'order.*' => ['integer', 'min:1'],
        ]);

        foreach ($data['order'] as $position => $id) {
            ProductImage::where('id', $id)
                ->where('product_id', $product->id)
                ->update(['position' => $position]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        $status = $product->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Product {$status} successfully.");
    }

    public function toggleFeatured(Product $product): RedirectResponse
    {
        $product->update(['is_featured' => ! $product->is_featured]);

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

        $filename = 'products-'.now()->format('Y-m-d-His').'.csv';

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
            'csv_file' => [
                'required',
                'file',
                'extensions:csv,txt',
                'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel',
                'max:10240',
            ],
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return back()->with('error', 'Could not read the uploaded file.');
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return back()->with('error', 'CSV file is empty or has no header row.');
        }

        $header = array_map(fn ($col) => strtolower(trim($col)), $header);

        $requiredColumns = ['name', 'sku', 'price'];
        $missingColumns = array_diff($requiredColumns, $header);
        if (! empty($missingColumns)) {
            fclose($handle);

            return back()->with('error', 'Missing required columns: '.implode(', ', $missingColumns));
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

            // A CSV is just another way to write these columns, but none of the
            // HTTP middleware (TrimStrings, ConvertEmptyStringsToNull) runs on
            // it - so the row is normalised and then put through the same field
            // rules the admin form enforces.
            $blankToNull = static function (?string $value): ?string {
                $value = trim((string) $value);

                return $value === '' ? null : $value;
            };

            $candidate = [
                'name' => $blankToNull($record['name'] ?? null),
                'sku' => $blankToNull($record['sku'] ?? null),
                'slug' => $blankToNull($record['slug'] ?? null),
                'price' => $blankToNull($record['price'] ?? null),
                'sale_price' => $blankToNull($record['sale_price'] ?? null),
                'cost_price' => $blankToNull($record['cost_price'] ?? null),
                'stock_quantity' => $blankToNull($record['stock_quantity'] ?? null),
                'short_description' => $blankToNull($record['short_description'] ?? null),
                'description' => $blankToNull($record['description'] ?? null),
                'meta_title' => $blankToNull($record['meta_title'] ?? null),
                'meta_description' => $blankToNull($record['meta_description'] ?? null),
                'image_url' => $blankToNull($record['image_url'] ?? null),
            ];

            $rowValidator = Validator::make($candidate, [
                'name' => V::text(max: 255, min: 2),
                'sku' => [...self::SKU_RULES, 'required'],
                'slug' => self::SLUG_RULES,
                'price' => V::money(),
                'sale_price' => V::money(required: false),
                'cost_price' => V::money(required: false),
                'stock_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
                'short_description' => V::textarea(required: false, max: 500),
                'description' => ['nullable', 'string', 'max:65535'],
                'meta_title' => V::text(required: false, max: 255),
                'meta_description' => V::textarea(required: false, max: 500),
                'image_url' => V::url(required: false),
            ]);

            if ($rowValidator->fails()) {
                $errors[] = "Row {$row}: ".$rowValidator->errors()->first();
                $skipped++;

                continue;
            }

            $name = $candidate['name'];
            $sku = $candidate['sku'];
            $price = $candidate['price'];

            if (Product::where('sku', $sku)->exists()) {
                $errors[] = "Row {$row}: SKU '{$sku}' already exists.";
                $skipped++;

                continue;
            }

            $categoryId = null;
            if (! empty($record['category'])) {
                $categoryId = $categoriesLower[strtolower(trim($record['category']))] ?? null;
            }

            $sellerId = null;
            if (! empty($record['seller'])) {
                $sellerId = $sellersLower[strtolower(trim($record['seller']))] ?? null;
            }

            $product = Product::create([
                'name' => $name,
                'sku' => $sku,
                'slug' => $candidate['slug'] ?? Str::slug($name),
                'price' => (float) $price,
                'sale_price' => $candidate['sale_price'] !== null ? (float) $candidate['sale_price'] : null,
                'cost_price' => $candidate['cost_price'] !== null ? (float) $candidate['cost_price'] : null,
                'stock_quantity' => (int) ($candidate['stock_quantity'] ?? 0),
                'category_id' => $categoryId,
                'seller_id' => $sellerId,
                'short_description' => $candidate['short_description'],
                'description' => $candidate['description'] ?? $name,
                'is_active' => filter_var($record['is_active'] ?? '1', FILTER_VALIDATE_BOOLEAN),
                'is_featured' => filter_var($record['is_featured'] ?? '0', FILTER_VALIDATE_BOOLEAN),
                'meta_title' => $candidate['meta_title'],
                'meta_description' => $candidate['meta_description'],
            ]);

            // Handle image URL. The row supplies a URL the server then fetches,
            // so the scheme and the host are checked first: FILTER_VALIDATE_URL
            // alone happily passes file:///etc/passwd and http://169.254.169.254,
            // which turned this importer into a file-read and SSRF primitive.
            $imageUrl = $candidate['image_url'];
            if ($imageUrl !== null && $this->isFetchableImageUrl($imageUrl)) {
                try {
                    $imageContents = @file_get_contents($imageUrl, false, stream_context_create([
                        'http' => ['timeout' => 10, 'follow_location' => 0],
                    ]), 0, 5 * 1024 * 1024);

                    // Trust the bytes, not the URL: only store it if GD agrees
                    // it is one of the formats the storefront can render.
                    $info = $imageContents ? @getimagesizefromstring($imageContents) : false;
                    $extension = match ($info['mime'] ?? null) {
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        'image/gif' => 'gif',
                        default => null,
                    };

                    if ($extension !== null) {
                        $path = 'products/'.Str::uuid().'.'.$extension;
                        Storage::disk('public')->put($path, $imageContents);

                        ProductImage::create([
                            'product_id' => $product->id,
                            'url' => asset('storage/'.$path),
                            'is_primary' => true,
                            'position' => 0,
                        ]);
                    }
                } catch (\Exception) {
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

        if (! empty($errors)) {
            $errorSummary = implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $errorSummary .= ' ... and '.(count($errors) - 5).' more.';
            }

            return back()
                ->with('warning', $message)
                ->with('error', $errorSummary);
        }

        return back()->with('success', $message);
    }
}
