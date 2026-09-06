<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportLocalProducts extends Command
{
    protected $signature = 'products:import-local {path : Path to the folder containing Male/Female subfolders}';
    protected $description = 'Import products from local image folder with generated content';

    // Product catalog based on image analysis
    private array $menProducts = [
        ['name' => 'Forest Green Ribbed Knit Polo', 'desc' => 'Premium ribbed knit polo in deep forest green. Features an open collar design and slim fit silhouette. Crafted from soft cotton blend for all-day comfort.', 'category' => 'polos-men', 'color' => 'Green'],
        ['name' => 'Pink Striped Linen Shirt', 'desc' => 'Elegant long-sleeve linen shirt with vertical pink and cream stripes. Button-front design with classic collar. Perfect for summer occasions.', 'category' => 'shirts-men', 'color' => 'Pink'],
        ['name' => 'Cream Knit Open Collar Polo', 'desc' => 'Sophisticated off-white knit polo with buttonless open collar. Short sleeves with refined fit. Ideal for smart-casual styling.', 'category' => 'polos-men', 'color' => 'Cream'],
        ['name' => 'Navy & White Striped Rugby Polo', 'desc' => 'Classic rugby-style polo with bold horizontal navy and cream stripes. Quarter-zip design with contrast white collar. Long sleeves for versatile wear.', 'category' => 'polos-men', 'color' => 'Navy'],
        ['name' => 'Olive Linen Henley Shirt', 'desc' => 'Relaxed olive green linen henley with mandarin collar. Half-button placket with chest pocket. Breathable fabric perfect for warm weather.', 'category' => 'shirts-men', 'color' => 'Olive'],
        ['name' => 'Beige Ribbed Knit T-Shirt', 'desc' => 'Modern ribbed knit t-shirt in warm beige. Crew neck with short sleeves. Muscle fit design that flatters the physique.', 'category' => 't-shirts-men', 'color' => 'Beige'],
        ['name' => 'White Linen Casual Shirt', 'desc' => 'Classic white linen shirt with relaxed fit. Button-front design with rolled-up sleeves. Essential piece for summer wardrobes.', 'category' => 'shirts-men', 'color' => 'White'],
        ['name' => 'Sky Blue Cotton Polo', 'desc' => 'Fresh sky blue cotton polo with ribbed collar. Short sleeves with classic fit. Soft breathable fabric for everyday comfort.', 'category' => 'polos-men', 'color' => 'Blue'],
        ['name' => 'Charcoal Grey Knit T-Shirt', 'desc' => 'Sleek charcoal grey knit t-shirt with subtle texture. Crew neck design with regular fit. Versatile piece for layering or solo wear.', 'category' => 't-shirts-men', 'color' => 'Grey'],
        ['name' => 'Tan Linen Blend Shirt', 'desc' => 'Sophisticated tan linen blend shirt with button-front closure. Long sleeves with barrel cuffs. Refined texture for elevated casual style.', 'category' => 'shirts-men', 'color' => 'Tan'],
        ['name' => 'Navy Blue Textured Polo', 'desc' => 'Deep navy blue polo with textured knit fabric. Open collar design with short sleeves. Premium cotton blend for refined comfort.', 'category' => 'polos-men', 'color' => 'Navy'],
        ['name' => 'Sage Green Linen Shirt', 'desc' => 'Soft sage green linen shirt with relaxed silhouette. Button-down collar with chest pocket. Natural fabric that gets better with wear.', 'category' => 'shirts-men', 'color' => 'Green'],
        ['name' => 'Oatmeal Cable Knit Sweater', 'desc' => 'Cozy oatmeal cable knit sweater with crew neck. Long sleeves with ribbed cuffs and hem. Premium wool blend for warmth and style.', 'category' => 'sweaters-men', 'color' => 'Oatmeal'],
        ['name' => 'Burgundy Slim Fit Polo', 'desc' => 'Rich burgundy polo with slim fit design. Ribbed collar and cuffs with two-button placket. Cotton piqué fabric for classic appeal.', 'category' => 'polos-men', 'color' => 'Burgundy'],
        ['name' => 'Stone Grey Linen Henley', 'desc' => 'Understated stone grey linen henley shirt. Mandarin collar with half-button placket. Lightweight and breathable for hot days.', 'category' => 'shirts-men', 'color' => 'Grey'],
        ['name' => 'Ivory Textured Knit T-Shirt', 'desc' => 'Elegant ivory t-shirt with subtle textured knit. Crew neck with short sleeves. Premium cotton for exceptional softness.', 'category' => 't-shirts-men', 'color' => 'Ivory'],
    ];

    private array $womenProducts = [
        ['name' => 'White Cotton Mini Dress with Belt', 'desc' => 'Chic white cotton mini dress featuring mandarin collar and short sleeves. Flattering pleated skirt with self-tie belt at waist. Perfect for brunch dates and summer outings.', 'category' => 'dresses-women', 'color' => 'White'],
        ['name' => 'White Linen Shirt Dress', 'desc' => 'Effortlessly elegant white linen shirt dress with classic collar. Button-front design with rolled sleeves. Pairs beautifully with a leather belt for defined silhouette.', 'category' => 'dresses-women', 'color' => 'White'],
        ['name' => 'Beige Linen Shirt Dress', 'desc' => 'Sophisticated beige linen shirt dress with relaxed fit. Button-front closure with classic collar. Versatile piece that transitions from day to evening.', 'category' => 'dresses-women', 'color' => 'Beige'],
        ['name' => 'Natural Linen Co-ord Set', 'desc' => 'Refined two-piece linen co-ord in natural beige. Button-front shirt with matching wide-leg trousers. Effortless coordination for polished summer style.', 'category' => 'co-ord-sets-women', 'color' => 'Beige'],
        ['name' => 'Blush Pink Linen Shirt', 'desc' => 'Soft blush pink linen shirt with classic collar. Long sleeves with button-front closure. Breathable fabric for comfortable elegance.', 'category' => 'shirts-women', 'color' => 'Pink'],
        ['name' => 'Emerald Green Embroidered Blouse', 'desc' => 'Stunning emerald green blouse with delicate floral embroidery on cuffs. V-neck design with three-quarter sleeves. Statement piece for special occasions.', 'category' => 'blouses', 'color' => 'Green'],
        ['name' => 'Beige Linen Maxi Dress', 'desc' => 'Timeless beige linen maxi dress with shirt collar. Button placket with three-quarter sleeves. Flowing silhouette perfect for resort wear.', 'category' => 'dresses-women', 'color' => 'Beige'],
        ['name' => 'White Wide-Leg Linen Trousers', 'desc' => 'Elegant white wide-leg trousers in premium linen. High-waist design with side pockets. Relaxed fit for effortless summer chic.', 'category' => 'trousers-women', 'color' => 'White'],
        ['name' => 'Cream Silk Blend Blouse', 'desc' => 'Luxurious cream silk blend blouse with subtle sheen. V-neck with delicate pleating. Sophisticated choice for office to evening transitions.', 'category' => 'blouses', 'color' => 'Cream'],
        ['name' => 'Dusty Rose Wrap Dress', 'desc' => 'Flattering dusty rose wrap dress with tie waist. V-neck with flutter sleeves. Soft fabric that drapes beautifully on all body types.', 'category' => 'dresses-women', 'color' => 'Pink'],
        ['name' => 'Sage Green Linen Top', 'desc' => 'Fresh sage green linen top with relaxed fit. Crew neck with short sleeves. Natural fabric that feels cool and comfortable all day.', 'category' => 'tops-women', 'color' => 'Green'],
        ['name' => 'Ivory Pleated Midi Skirt', 'desc' => 'Elegant ivory pleated midi skirt with satin finish. High-waist design with concealed zipper. Timeless piece for sophisticated styling.', 'category' => 'skirts-women', 'color' => 'Ivory'],
        ['name' => 'Terracotta Linen Shift Dress', 'desc' => 'Warm terracotta linen shift dress with minimalist design. Round neck with short sleeves. Easy-wear silhouette for everyday elegance.', 'category' => 'dresses-women', 'color' => 'Terracotta'],
        ['name' => 'Navy Striped Cotton Top', 'desc' => 'Classic navy and white striped cotton top. Boat neck with three-quarter sleeves. Nautical-inspired design for casual sophistication.', 'category' => 'tops-women', 'color' => 'Navy'],
        ['name' => 'Lavender Linen Blouse', 'desc' => 'Delicate lavender linen blouse with feminine details. Peter Pan collar with button-back closure. Romantic piece for spring and summer.', 'category' => 'blouses', 'color' => 'Lavender'],
        ['name' => 'Olive Wide-Leg Trousers', 'desc' => 'Sophisticated olive green wide-leg trousers. High-waist design with pleated front. Flattering fit that elongates the silhouette.', 'category' => 'trousers-women', 'color' => 'Olive'],
        ['name' => 'Champagne Satin Cami Top', 'desc' => 'Luxurious champagne satin cami top with adjustable straps. V-neck with delicate lace trim. Perfect for layering or evening wear.', 'category' => 'tops-women', 'color' => 'Champagne'],
        ['name' => 'Camel Knit Cardigan', 'desc' => 'Cozy camel knit cardigan with button-front closure. Long sleeves with ribbed cuffs. Soft wool blend for lightweight warmth.', 'category' => 'sweaters-women', 'color' => 'Camel'],
        ['name' => 'White Broderie Anglaise Dress', 'desc' => 'Romantic white broderie anglaise dress with intricate eyelet detailing. Puff sleeves with tiered skirt. Summer essential for garden parties.', 'category' => 'dresses-women', 'color' => 'White'],
        ['name' => 'Stone Linen Palazzo Pants', 'desc' => 'Flowing stone-colored linen palazzo pants. Elasticated waist with wide flared legs. Ultimate comfort meets effortless style.', 'category' => 'trousers-women', 'color' => 'Stone'],
        ['name' => 'Coral Sleeveless Blouse', 'desc' => 'Vibrant coral sleeveless blouse with feminine ruffle details. V-neck with button-front closure. Fresh pop of color for summer wardrobes.', 'category' => 'blouses', 'color' => 'Coral'],
        ['name' => 'Ecru Linen Jumpsuit', 'desc' => 'Effortless ecru linen jumpsuit with wide-leg silhouette. Button-front with self-tie belt. One-piece elegance for any occasion.', 'category' => 'co-ord-sets-women', 'color' => 'Ecru'],
        ['name' => 'Powder Blue Cotton Dress', 'desc' => 'Sweet powder blue cotton dress with A-line silhouette. Round neck with cap sleeves. Lightweight fabric perfect for warm days.', 'category' => 'dresses-women', 'color' => 'Blue'],
        ['name' => 'Rust Wrap Top', 'desc' => 'Flattering rust-colored wrap top with tie closure. V-neck with three-quarter sleeves. Versatile piece that pairs with any bottom.', 'category' => 'tops-women', 'color' => 'Rust'],
    ];

    public function handle(): int
    {
        $basePath = $this->argument('path');
        
        if (!File::isDirectory($basePath)) {
            $this->error("Directory not found: {$basePath}");
            return 1;
        }

        $malePath = $basePath . DIRECTORY_SEPARATOR . 'Male';
        $femalePath = $basePath . DIRECTORY_SEPARATOR . 'Female';

        // Ensure storage directory exists
        Storage::disk('public')->makeDirectory('products/karmaa-collection');

        $imported = 0;

        // Import male products
        if (File::isDirectory($malePath)) {
            $maleImages = File::files($malePath);
            $this->info("Found " . count($maleImages) . " male product images");
            
            foreach ($maleImages as $index => $file) {
                if ($index >= count($this->menProducts)) break;
                
                $product = $this->menProducts[$index];
                $result = $this->importProduct($file, $product, 60); // MEN category ID
                if ($result) $imported++;
            }
        }

        // Import female products
        if (File::isDirectory($femalePath)) {
            $femaleImages = File::files($femalePath);
            $this->info("Found " . count($femaleImages) . " female product images");
            
            foreach ($femaleImages as $index => $file) {
                if ($index >= count($this->womenProducts)) break;
                
                $product = $this->womenProducts[$index];
                $result = $this->importProduct($file, $product, 61); // WOMEN category ID
                if ($result) $imported++;
            }
        }

        $this->info("✓ Successfully imported {$imported} products");
        return 0;
    }

    private function importProduct($file, array $productData, int $parentCategoryId): bool
    {
        $productName = $productData['name'];
        
        // Check if product already exists (idempotent)
        $existingSlug = Str::slug($productName);
        if (Product::where('slug', 'like', $existingSlug . '%')->exists()) {
            $this->warn("Skipping (already exists): {$productName}");
            return false;
        }

        // Find the subcategory
        $category = Category::where('slug', $productData['category'])->first();
        if (!$category) {
            // Fallback to parent category
            $category = Category::find($parentCategoryId);
        }

        // Generate random price between 3000-4000
        $price = rand(3000, 4000);
        $mrp = $price + rand(500, 1500); // MRP is higher

        // Copy image to storage
        $extension = $file->getExtension();
        $newFilename = Str::slug($productName) . '-' . Str::random(4) . '.' . $extension;
        $storagePath = 'products/karmaa-collection/' . $newFilename;
        
        Storage::disk('public')->put($storagePath, File::get($file->getPathname()));

        // Create product
        DB::beginTransaction();
        try {
            $product = Product::create([
                'name' => $productName,
                'slug' => Str::slug($productName) . '-' . Str::random(4),
                'short_description' => Str::limit($productData['desc'], 200),
                'description' => '<p>' . $productData['desc'] . '</p><p><strong>Color:</strong> ' . $productData['color'] . '</p><p><strong>Material:</strong> Premium quality fabric</p><p><strong>Care:</strong> Machine wash cold, tumble dry low</p>',
                'category_id' => $category->id,
                'sku' => 'KK-' . strtoupper(Str::random(8)),
                'price' => $price,
                'mrp' => $mrp,
                'stock_quantity' => rand(10, 50),
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_featured' => rand(0, 1) === 1,
                'status' => 'approved',
                'published_at' => now(),
                'attributes' => [
                    'Color' => $productData['color'],
                    'Material' => 'Premium Fabric',
                ],
            ]);

            // Create product image
            ProductImage::create([
                'product_id' => $product->id,
                'url' => $storagePath,
                'media_type' => 'image',
                'alt_text' => $productName,
                'position' => 0,
                'is_primary' => true,
            ]);

            DB::commit();
            $this->info("✓ Imported: {$productName} (₹{$price})");
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to import {$productName}: " . $e->getMessage());
            return false;
        }
    }
}
