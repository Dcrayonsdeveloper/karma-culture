<?php
/**
 * House of Rare Product Importer - Enhanced Category Mapping
 * Uses tags, product_type, and title to properly assign to subcategories
 * Run on server: php import-houseofrare.php
 */

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

echo "=== House of Rare Product Importer (Enhanced) ===\n\n";

// Step 1: Create Category Structure with comprehensive keywords from tags
echo "Step 1: Creating category structure...\n";
$categoryStructure = [
    'MEN' => [
        'Shirts' => ['keywords' => ['shirt', 'formal shirt', 'casual shirt', 'linen shirt', 'cotton shirt', 'oxford', 'button-down']],
        'T-Shirts' => ['keywords' => ['t-shirt', 'tshirt', 'tee', 'crew neck', 'round neck', 'graphic tee']],
        'Polos' => ['keywords' => ['polo', 'polo shirt', 'collared t-shirt', 'pique']],
        'Sweaters' => ['keywords' => ['sweater', 'pullover', 'knit', 'cardigan', 'knitwear', 'woolen']],
        'Hoodies' => ['keywords' => ['hoodie', 'sweatshirt', 'hooded', 'fleece', 'zipup']],
        'Jackets' => ['keywords' => ['jacket', 'bomber', 'windbreaker', 'puffer', 'quilted', 'leather jacket', 'denim jacket']],
        'Blazers' => ['keywords' => ['blazer', 'sport coat', 'suit jacket']],
        'Co-ord Sets' => ['keywords' => ['co-ord', 'coord', 'set', 'matching set', 'twin set']],
        'Trousers' => ['keywords' => ['trouser', 'pant', 'chino', 'formal pant', 'cotton pant', 'linen pant', 'slim fit pant']],
        'Jeans' => ['keywords' => ['jeans', 'denim', 'denim pant']],
        'Shorts' => ['keywords' => ['shorts', 'bermuda', 'casual shorts', 'cotton shorts']],
        'Track Pants' => ['keywords' => ['track', 'jogger', 'sweatpant', 'athleisure', 'pajama', 'lounge']],
        'Kurtas' => ['keywords' => ['kurta', 'ethnic', 'traditional', 'festive']],
        'Activewear' => ['keywords' => ['activewear', 'sportswear', 'gym', 'workout', 'athletic']],
    ],
    'WOMEN' => [
        'Tops' => ['keywords' => ['top', 'blouse', 'crop top', 'tank', 'cami', 'peplum', 'tunic']],
        'Shirts' => ['keywords' => ['shirt', 'button-down', 'formal shirt', 'oxford']],
        'T-Shirts' => ['keywords' => ['t-shirt', 'tshirt', 'tee', 'graphic tee', 'basic tee']],
        'Dresses' => ['keywords' => ['dress', 'kaftan', 'maxi', 'midi', 'mini dress', 'gown', 'jumpsuit', 'romper', 'playsuit', 'bodycon']],
        'Sweaters' => ['keywords' => ['sweater', 'pullover', 'knit', 'cardigan', 'knitwear', 'woolen']],
        'Hoodies' => ['keywords' => ['hoodie', 'sweatshirt', 'hooded', 'fleece']],
        'Jackets' => ['keywords' => ['jacket', 'bomber', 'puffer', 'quilted', 'leather jacket', 'denim jacket', 'shrug']],
        'Blazers' => ['keywords' => ['blazer', 'formal jacket']],
        'Co-ord Sets' => ['keywords' => ['co-ord', 'coord', 'set', 'matching set', 'twin set']],
        'Trousers' => ['keywords' => ['trouser', 'pant', 'palazzo', 'wide leg', 'formal pant', 'cotton pant']],
        'Jeans' => ['keywords' => ['jeans', 'denim', 'jegging']],
        'Skirts' => ['keywords' => ['skirt', 'mini skirt', 'midi skirt', 'maxi skirt', 'a-line']],
        'Shorts' => ['keywords' => ['shorts', 'hot pants', 'denim shorts']],
        'Leggings' => ['keywords' => ['legging', 'jegging', 'tregging', 'tights']],
        'Kurtis' => ['keywords' => ['kurti', 'kurta', 'ethnic top']],
        'Sarees' => ['keywords' => ['saree', 'sari']],
        'Lehengas' => ['keywords' => ['lehenga', 'chaniya choli', 'ghagra']],
    ],
    'KIDS' => [
        'Boys T-Shirts' => ['keywords' => ['t-shirt', 'tshirt', 'tee'], 'gender' => 'boy'],
        'Boys Shirts' => ['keywords' => ['shirt'], 'gender' => 'boy'],
        'Boys Shorts' => ['keywords' => ['shorts'], 'gender' => 'boy'],
        'Boys Trousers' => ['keywords' => ['trouser', 'pant', 'jeans', 'jogger'], 'gender' => 'boy'],
        'Boys Hoodies' => ['keywords' => ['hoodie', 'sweatshirt', 'jacket'], 'gender' => 'boy'],
        'Girls Dresses' => ['keywords' => ['dress', 'frock', 'gown'], 'gender' => 'girl'],
        'Girls Tops' => ['keywords' => ['top', 't-shirt', 'tee', 'blouse'], 'gender' => 'girl'],
        'Girls Skirts' => ['keywords' => ['skirt'], 'gender' => 'girl'],
    ],
];

$categoryMap = [];
foreach ($categoryStructure as $parentName => $children) {
    $parent = Category::firstOrCreate(
        ['slug' => Str::slug($parentName)],
        ['name' => $parentName, 'is_active' => true]
    );
    $categoryMap[$parentName] = ['id' => $parent->id, 'children' => []];
    
    foreach ($children as $childName => $config) {
        $child = Category::firstOrCreate(
            ['slug' => Str::slug($childName) . '-' . Str::lower($parentName), 'parent_id' => $parent->id],
            ['name' => $childName, 'is_active' => true]
        );
        $categoryMap[$parentName]['children'][$childName] = [
            'id' => $child->id,
            'keywords' => $config['keywords'],
            'gender' => $config['gender'] ?? null,
        ];
    }
}
echo "Categories created/verified.\n\n";

// Step 2: Fetch all products from API
echo "Step 2: Fetching products from House of Rare API...\n";
$allProducts = [];
$page = 1;
$limit = 250;

while (true) {
    $url = "https://thehouseofrare.com/products.json?limit={$limit}&page={$page}";
    echo "  Fetching page {$page}... ";
    
    $response = fetchWithCurl($url);
    if (!$response) {
        echo "Failed to fetch.\n";
        break;
    }
    
    $data = json_decode($response, true);
    $products = $data['products'] ?? [];
    
    if (empty($products)) {
        echo "No more products.\n";
        break;
    }
    
    echo count($products) . " products\n";
    $allProducts = array_merge($allProducts, $products);
    
    if (count($products) < $limit) {
        break;
    }
    $page++;
}

echo "Total products fetched: " . count($allProducts) . "\n\n";

// Step 3: Import products with 50 per subcategory limit
echo "Step 3: Importing products (max 50 per subcategory)...\n";
$imported = 0;
$skipped = 0;
$errors = [];
$categoryStats = [];
$MAX_PER_CATEGORY = 50;

foreach ($allProducts as $index => $shopifyProduct) {
    $title = $shopifyProduct['title'] ?? '';
    
    // Skip if already exists
    if (Product::where('name', $title)->exists()) {
        $skipped++;
        continue;
    }
    
    // Determine category using enhanced logic FIRST (before checking limit)
    $categoryResult = determineCategoryIdEnhanced($shopifyProduct, $categoryMap);
    $categoryId = $categoryResult['id'];
    $categoryName = $categoryResult['name'];
    $parentName = $categoryResult['parent'] ?? 'Unknown';
    $fullCategoryName = "{$parentName} > {$categoryName}";
    
    // Initialize category counter
    if (!isset($categoryStats[$fullCategoryName])) {
        $categoryStats[$fullCategoryName] = 0;
    }
    
    // Skip if category already has 50 products
    if ($categoryStats[$fullCategoryName] >= $MAX_PER_CATEGORY) {
        continue;
    }
    
    echo "  [{$index}] Importing: " . Str::limit($title, 45) . "... ";
    
    try {
        DB::beginTransaction();
        
        // Create product
        $product = createProduct($shopifyProduct, $categoryId);
        
        // Download and save images
        $imageCount = downloadImages($product, $shopifyProduct['images'] ?? []);
        
        // Create variants
        createVariants($product, $shopifyProduct['variants'] ?? []);
        
        DB::commit();
        $imported++;
        $categoryStats[$fullCategoryName]++;
        echo "OK ({$imageCount} imgs) -> {$fullCategoryName} [{$categoryStats[$fullCategoryName]}/50]\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        $errors[] = ['title' => $title, 'error' => $e->getMessage()];
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    
    // Small delay to avoid overwhelming the server
    usleep(50000); // 50ms
    
    // Check if all categories are full
    $allFull = true;
    foreach ($categoryMap as $parent => $data) {
        foreach ($data['children'] as $childName => $childData) {
            $key = "{$parent} > {$childName}";
            if (!isset($categoryStats[$key]) || $categoryStats[$key] < $MAX_PER_CATEGORY) {
                $allFull = false;
                break 2;
            }
        }
    }
    if ($allFull) {
        echo "\n*** All categories have reached 50 products. Stopping import. ***\n";
        break;
    }
}

echo "\n=== Import Complete ===\n";
echo "Imported: {$imported}\n";
echo "Skipped (already exists): {$skipped}\n";
echo "Errors: " . count($errors) . "\n";

echo "\n=== Category Distribution ===\n";
arsort($categoryStats);
foreach ($categoryStats as $cat => $count) {
    echo "  {$cat}: {$count}\n";
}

if (!empty($errors)) {
    echo "\nError details (first 10):\n";
    foreach (array_slice($errors, 0, 10) as $err) {
        echo "  - {$err['title']}: {$err['error']}\n";
    }
}

// Helper Functions

function fetchWithCurl(string $url): ?string
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response ?: null;
}

function determineCategoryIdEnhanced(array $product, array $categoryMap): array
{
    $title = strtolower($product['title'] ?? '');
    $productType = strtolower($product['product_type'] ?? '');
    $vendor = strtolower($product['vendor'] ?? '');
    $tags = $product['tags'] ?? [];
    $tagsLower = array_map('strtolower', $tags);
    $tagsString = implode(' ', $tagsLower);
    
    // Combined search text
    $searchText = $title . ' ' . $productType . ' ' . $tagsString . ' ' . $vendor;
    
    // Step 1: Determine Gender/Audience (MEN, WOMEN, KIDS)
    $parentKey = determineParent($searchText, $title, $vendor, $tagsLower);
    
    // Step 2: Find best matching subcategory based on tags and product type
    $bestMatch = null;
    $bestScore = 0;
    
    foreach ($categoryMap[$parentKey]['children'] as $catName => $catConfig) {
        $score = 0;
        
        // For KIDS, check gender match first
        if ($parentKey === 'KIDS' && isset($catConfig['gender'])) {
            $genderKeyword = $catConfig['gender'];
            if (!str_contains($searchText, $genderKeyword)) {
                continue; // Skip if gender doesn't match
            }
            $score += 5; // Bonus for gender match
        }
        
        // Score based on keyword matches
        foreach ($catConfig['keywords'] as $keyword) {
            // Check in product_type (highest priority)
            if (str_contains($productType, $keyword)) {
                $score += 10;
            }
            // Check in tags
            if (str_contains($tagsString, $keyword)) {
                $score += 5;
            }
            // Check in title
            if (str_contains($title, $keyword)) {
                $score += 3;
            }
        }
        
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = ['id' => $catConfig['id'], 'name' => $catName, 'parent' => $parentKey];
        }
    }
    
    // If no good match found, use product_type mapping
    if (!$bestMatch || $bestScore < 3) {
        $bestMatch = mapByProductType($productType, $parentKey, $categoryMap);
        if ($bestMatch) {
            $bestMatch['parent'] = $parentKey;
        }
    }
    
    // Fallback to first subcategory if still no match
    if (!$bestMatch) {
        $firstChild = reset($categoryMap[$parentKey]['children']);
        $firstChildName = array_key_first($categoryMap[$parentKey]['children']);
        $bestMatch = ['id' => $firstChild['id'], 'name' => $firstChildName, 'parent' => $parentKey];
    }
    
    return $bestMatch;
}

function determineParent(string $searchText, string $title, string $vendor, array $tags): string
{
    // Kids detection (most specific first)
    $kidsIndicators = ['kids', 'kid', 'rare ones', 'rareones', 'children', 'child'];
    foreach ($kidsIndicators as $indicator) {
        if (str_contains($searchText, $indicator)) {
            return 'KIDS';
        }
    }
    
    // Check title explicitly for "Men's" or "Women's" (most reliable)
    if (preg_match("/\bmen'?s?\b/i", $title)) {
        return 'MEN';
    }
    if (preg_match("/\bwomen'?s?\b/i", $title)) {
        return 'WOMEN';
    }
    
    // Women detection by brand
    if ($vendor === 'rareism' || str_contains($vendor, 'rareism')) {
        return 'WOMEN';
    }
    
    // Women detection by keywords
    $womenIndicators = ['women', 'woman', 'female', 'ladies', 'her '];
    foreach ($womenIndicators as $indicator) {
        if (str_contains($searchText, $indicator)) {
            return 'WOMEN';
        }
    }
    
    // Check tags for gender
    foreach ($tags as $tag) {
        if (in_array($tag, ['women', 'woman', 'female', 'ladies', "women's"])) {
            return 'WOMEN';
        }
        if (in_array($tag, ['kids', 'children', 'boys', 'girls'])) {
            return 'KIDS';
        }
        if (in_array($tag, ['men', 'man', 'male', "men's"])) {
            return 'MEN';
        }
    }
    
    // Men detection by brand
    if ($vendor === 'rare rabbit' || str_contains($vendor, 'rabbit') || str_contains($vendor, 'rarez')) {
        return 'MEN';
    }
    
    // Default to MEN
    return 'MEN';
}

function mapByProductType(string $productType, string $parentKey, array $categoryMap): ?array
{
    // Comprehensive product type to category mapping
    $typeMapping = [
        // Tops
        'polo' => 'Polos',
        'polo shirt' => 'Polos',
        't-shirt' => 'T-Shirts',
        'tshirt' => 'T-Shirts',
        'tee' => 'T-Shirts',
        'shirt' => 'Shirts',
        'top' => 'Tops',
        'blouse' => 'Tops',
        
        // Dresses & Jumpsuits
        'dress' => 'Dresses',
        'gown' => 'Dresses',
        'jumpsuit' => 'Dresses',
        'romper' => 'Dresses',
        
        // Outerwear
        'jacket' => 'Jackets',
        'blazer' => 'Blazers',
        'sweater' => 'Sweaters',
        'cardigan' => 'Sweaters',
        'hoodie' => 'Hoodies',
        'sweatshirt' => 'Hoodies',
        
        // Bottoms
        'trouser' => 'Trousers',
        'pant' => 'Trousers',
        'chino' => 'Trousers',
        'jeans' => 'Jeans',
        'denim' => 'Jeans',
        'shorts' => 'Shorts',
        'skirt' => 'Skirts',
        'legging' => 'Leggings',
        'jogger' => 'Track Pants',
        'track pant' => 'Track Pants',
        'pajama' => 'Track Pants',
        
        // Sets
        'co-ord' => 'Co-ord Sets',
        'coord' => 'Co-ord Sets',
        'set' => 'Co-ord Sets',
        
        // Ethnic
        'kurta' => $parentKey === 'WOMEN' ? 'Kurtis' : 'Kurtas',
        'kurti' => 'Kurtis',
        'saree' => 'Sarees',
        'lehenga' => 'Lehengas',
        
        // Accessories fallback
        'footwear' => 'Shirts', // fallback
        'accessory' => 'Shirts', // fallback
        'shoe' => 'Shirts', // fallback
        'bag' => 'Shirts', // fallback
    ];
    
    foreach ($typeMapping as $type => $catName) {
        if (str_contains($productType, $type)) {
            // Check if this category exists in the parent
            if (isset($categoryMap[$parentKey]['children'][$catName])) {
                return [
                    'id' => $categoryMap[$parentKey]['children'][$catName]['id'],
                    'name' => $catName
                ];
            }
            // Try alternate category name for same parent
            $alternates = [
                'Tops' => ['Shirts', 'T-Shirts'],
                'Shirts' => ['Tops', 'T-Shirts'],
                'Track Pants' => ['Trousers'],
                'Kurtis' => ['Kurtas', 'Tops'],
            ];
            if (isset($alternates[$catName])) {
                foreach ($alternates[$catName] as $alt) {
                    if (isset($categoryMap[$parentKey]['children'][$alt])) {
                        return [
                            'id' => $categoryMap[$parentKey]['children'][$alt]['id'],
                            'name' => $alt
                        ];
                    }
                }
            }
        }
    }
    
    return null;
}

function createProduct(array $data, int $categoryId): Product
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
    
    // Clean description
    $description = $data['body_html'] ?? '';
    $shortDescription = Str::limit(strip_tags($description), 200);
    
    // Calculate stock
    $totalStock = 0;
    $hasStock = false;
    foreach ($data['variants'] ?? [] as $variant) {
        if ($variant['available'] ?? false) {
            $hasStock = true;
            $totalStock += 10; // Default stock per available size
        }
    }
    
    $seoData = [
        'meta_title' => Str::limit($data['title'], 60),
        'meta_description' => Str::limit(strip_tags($description), 160),
    ];
    
    return Product::create([
        'uuid' => (string) Str::uuid(),
        'name' => $data['title'],
        'slug' => Str::slug($data['title']) . '-' . Str::random(4),
        'description' => $description,
        'short_description' => $shortDescription,
        'category_id' => $categoryId,
        'sku' => $sku,
        'mrp' => $mrp,
        'price' => $price,
        'stock_quantity' => $totalStock ?: 100,
        'stock_status' => $hasStock ? 'in_stock' : 'out_of_stock',
        'is_active' => true,
        'is_taxable' => true,
        'tax_rate' => 18.00,
        'status' => 'approved',
        'seo_data' => $seoData,
        'published_at' => now(),
    ]);
}

function downloadImages(Product $product, array $images): int
{
    $storageDir = 'products/imported';
    $count = 0;
    
    foreach ($images as $index => $image) {
        $imageUrl = $image['src'] ?? '';
        if (!$imageUrl) continue;
        
        // Remove query params for cleaner URL
        $imageUrl = preg_replace('/\?.*/', '', $imageUrl);
        
        // Download image
        $imageContent = fetchWithCurl($imageUrl);
        if (!$imageContent || strlen($imageContent) < 1000) {
            continue;
        }
        
        // Determine extension
        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'webp';
        $filename = Str::slug($product->name) . '-' . ($index + 1) . '-' . Str::random(6) . '.' . $extension;
        $path = "{$storageDir}/{$filename}";
        
        // Save to storage
        Storage::disk('public')->put($path, $imageContent);
        
        // Create database record
        ProductImage::create([
            'product_id' => $product->id,
            'url' => $path,
            'alt_text' => $product->name,
            'position' => $index,
            'is_primary' => $index === 0,
        ]);
        
        $count++;
        
        // Limit to 6 images per product
        if ($count >= 6) break;
    }
    
    return $count;
}

function createVariants(Product $product, array $variants): void
{
    // Skip if only one variant (default)
    if (count($variants) <= 1) {
        return;
    }
    
    foreach ($variants as $index => $variant) {
        $variantTitle = $variant['title'] ?? 'Default';
        if ($variantTitle === 'Default Title') {
            continue;
        }
        
        // Parse size and color
        $size = $variant['option1'] ?? null;
        $color = $variant['option2'] ?? null;
        
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
            'stock_quantity' => ($variant['available'] ?? false) ? 10 : 0,
            'is_active' => true,
        ]);
    }
}
