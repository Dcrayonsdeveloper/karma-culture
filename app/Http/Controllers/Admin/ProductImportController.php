<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImportController extends Controller
{
    /**
     * Show the import page
     */
    public function index()
    {
        $categories = Category::with('parent')->get();
        $stats = [
            'total_products' => Product::count(),
            'total_images' => ProductImage::count(),
            'total_categories' => Category::count(),
        ];
        
        return view('admin.products.import', [
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }
    
    /**
     * Fetch products from House of Rare API
     */
    public function fetchProducts(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 50);
        
        $url = "https://thehouseofrare.com/products.json?limit={$limit}&page={$page}";
        
        $response = $this->fetchWithCurl($url);
        if (!$response) {
            return response()->json(['error' => 'Failed to fetch products'], 500);
        }
        
        $data = json_decode($response, true);
        $products = $data['products'] ?? [];
        
        // Add category suggestions based on product type/tags
        foreach ($products as &$product) {
            $product['suggested_category'] = $this->suggestCategory(
                $product['product_type'] ?? '',
                $product['tags'] ?? [],
                $product['title'] ?? ''
            );
            $product['already_exists'] = Product::where('name', $product['title'])->exists();
        }
        
        return response()->json([
            'products' => $products,
            'page' => $page,
            'has_more' => count($products) === $limit,
        ]);
    }
    
    /**
     * Import selected products
     */
    public function importProducts(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required',
            'products.*.title' => 'required|string',
            'products.*.category_id' => 'required|integer',
        ]);
        
        $products = $request->input('products');
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($products as $shopifyProduct) {
            // Skip if already exists
            if (Product::where('name', $shopifyProduct['title'])->exists()) {
                $skipped++;
                continue;
            }
            
            try {
                DB::beginTransaction();
                
                $product = $this->createProduct($shopifyProduct);
                $this->downloadImages($product, $shopifyProduct['images'] ?? []);
                $this->createVariants($product, $shopifyProduct['variants'] ?? []);
                
                DB::commit();
                $imported++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = [
                    'title' => $shopifyProduct['title'],
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return response()->json([
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }
    
    /**
     * Create product from Shopify data
     */
    private function createProduct(array $data): Product
    {
        $firstVariant = $data['variants'][0] ?? [];
        $price = (float)($firstVariant['price'] ?? 0);
        $comparePrice = (float)($firstVariant['compare_at_price'] ?? 0);
        $mrp = $comparePrice > $price ? $comparePrice : $price;
        $sku = $firstVariant['sku'] ?? 'HOR-' . Str::random(8);
        
        // Ensure unique SKU
        while (Product::where('sku', $sku)->exists()) {
            $sku = 'HOR-' . Str::random(8);
        }
        
        $seoData = [
            'meta_title' => Str::limit($data['title'], 60),
            'meta_description' => Str::limit(strip_tags($data['body_html'] ?? ''), 160),
        ];
        
        return Product::create([
            'uuid' => (string) Str::uuid(),
            'name' => $data['title'],
            'slug' => Str::slug($data['title']) . '-' . Str::random(4),
            'description' => $data['body_html'] ?? '',
            'short_description' => Str::limit(strip_tags($data['body_html'] ?? ''), 200),
            'category_id' => $data['category_id'],
            'sku' => $sku,
            'mrp' => $mrp,
            'price' => $price,
            'stock_quantity' => 100,
            'stock_status' => 'in_stock',
            'is_active' => true,
            'is_taxable' => true,
            'tax_rate' => 18.00,
            'status' => 'approved',
            'seo_data' => $seoData,
            'published_at' => now(),
        ]);
    }
    
    /**
     * Download and save product images
     */
    private function downloadImages(Product $product, array $images): void
    {
        $storageDir = 'products/imported';
        
        foreach ($images as $index => $image) {
            $imageUrl = $image['src'] ?? '';
            if (!$imageUrl) continue;
            
            // Remove query params
            $imageUrl = preg_replace('/\?.*/', '', $imageUrl);
            
            $imageContent = $this->fetchWithCurl($imageUrl);
            if (!$imageContent || strlen($imageContent) < 1000) {
                continue;
            }
            
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = Str::slug($product->name) . '-' . ($index + 1) . '-' . Str::random(6) . '.' . $extension;
            $path = "{$storageDir}/{$filename}";
            
            Storage::disk('public')->put($path, $imageContent);
            
            ProductImage::create([
                'product_id' => $product->id,
                'url' => $path,
                'alt_text' => $product->name,
                'position' => $index,
                'is_primary' => $index === 0,
            ]);
        }
    }
    
    /**
     * Create product variants
     */
    private function createVariants(Product $product, array $variants): void
    {
        if (count($variants) <= 1) {
            return;
        }
        
        foreach ($variants as $index => $variant) {
            $variantTitle = $variant['title'] ?? 'Default';
            if ($variantTitle === 'Default Title') {
                continue;
            }
            
            $variantSku = $variant['sku'] ?: $product->sku . '-V' . $index;
            
            // Ensure unique variant SKU
            while (ProductVariant::where('sku', $variantSku)->exists()) {
                $variantSku = $product->sku . '-V' . $index . '-' . Str::random(4);
            }
            
            ProductVariant::create([
                'product_id' => $product->id,
                'name' => $variantTitle,
                'sku' => $variantSku,
                'mrp' => (float)($variant['compare_at_price'] ?? $variant['price'] ?? $product->mrp),
                'price' => (float)($variant['price'] ?? $product->price),
                'stock_quantity' => (int)($variant['inventory_quantity'] ?? 100),
                'is_active' => true,
            ]);
        }
    }
    
    /**
     * Suggest category based on product data
     */
    private function suggestCategory(string $productType, array $tags, string $title): ?int
    {
        $searchText = strtolower($productType . ' ' . implode(' ', $tags) . ' ' . $title);
        
        $isWomen = str_contains($searchText, 'women') || 
                   str_contains($searchText, 'rareism') || 
                   str_contains($searchText, 'womens');
        
        $isKids = str_contains($searchText, 'kids') || 
                  str_contains($searchText, 'rare ones') ||
                  str_contains($searchText, 'boy') ||
                  str_contains($searchText, 'girl');
        
        $keywords = [
            'polo' => 'Polos',
            't-shirt' => 'T-Shirts',
            'tshirt' => 'T-Shirts',
            'shirt' => 'Shirts',
            'dress' => 'Dresses',
            'top' => 'Tops',
            'jeans' => 'Jeans',
            'trouser' => 'Trousers',
            'shorts' => 'Shorts',
            'jacket' => 'Jackets',
            'hoodie' => 'Hoodies',
            'sweater' => 'Sweaters',
        ];
        
        $categoryName = null;
        foreach ($keywords as $keyword => $catName) {
            if (str_contains($searchText, $keyword)) {
                $categoryName = $catName;
                break;
            }
        }
        
        if (!$categoryName) {
            $categoryName = $isWomen ? 'Tops' : ($isKids ? 'Boys T-Shirts' : 'Shirts');
        }
        
        // Find parent based on gender
        $parentName = $isWomen ? 'WOMEN' : ($isKids ? 'KIDS' : 'MEN');
        $parent = Category::where('name', $parentName)->first();
        
        if ($parent) {
            $category = Category::where('name', $categoryName)
                ->where('parent_id', $parent->id)
                ->first();
            
            if ($category) {
                return $category->id;
            }
        }
        
        // Fallback to parent
        return $parent?->id ?? Category::first()?->id;
    }
    
    /**
     * Fetch URL with cURL
     */
    private function fetchWithCurl(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept: application/json,text/html,application/xhtml+xml',
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response ?: null;
    }
    
    /**
     * Create categories from House of Rare structure
     */
    public function createCategories()
    {
        $structure = [
            'MEN' => ['Shirts', 'T-Shirts', 'Polos', 'Sweaters', 'Hoodies', 'Jackets', 'Blazers', 'Co-ord Sets', 'Trousers', 'Jeans', 'Chinos', 'Shorts', 'Track Pants', 'Footwear', 'Bags', 'Innerwear'],
            'WOMEN' => ['Tops', 'Shirts', 'T-Shirts', 'Sweaters', 'Hoodies', 'Jackets', 'Dresses', 'Co-ord Sets', 'Trousers', 'Jeans', 'Skirts', 'Shorts', 'Track Pants', 'Bags'],
            'KIDS' => ['Boys T-Shirts', 'Boys Shirts', 'Boys Polos', 'Boys Shorts', 'Boys Trousers', 'Boys Co-ord Sets', 'Girls Dresses', 'Girls Tops'],
        ];
        
        $created = 0;
        
        foreach ($structure as $parentName => $children) {
            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($parentName)],
                ['name' => $parentName, 'is_active' => true]
            );
            
            foreach ($children as $childName) {
                $existing = Category::where('name', $childName)
                    ->where('parent_id', $parent->id)
                    ->first();
                    
                if (!$existing) {
                    Category::create([
                        'name' => $childName,
                        'slug' => Str::slug($childName) . '-' . Str::lower($parentName),
                        'parent_id' => $parent->id,
                        'is_active' => true,
                    ]);
                    $created++;
                }
            }
        }
        
        return response()->json([
            'message' => "Created {$created} categories",
            'categories' => Category::with('children')->whereNull('parent_id')->get(),
        ]);
    }
}
