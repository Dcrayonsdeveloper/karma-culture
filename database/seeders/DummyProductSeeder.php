<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DummyProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Setting up categories, brands, seller...');

        // Ensure storage link exists
        if (!file_exists(public_path('storage'))) {
            $this->command->call('storage:link');
        }

        // Create product images directory
        $imageDir = storage_path('app/public/products');
        if (!File::exists($imageDir)) {
            File::makeDirectory($imageDir, 0755, true);
        }

        // --- Create fashion-relevant categories ---
        $categoryData = [
            [
                'name' => 'Men',
                'description' => 'Curated fashion for men',
                'icon' => 'shirt',
                'children' => [
                    ['name' => 'Shirts', 'description' => 'Formal & casual shirts'],
                    ['name' => 'T-Shirts', 'description' => 'Round neck, polo & henley'],
                    ['name' => 'Trousers', 'description' => 'Chinos, jeans & formal'],
                    ['name' => 'Kurtas', 'description' => 'Ethnic & festive wear'],
                    ['name' => 'Jackets', 'description' => 'Blazers, bombers & denim'],
                ],
            ],
            [
                'name' => 'Women',
                'description' => 'Curated fashion for women',
                'icon' => 'sparkles',
                'children' => [
                    ['name' => 'Dresses', 'description' => 'Maxi, midi & mini dresses'],
                    ['name' => 'Tops', 'description' => 'Blouses, crop tops & tunics'],
                    ['name' => 'Kurtis', 'description' => 'Ethnic & indo-western'],
                    ['name' => 'Skirts', 'description' => 'A-line, pleated & wrap'],
                    ['name' => 'Jackets', 'description' => 'Blazers & overcoats'],
                ],
            ],
            [
                'name' => 'Accessories',
                'description' => 'Complete your look',
                'icon' => 'gift',
                'children' => [
                    ['name' => 'Bags', 'description' => 'Totes, slings & clutches'],
                    ['name' => 'Watches', 'description' => 'Analog & smart watches'],
                    ['name' => 'Sunglasses', 'description' => 'Aviators, wayfarers & more'],
                    ['name' => 'Jewellery', 'description' => 'Rings, bracelets & necklaces'],
                ],
            ],
            [
                'name' => 'Footwear',
                'description' => 'Step out in style',
                'icon' => 'trophy',
                'children' => [
                    ['name' => 'Sneakers', 'description' => 'Casual & sport sneakers'],
                    ['name' => 'Formal Shoes', 'description' => 'Oxford, derby & loafers'],
                    ['name' => 'Sandals', 'description' => 'Flats, heels & slides'],
                ],
            ],
        ];

        // Clear existing categories if empty products
        if (Product::count() === 0 && Category::count() > 0) {
            Category::query()->delete();
        }

        $position = 1;
        foreach ($categoryData as $catData) {
            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($catData['name'])],
                [
                    'name' => $catData['name'],
                    'description' => $catData['description'],
                    'icon' => $catData['icon'] ?? null,
                    'position' => $position++,
                    'is_active' => true,
                    'is_featured' => true,
                ]
            );

            if (!empty($catData['children'])) {
                $childPos = 1;
                foreach ($catData['children'] as $childData) {
                    Category::firstOrCreate(
                        ['slug' => Str::slug($childData['name'])],
                        [
                            'parent_id' => $parent->id,
                            'name' => $childData['name'],
                            'description' => $childData['description'],
                            'position' => $childPos++,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        // --- Create luxury fashion brands ---
        $brands = [
            ['name' => 'Karma Culture', 'description' => 'Our signature label', 'is_featured' => true],
            ['name' => 'Aurelia', 'description' => 'Contemporary ethnic wear', 'is_featured' => true],
            ['name' => 'Raymond', 'description' => 'The Complete Man', 'is_featured' => true],
            ['name' => 'FabIndia', 'description' => 'Handcrafted excellence', 'is_featured' => true],
            ['name' => 'Biba', 'description' => 'Celebrating Indian fashion', 'is_featured' => true],
            ['name' => 'Allen Solly', 'description' => 'Smart casual wear', 'is_featured' => false],
            ['name' => 'W for Woman', 'description' => 'Modern Indian wear', 'is_featured' => false],
            ['name' => 'Manyavar', 'description' => 'Festive ethnic wear', 'is_featured' => false],
        ];

        if (Brand::count() === 0) {
            $bPos = 1;
            foreach ($brands as $b) {
                Brand::create([
                    'name' => $b['name'],
                    'slug' => Str::slug($b['name']),
                    'description' => $b['description'],
                    'is_active' => true,
                    'is_featured' => $b['is_featured'],
                    'position' => $bPos++,
                ]);
            }
        }

        // --- Create seller if none exists ---
        if (Seller::count() === 0) {
            $user = User::firstOrCreate(
                ['email' => 'seller@karmaculture.com'],
                [
                    'first_name' => 'Karma',
                    'last_name' => 'Culture',
                    'password' => Hash::make('password'),
                    'role' => 'seller',
                    'is_verified' => true,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            Seller::create([
                'user_id' => $user->id,
                'store_name' => 'Karma Culture Official',
                'business_name' => 'Karma Culture Pvt Ltd',
                'slug' => 'karma-culture-official',
                'store_description' => 'Curated fashion for the modern individual.',
                'description' => 'Curated fashion for the modern individual.',
                'status' => 'approved',
                'commission_rate' => 10,
                'available_balance' => 0,
                'pending_balance' => 0,
                'email_notifications' => true,
                'order_notifications' => true,
                'review_notifications' => true,
                'approved_at' => now(),
            ]);
        }

        // --- Create inventory location if none ---
        $location = InventoryLocation::first();
        if (!$location) {
            $location = InventoryLocation::create([
                'name' => 'Main Warehouse',
                'code' => 'WH-MAIN',
                'type' => 'warehouse',
                'address' => '123 Fashion Street',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'postal_code' => '400001',
                'is_active' => true,
            ]);
        }

        $seller = Seller::first();
        $allBrands = Brand::all();
        $childCategories = Category::whereNotNull('parent_id')->where('is_active', true)->get();

        // --- Product data with Picsum image IDs ---
        $products = [
            // Men's Shirts
            ['name' => 'Classic White Oxford Shirt', 'cat' => 'Shirts', 'mrp' => 2999, 'price' => 1999, 'stock' => 80, 'desc' => 'A timeless white oxford shirt crafted from premium cotton. Perfect for both formal and smart-casual occasions.', 'images' => [1011, 1012, 1013]],
            ['name' => 'Midnight Navy Slim Fit Shirt', 'cat' => 'Shirts', 'mrp' => 2499, 'price' => 1799, 'stock' => 65, 'desc' => 'Elegantly tailored navy shirt with a modern slim fit. Features mother-of-pearl buttons.', 'images' => [1014, 1015, 1016]],
            ['name' => 'Sage Green Linen Shirt', 'cat' => 'Shirts', 'mrp' => 3499, 'price' => 2499, 'stock' => 45, 'desc' => 'Breathable linen shirt in a soothing sage green. Ideal for summer occasions.', 'images' => [1018, 1019, 1020]],
            ['name' => 'Pinstripe Formal Shirt', 'cat' => 'Shirts', 'mrp' => 2799, 'price' => 2099, 'stock' => 55, 'desc' => 'Classic pinstripe pattern on premium cotton. A boardroom essential.', 'images' => [1021, 1022, 1023]],

            // Men's T-Shirts
            ['name' => 'Essential Crew Neck Tee - Ivory', 'cat' => 'T-Shirts', 'mrp' => 1299, 'price' => 899, 'stock' => 120, 'desc' => 'Soft organic cotton crew neck in warm ivory. Your everyday essential.', 'images' => [1024, 1025, 1026]],
            ['name' => 'Premium Polo - Charcoal', 'cat' => 'T-Shirts', 'mrp' => 1799, 'price' => 1299, 'stock' => 90, 'desc' => 'Pique cotton polo with ribbed collar. Understated elegance for any occasion.', 'images' => [1027, 1028, 1029]],
            ['name' => 'Henley Full Sleeve - Olive', 'cat' => 'T-Shirts', 'mrp' => 1599, 'price' => 1199, 'stock' => 70, 'desc' => 'Relaxed henley in olive green with wooden buttons. Effortlessly cool.', 'images' => [1031, 1032, 1033]],
            ['name' => 'Striped V-Neck Tee', 'cat' => 'T-Shirts', 'mrp' => 1499, 'price' => 999, 'stock' => 110, 'desc' => 'Nautical-inspired stripe tee in soft jersey cotton. Perfect weekend wear.', 'images' => [1035, 1036, 1037]],

            // Men's Trousers
            ['name' => 'Tailored Chinos - Sand', 'cat' => 'Trousers', 'mrp' => 2999, 'price' => 2199, 'stock' => 60, 'desc' => 'Perfectly tailored chinos in warm sand. Versatile enough for office to evening.', 'images' => [1038, 1039, 1040]],
            ['name' => 'Slim Fit Dark Wash Jeans', 'cat' => 'Trousers', 'mrp' => 3499, 'price' => 2499, 'stock' => 75, 'desc' => 'Premium selvedge denim in a refined dark wash. Built to last.', 'images' => [1041, 1042, 1043]],

            // Men's Kurtas
            ['name' => 'Embroidered Silk Kurta - Gold', 'cat' => 'Kurtas', 'mrp' => 5999, 'price' => 4499, 'stock' => 30, 'desc' => 'Luxurious silk kurta with intricate gold threadwork. Perfect for celebrations.', 'images' => [1044, 1045, 1046]],
            ['name' => 'Cotton Kurta - Natural White', 'cat' => 'Kurtas', 'mrp' => 2499, 'price' => 1799, 'stock' => 50, 'desc' => 'Handspun cotton kurta in pristine white. Comfort meets tradition.', 'images' => [1047, 1048, 1049]],

            // Men's Jackets
            ['name' => 'Wool Blend Blazer - Charcoal', 'cat' => 'Jackets', 'mrp' => 7999, 'price' => 5999, 'stock' => 25, 'desc' => 'Italian-inspired wool blend blazer with peak lapels. Sophisticated and sharp.', 'images' => [1050, 1051, 1052]],
            ['name' => 'Suede Bomber Jacket - Tan', 'cat' => 'Jackets', 'mrp' => 6999, 'price' => 4999, 'stock' => 20, 'desc' => 'Butter-soft suede bomber in warm tan. A statement piece for autumn.', 'images' => [1053, 1054, 1055]],

            // Women's Dresses
            ['name' => 'Floral Maxi Dress - Blush', 'cat' => 'Dresses', 'mrp' => 4999, 'price' => 3499, 'stock' => 40, 'desc' => 'Flowing maxi dress with delicate floral print. Feminine and graceful.', 'images' => [1056, 1057, 1058]],
            ['name' => 'Wrap Midi Dress - Emerald', 'cat' => 'Dresses', 'mrp' => 3999, 'price' => 2999, 'stock' => 35, 'desc' => 'Flattering wrap silhouette in rich emerald green. Perfect for dinner dates.', 'images' => [1059, 1060, 1061]],
            ['name' => 'Silk Slip Dress - Champagne', 'cat' => 'Dresses', 'mrp' => 5499, 'price' => 3999, 'stock' => 25, 'desc' => 'Luxurious silk slip dress with delicate lace trim. Effortless elegance.', 'images' => [1062, 1063, 1064]],

            // Women's Tops
            ['name' => 'Ruffle Sleeve Blouse - Cream', 'cat' => 'Tops', 'mrp' => 2499, 'price' => 1799, 'stock' => 55, 'desc' => 'Romantic ruffle sleeve blouse in soft cream georgette. Day to night versatility.', 'images' => [1065, 1066, 1067]],
            ['name' => 'Satin Camisole - Black', 'cat' => 'Tops', 'mrp' => 1999, 'price' => 1499, 'stock' => 65, 'desc' => 'Sleek satin camisole with adjustable straps. A wardrobe essential.', 'images' => [1068, 1069, 1070]],

            // Women's Kurtis
            ['name' => 'Block Print Kurti - Indigo', 'cat' => 'Kurtis', 'mrp' => 2799, 'price' => 1999, 'stock' => 45, 'desc' => 'Handblock printed kurti in traditional indigo. Artisanal craftsmanship.', 'images' => [1071, 1072, 1073]],
            ['name' => 'Embellished Anarkali - Rose', 'cat' => 'Kurtis', 'mrp' => 4999, 'price' => 3499, 'stock' => 30, 'desc' => 'Stunning rose-pink anarkali with zari embellishment. Festive glamour.', 'images' => [1074, 1075, 1076]],

            // Women's Skirts
            ['name' => 'Pleated Midi Skirt - Burgundy', 'cat' => 'Skirts', 'mrp' => 2999, 'price' => 2199, 'stock' => 40, 'desc' => 'Elegant pleated midi in deep burgundy. Pairs beautifully with tucked-in blouses.', 'images' => [1077, 1078, 1079]],

            // Women's Jackets
            ['name' => 'Tailored Blazer - Ivory', 'cat' => 'Jackets', 'mrp' => 5999, 'price' => 4499, 'stock' => 30, 'desc' => 'Crisp ivory blazer with gold-tone buttons. Power dressing redefined.', 'images' => [1080, 1081, 1082]],

            // Accessories - Bags
            ['name' => 'Leather Tote - Cognac', 'cat' => 'Bags', 'mrp' => 4999, 'price' => 3499, 'stock' => 35, 'desc' => 'Full-grain leather tote in rich cognac. Spacious and structured.', 'images' => [1083, 1084, 1085]],
            ['name' => 'Crossbody Sling - Olive', 'cat' => 'Bags', 'mrp' => 2499, 'price' => 1799, 'stock' => 50, 'desc' => 'Compact crossbody sling with gold hardware. Hands-free chic.', 'images' => [1069, 1070, 1071]],

            // Accessories - Watches
            ['name' => 'Minimalist Watch - Rose Gold', 'cat' => 'Watches', 'mrp' => 6999, 'price' => 4999, 'stock' => 25, 'desc' => 'Clean dial with rose gold case and Italian leather strap. Timeless.', 'images' => [1072, 1073, 1074]],

            // Accessories - Sunglasses
            ['name' => 'Classic Aviator - Gold Frame', 'cat' => 'Sunglasses', 'mrp' => 3999, 'price' => 2799, 'stock' => 60, 'desc' => 'Iconic aviator silhouette with polarized lenses and gold frame.', 'images' => [1075, 1076, 1077]],

            // Accessories - Jewellery
            ['name' => 'Gold Plated Chain Necklace', 'cat' => 'Jewellery', 'mrp' => 2999, 'price' => 1999, 'stock' => 45, 'desc' => '18K gold plated chain necklace. Subtle luxury for everyday wear.', 'images' => [1078, 1079, 1080]],

            // Footwear - Sneakers
            ['name' => 'White Leather Sneakers', 'cat' => 'Sneakers', 'mrp' => 4999, 'price' => 3499, 'stock' => 70, 'desc' => 'Clean white leather sneakers with cushioned insole. Walk in comfort.', 'images' => [1081, 1082, 1083]],
            ['name' => 'Canvas Low-Top - Navy', 'cat' => 'Sneakers', 'mrp' => 2499, 'price' => 1799, 'stock' => 85, 'desc' => 'Classic canvas sneakers in maritime navy. Weekend-ready.', 'images' => [1084, 1085, 1011]],

            // Footwear - Formal
            ['name' => 'Oxford Brogues - Brown', 'cat' => 'Formal Shoes', 'mrp' => 5999, 'price' => 4499, 'stock' => 30, 'desc' => 'Hand-burnished leather brogues with Goodyear welt construction.', 'images' => [1012, 1013, 1014]],

            // Footwear - Sandals
            ['name' => 'Leather Slides - Tan', 'cat' => 'Sandals', 'mrp' => 1999, 'price' => 1499, 'stock' => 55, 'desc' => 'Minimalist leather slides with contoured footbed. Resort-ready.', 'images' => [1015, 1016, 1018]],
        ];

        $this->command->info('Downloading product images...');
        $downloadedImages = [];
        $imageIds = [];
        foreach ($products as $p) {
            foreach ($p['images'] as $id) {
                $imageIds[$id] = true;
            }
        }

        $bar = $this->command->getOutput()->createProgressBar(count($imageIds));
        $bar->start();

        foreach (array_keys($imageIds) as $picId) {
            $filename = "product-{$picId}.jpg";
            $filepath = $imageDir . '/' . $filename;

            if (File::exists($filepath)) {
                $downloadedImages[$picId] = 'products/' . $filename;
                $bar->advance();
                continue;
            }

            try {
                $response = Http::timeout(15)->withOptions(['allow_redirects' => true])->get("https://picsum.photos/id/{$picId}/600/800");
                if ($response->successful()) {
                    File::put($filepath, $response->body());
                    $downloadedImages[$picId] = 'products/' . $filename;
                }
            } catch (\Exception $e) {
                // Use a fallback color image
                $this->createPlaceholderImage($filepath, $picId);
                $downloadedImages[$picId] = 'products/' . $filename;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Creating products...');

        foreach ($products as $index => $pData) {
            $catSlug = Str::slug($pData['cat']);
            $category = $childCategories->firstWhere('slug', $catSlug);
            if (!$category) {
                $this->command->warn("Category '{$pData['cat']}' not found, skipping: {$pData['name']}");
                continue;
            }

            $product = Product::create([
                'uuid' => Str::uuid(),
                'seller_id' => $seller->id,
                'category_id' => $category->id,
                'brand_id' => $allBrands->random()->id,
                'name' => $pData['name'],
                'slug' => Str::slug($pData['name']),
                'short_description' => Str::limit($pData['desc'], 100),
                'description' => '<p>' . $pData['desc'] . '</p><p>Crafted with care using premium materials. Designed for comfort and style.</p>',
                'sku' => 'KC-' . strtoupper(Str::random(8)),
                'mrp' => $pData['mrp'],
                'price' => $pData['price'],
                'cost_price' => round($pData['price'] * 0.5, 2),
                'stock_quantity' => $pData['stock'],
                'low_stock_threshold' => 10,
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_featured' => $index < 10,
                'is_taxable' => true,
                'tax_rate' => 12,
                'rating' => rand(38, 50) / 10,
                'review_count' => rand(5, 200),
                'view_count' => rand(50, 3000),
                'sales_count' => rand(10, 500),
                'status' => 'approved',
                'published_at' => now()->subDays(rand(1, 60)),
            ]);

            // Create product images
            foreach ($pData['images'] as $imgIndex => $picId) {
                if (isset($downloadedImages[$picId])) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'url' => $downloadedImages[$picId],
                        'alt_text' => $pData['name'],
                        'position' => $imgIndex,
                        'is_primary' => $imgIndex === 0,
                    ]);
                }
            }

            // Create size variants for clothing
            if (in_array($pData['cat'], ['Shirts', 'T-Shirts', 'Trousers', 'Kurtas', 'Dresses', 'Tops', 'Kurtis', 'Skirts', 'Jackets'])) {
                $sizes = ['S', 'M', 'L', 'XL'];
                foreach ($sizes as $size) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => $pData['name'] . ' - ' . $size,
                        'sku' => $product->sku . '-' . $size,
                        'stock_quantity' => rand(5, 30),
                        'attributes' => ['Size' => $size],
                        'is_active' => true,
                    ]);
                }
            } elseif (in_array($pData['cat'], ['Sneakers', 'Formal Shoes', 'Sandals'])) {
                $sizes = ['7', '8', '9', '10', '11'];
                foreach ($sizes as $size) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => $pData['name'] . ' - UK ' . $size,
                        'sku' => $product->sku . '-UK' . $size,
                        'stock_quantity' => rand(3, 15),
                        'attributes' => ['Size' => 'UK ' . $size],
                        'is_active' => true,
                    ]);
                }
            }

            // The product's stock already landed on the default shelf when it
            // was created (see TracksWarehouseStock); a second row here would
            // count the same units twice, because MySQL lets NULL variant ids
            // past the unique index.
            InventoryStock::where('product_id', $product->id)
                ->whereNull('variant_id')
                ->where('location_id', $location->id)
                ->update([
                    'quantity' => $pData['stock'],
                    'reserved_quantity' => 0,
                    'available_quantity' => $pData['stock'],
                ]);
        }

        $this->command->info('Created ' . count($products) . ' products with images, variants, and inventory.');
    }

    private function createPlaceholderImage(string $filepath, int $seed): void
    {
        $colors = ['#f5f2ec', '#e8e3da', '#d2bd8a', '#ab8c52', '#967a45', '#7d6639'];
        $color = $colors[$seed % count($colors)];
        $r = hexdec(substr($color, 1, 2));
        $g = hexdec(substr($color, 3, 2));
        $b = hexdec(substr($color, 5, 2));

        $img = imagecreatetruecolor(600, 800);
        $bgColor = imagecolorallocate($img, $r, $g, $b);
        imagefill($img, 0, 0, $bgColor);

        $textColor = imagecolorallocate($img, 255, 255, 255);
        $text = 'KARMA CULTURE';
        $fontSize = 4;
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $x = (600 - $textWidth) / 2;
        $y = 390;
        imagestring($img, $fontSize, (int)$x, (int)$y, $text, $textColor);

        imagejpeg($img, $filepath, 85);
        imagedestroy($img);
    }
}
