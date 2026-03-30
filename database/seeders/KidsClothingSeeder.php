<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KidsClothingSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Cleaning existing data...');
        $this->cleanData();

        $this->command->info('Seeding settings...');
        $this->seedSettings();

        $this->command->info('Seeding brands...');
        $this->seedBrands();

        $this->command->info('Seeding categories...');
        $this->seedCategories();

        $this->command->info('Seeding products...');
        $this->seedProducts();

        $this->command->info('Seeding homepage sections...');
        $this->seedHomepageSections();

        $this->command->info('Seeding testimonials...');
        $this->seedTestimonials();

        $this->command->info('Done! Kids clothing data seeded successfully.');
    }

    private function cleanData(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        ProductImage::truncate();
        Product::withTrashed()->forceDelete();
        Category::truncate();
        Brand::truncate();
        Testimonial::truncate();
        HomepageSection::truncate();
        Banner::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function seedSettings(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'ForeverKids', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'site_tagline', 'value' => 'Adorable Clothing for Little Ones', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'site_description', 'value' => 'Trendy, comfortable kids\' clothing for boys and girls. Shop the cutest outfits for every occasion.', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'footer_about', 'value' => 'ForeverKids is your one-stop shop for adorable, comfortable, and stylish kids\' clothing. From everyday basics to festive outfits, we dress your little ones in joy.', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'footer_copyright', 'value' => 'ForeverKids. All rights reserved.', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'social_facebook', 'value' => 'https://facebook.com/foreverkids', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'social_instagram', 'value' => 'https://instagram.com/foreverkids', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'social_twitter', 'value' => 'https://x.com/foreverkids', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'social_youtube', 'value' => 'https://youtube.com/@foreverkids', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'social_tiktok', 'value' => '', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'social_pinterest', 'value' => 'https://pinterest.com/foreverkids', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'contact_email', 'value' => 'hello@foreverkids.in', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'contact_phone', 'value' => '+91 98765 43210', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'contact_address', 'value' => '123 Fashion Street, Andheri West, Mumbai, Maharashtra 400058', 'type' => 'string', 'group' => 'homepage'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }

    private function seedBrands(): void
    {
        $brands = [
            ['name' => 'ForeverKids', 'description' => 'Our signature kids\' clothing line', 'is_featured' => true],
            ['name' => 'Little Munchkins', 'description' => 'Playful everyday wear for active kids', 'is_featured' => true],
            ['name' => 'TinyTrend', 'description' => 'Fashion-forward outfits for stylish kids', 'is_featured' => true],
            ['name' => 'CuddleBug', 'description' => 'Ultra-soft organic cotton basics', 'is_featured' => true],
            ['name' => 'Desi Kids', 'description' => 'Beautiful ethnic and festive wear', 'is_featured' => true],
            ['name' => 'KidStreet', 'description' => 'Cool casual and streetwear for kids', 'is_featured' => false],
        ];

        foreach ($brands as $b) {
            Brand::create(array_merge($b, ['is_active' => true]));
        }
    }

    private function seedCategories(): void
    {
        // ── Shop for Girls (root + 6 children) ──
        $girls = Category::create([
            'name' => 'Shop for Girls',
            'description' => 'Beautiful clothing collection for girls aged 0-14 years',
            'icon' => 'girls',
            'position' => 1,
            'is_active' => true,
            'is_featured' => true,
            'image_url' => 'https://placehold.co/680x400/FFB6C1/8B004A?text=Shop+for+Girls',
        ]);

        $girlsChildren = [
            ['name' => 'Frocks & Dresses', 'description' => 'Everyday and party dresses for girls', 'icon' => 'dress', 'position' => 1, 'image_url' => 'https://placehold.co/400x400/FADADD/8B004A?text=Frocks'],
            ['name' => 'Lehenga Sets', 'description' => 'Festive lehenga choli sets for girls', 'icon' => 'lehenga', 'position' => 2, 'image_url' => 'https://placehold.co/400x400/FFF0F5/8B004A?text=Lehenga+Sets'],
            ['name' => 'Sharara Sets', 'description' => 'Trendy sharara sets for celebrations', 'icon' => 'sharara', 'position' => 3, 'image_url' => 'https://placehold.co/400x400/FFE4E1/8B004A?text=Sharara+Sets'],
            ['name' => 'Tops & T-Shirts', 'description' => 'Casual tops and tees for girls', 'icon' => 'top', 'position' => 4, 'image_url' => 'https://placehold.co/400x400/E8D5E0/8B004A?text=Tops'],
            ['name' => 'Skirts & Shorts', 'description' => 'Skirts, shorts, and bottoms for girls', 'icon' => 'skirt', 'position' => 5, 'image_url' => 'https://placehold.co/400x400/F5E6F0/8B004A?text=Skirts'],
            ['name' => 'Nightwear', 'description' => 'Cozy pajama sets and nightgowns', 'icon' => 'night', 'position' => 6, 'image_url' => 'https://placehold.co/400x400/E6E0F3/8B004A?text=Nightwear'],
        ];

        foreach ($girlsChildren as $child) {
            Category::create(array_merge($child, [
                'parent_id' => $girls->id,
                'is_active' => true,
                'is_featured' => false,
            ]));
        }

        // ── Shop for Boys (root + 6 children) ──
        $boys = Category::create([
            'name' => 'Shop for Boys',
            'description' => 'Smart and stylish clothing for boys aged 0-14 years',
            'icon' => 'boys',
            'position' => 2,
            'is_active' => true,
            'is_featured' => true,
            'image_url' => 'https://placehold.co/680x400/B0D4E8/1B3A5C?text=Shop+for+Boys',
        ]);

        $boysChildren = [
            ['name' => 'T-Shirts & Shirts', 'description' => 'Casual and formal shirts for boys', 'icon' => 'shirt', 'position' => 1, 'image_url' => 'https://placehold.co/400x400/D4E8F0/1B3A5C?text=T-Shirts'],
            ['name' => 'Kurta Sets', 'description' => 'Traditional kurta pajama sets for boys', 'icon' => 'kurta', 'position' => 2, 'image_url' => 'https://placehold.co/400x400/E0EEF5/1B3A5C?text=Kurta+Sets'],
            ['name' => 'Jeans & Trousers', 'description' => 'Comfortable jeans and trousers', 'icon' => 'jeans', 'position' => 3, 'image_url' => 'https://placehold.co/400x400/C8DDE8/1B3A5C?text=Jeans'],
            ['name' => 'Sherwani Sets', 'description' => 'Royal sherwani sets for celebrations', 'icon' => 'sherwani', 'position' => 4, 'image_url' => 'https://placehold.co/400x400/D8E8F0/1B3A5C?text=Sherwani'],
            ['name' => 'Shorts & Bermudas', 'description' => 'Casual shorts for everyday play', 'icon' => 'shorts', 'position' => 5, 'image_url' => 'https://placehold.co/400x400/BFD8E8/1B3A5C?text=Shorts'],
            ['name' => 'Nightwear', 'description' => 'Comfortable pajama sets for boys', 'icon' => 'night', 'position' => 6, 'image_url' => 'https://placehold.co/400x400/C5D9E8/1B3A5C?text=Nightwear'],
        ];

        foreach ($boysChildren as $child) {
            Category::create(array_merge($child, [
                'parent_id' => $boys->id,
                'is_active' => true,
                'is_featured' => false,
            ]));
        }

        // ── Baby Clothing (root + 4 children) ──
        $baby = Category::create([
            'name' => 'Baby Clothing',
            'description' => 'Soft and cozy clothing for babies 0-2 years',
            'icon' => 'baby',
            'position' => 3,
            'is_active' => true,
            'is_featured' => true,
            'image_url' => 'https://placehold.co/680x400/FFF8DC/8B6914?text=Baby+Clothing',
        ]);

        $babyChildren = [
            ['name' => 'Rompers & Bodysuits', 'description' => 'Easy-wear rompers and onesies', 'icon' => 'romper', 'position' => 1, 'image_url' => 'https://placehold.co/400x400/FFFACD/8B6914?text=Rompers'],
            ['name' => 'Baby Sets', 'description' => 'Coordinated clothing sets for babies', 'icon' => 'set', 'position' => 2, 'image_url' => 'https://placehold.co/400x400/FFF8DC/8B6914?text=Baby+Sets'],
            ['name' => 'Bibs & Accessories', 'description' => 'Essential baby accessories', 'icon' => 'bib', 'position' => 3, 'image_url' => 'https://placehold.co/400x400/FFEFD5/8B6914?text=Bibs'],
            ['name' => 'Swaddles & Blankets', 'description' => 'Soft muslin swaddles and blankets', 'icon' => 'blanket', 'position' => 4, 'image_url' => 'https://placehold.co/400x400/FFF5E1/8B6914?text=Swaddles'],
        ];

        foreach ($babyChildren as $child) {
            Category::create(array_merge($child, [
                'parent_id' => $baby->id,
                'is_active' => true,
                'is_featured' => false,
            ]));
        }
    }

    private function seedProducts(): void
    {
        $categories = Category::pluck('id', 'name');
        $brands = Brand::pluck('id', 'name');

        $products = [
            // ── Girls: Frocks & Dresses ──
            ['name' => 'Floral Print Cotton Frock', 'short_description' => 'Lightweight cotton frock with floral print', 'description' => 'A beautiful floral frock made from 100% breathable cotton. Features an A-line silhouette, back button closure, and comfortable fit. Perfect for everyday wear and casual outings. Available for ages 2-8 years.', 'category' => 'Frocks & Dresses', 'brand' => 'ForeverKids', 'price' => 699, 'mrp' => 999, 'is_featured' => true, 'sales_count' => 1850, 'img_bg' => 'FFE4EC', 'img_fg' => 'C1539C'],
            ['name' => 'Sequin Party Dress - Pink', 'short_description' => 'Sparkly sequin dress for special occasions', 'description' => 'Let your little princess shine in this gorgeous sequin party dress. Features a tulle underskirt, satin lining, and back zip closure. Perfect for birthdays, weddings, and festive celebrations.', 'category' => 'Frocks & Dresses', 'brand' => 'TinyTrend', 'price' => 1299, 'mrp' => 1899, 'is_featured' => true, 'sales_count' => 1240, 'img_bg' => 'FFC0CB', 'img_fg' => '8B004A'],
            ['name' => 'Denim Pinafore Dress', 'short_description' => 'Trendy denim pinafore for everyday style', 'description' => 'A trendy denim pinafore dress with adjustable straps and front pockets. Pair it with a t-shirt or top for a cute layered look. Made from soft stretch denim for all-day comfort.', 'category' => 'Frocks & Dresses', 'brand' => 'KidStreet', 'price' => 849, 'mrp' => 1199, 'is_featured' => false, 'sales_count' => 920, 'img_bg' => 'E8EDF2', 'img_fg' => '3D5A80'],
            ['name' => 'Ruffled Sleeve A-Line Dress', 'short_description' => 'Cute ruffled sleeve cotton dress', 'description' => 'An adorable A-line dress with statement ruffled sleeves and contrasting piping. Made from soft breathable cotton in a cheerful yellow color. Great for playdate and school wear.', 'category' => 'Frocks & Dresses', 'brand' => 'Little Munchkins', 'price' => 599, 'mrp' => 799, 'is_featured' => false, 'sales_count' => 680, 'img_bg' => 'FFF9C4', 'img_fg' => 'F57F17'],

            // ── Girls: Lehenga Sets ──
            ['name' => 'Embroidered Lehenga Choli - Red', 'short_description' => 'Festive lehenga choli with gold embroidery', 'description' => 'A stunning lehenga choli set in rich red with intricate gold embroidery. Includes a matching dupatta with lace border. Made from comfortable georgette with cotton lining. Perfect for Diwali, weddings, and celebrations.', 'category' => 'Lehenga Sets', 'brand' => 'Desi Kids', 'price' => 1599, 'mrp' => 2299, 'is_featured' => true, 'sales_count' => 1560, 'img_bg' => 'FFE0E0', 'img_fg' => 'C62828'],
            ['name' => 'Mirror Work Lehenga - Teal', 'short_description' => 'Traditional mirror work lehenga set', 'description' => 'A gorgeous teal lehenga with traditional mirror work embellishments. Features a crop top choli and flared skirt with matching dupatta. Ideal for Navratri garba and festive functions.', 'category' => 'Lehenga Sets', 'brand' => 'Desi Kids', 'price' => 1899, 'mrp' => 2699, 'is_featured' => false, 'sales_count' => 890, 'img_bg' => 'E0F2F1', 'img_fg' => '00695C'],

            // ── Girls: Sharara Sets ──
            ['name' => 'Printed Sharara Set - Lavender', 'short_description' => 'Elegant printed sharara set for festive wear', 'description' => 'A beautiful lavender sharara set with delicate block print pattern. Includes a short kurti top, flared sharara pants, and a matching dupatta. Lightweight and comfortable for long celebrations.', 'category' => 'Sharara Sets', 'brand' => 'Desi Kids', 'price' => 1499, 'mrp' => 2199, 'is_featured' => true, 'sales_count' => 1120, 'img_bg' => 'E8D5F5', 'img_fg' => '6A1B9A'],

            // ── Girls: Tops ──
            ['name' => 'Striped Puff Sleeve Top', 'short_description' => 'Casual striped top with puff sleeves', 'description' => 'A trendy striped top with statement puff sleeves and a smocked waist. Made from breathable cotton blend fabric. Pairs perfectly with jeans or skirts for an effortlessly stylish look.', 'category' => 'Tops & T-Shirts', 'brand' => 'TinyTrend', 'price' => 449, 'mrp' => 649, 'is_featured' => false, 'sales_count' => 760, 'img_bg' => 'FCE4EC', 'img_fg' => 'AD1457'],

            // ── Boys: T-Shirts & Shirts ──
            ['name' => 'Dinosaur Print T-Shirt - Green', 'short_description' => 'Fun dinosaur graphic tee for boys', 'description' => 'Kids love dinosaurs! This fun graphic t-shirt features a vibrant dinosaur print on premium quality cotton. Soft, breathable, and machine washable. A wardrobe essential for every little adventurer.', 'category' => 'T-Shirts & Shirts', 'brand' => 'Little Munchkins', 'price' => 399, 'mrp' => 549, 'is_featured' => true, 'sales_count' => 2100, 'img_bg' => 'E8F5E9', 'img_fg' => '2E7D32'],
            ['name' => 'Half Sleeve Cotton Shirt - Sky Blue', 'short_description' => 'Smart casual cotton shirt for boys', 'description' => 'A crisp half-sleeve cotton shirt in sky blue. Features a button-down collar, chest pocket, and comfortable regular fit. Perfect for school, parties, and family outings. Wrinkle-resistant fabric.', 'category' => 'T-Shirts & Shirts', 'brand' => 'ForeverKids', 'price' => 549, 'mrp' => 799, 'is_featured' => false, 'sales_count' => 1340, 'img_bg' => 'E3F2FD', 'img_fg' => '1565C0'],
            ['name' => 'Superhero Graphic Tee - Pack of 3', 'short_description' => 'Pack of 3 superhero graphic t-shirts', 'description' => 'Three awesome superhero-themed graphic tees in red, blue, and grey. Made from 100% combed cotton for supreme softness. Bold prints that stay vibrant after multiple washes. Ages 3-12.', 'category' => 'T-Shirts & Shirts', 'brand' => 'KidStreet', 'price' => 799, 'mrp' => 1199, 'is_featured' => true, 'sales_count' => 1980, 'img_bg' => 'FFEBEE', 'img_fg' => 'C62828'],

            // ── Boys: Kurta Sets ──
            ['name' => 'Chikankari Kurta Pajama - White', 'short_description' => 'Classic white chikankari kurta set', 'description' => 'A timeless white kurta with beautiful Lucknowi chikankari embroidery. Paired with comfortable elastic-waist pajama. Made from premium cotton for a soft, breathable feel. Ideal for pujas, Eid, and family functions.', 'category' => 'Kurta Sets', 'brand' => 'Desi Kids', 'price' => 999, 'mrp' => 1499, 'is_featured' => true, 'sales_count' => 2450, 'img_bg' => 'FAFAFA', 'img_fg' => '424242'],
            ['name' => 'Printed Kurta & Dhoti Set - Yellow', 'short_description' => 'Festive printed kurta with dhoti', 'description' => 'A cheerful yellow printed kurta paired with a pre-stitched dhoti for easy dressing. Traditional yet comfortable, perfect for temple visits, Holi celebrations, and family gatherings.', 'category' => 'Kurta Sets', 'brand' => 'Desi Kids', 'price' => 849, 'mrp' => 1199, 'is_featured' => false, 'sales_count' => 1150, 'img_bg' => 'FFF9C4', 'img_fg' => 'F57F17'],

            // ── Boys: Sherwani Sets ──
            ['name' => 'Royal Sherwani Set - Maroon', 'short_description' => 'Designer sherwani set for celebrations', 'description' => 'A regal maroon sherwani with gold button details and mandarin collar. Includes matching churidar and stole. Your little prince will look his best at weddings and festive occasions.', 'category' => 'Sherwani Sets', 'brand' => 'Desi Kids', 'price' => 1999, 'mrp' => 2999, 'is_featured' => true, 'sales_count' => 980, 'img_bg' => 'F3E5E5', 'img_fg' => '7B1F1F'],

            // ── Boys: Jeans & Trousers ──
            ['name' => 'Slim Fit Jogger Jeans - Dark Blue', 'short_description' => 'Comfortable jogger-style jeans for boys', 'description' => 'Modern jogger-style jeans that combine the look of denim with the comfort of joggers. Features an elastic waistband with drawstring, ribbed cuffs, and stretchy fabric. Perfect for active boys.', 'category' => 'Jeans & Trousers', 'brand' => 'KidStreet', 'price' => 699, 'mrp' => 999, 'is_featured' => false, 'sales_count' => 1560, 'img_bg' => 'E3F2FD', 'img_fg' => '0D47A1'],
            ['name' => 'Cargo Pants - Khaki', 'short_description' => 'Rugged cargo pants with multiple pockets', 'description' => 'Tough yet comfortable cargo pants in classic khaki. Features multiple pockets, reinforced knees, and an adjustable waistband. Built for adventure-loving boys who love to play outdoors.', 'category' => 'Jeans & Trousers', 'brand' => 'Little Munchkins', 'price' => 649, 'mrp' => 899, 'is_featured' => false, 'sales_count' => 870, 'img_bg' => 'F5F0E6', 'img_fg' => '6D5A3A'],

            // ── Baby Clothing ──
            ['name' => 'Organic Cotton Romper - Pack of 3', 'short_description' => 'Soft organic cotton rompers for babies', 'description' => 'A set of 3 adorable organic cotton rompers with cute animal prints. Features envelope neckline for easy dressing and nickel-free snap closures for quick diaper changes. GOTS certified organic cotton. Ages 0-18 months.', 'category' => 'Rompers & Bodysuits', 'brand' => 'CuddleBug', 'price' => 699, 'mrp' => 999, 'is_featured' => true, 'sales_count' => 2800, 'img_bg' => 'FFF3E0', 'img_fg' => 'E65100'],
            ['name' => 'Knitted Baby Sweater Set - Cream', 'short_description' => 'Cozy knitted sweater set for babies', 'description' => 'An adorable cream knitted sweater set including a cardigan, pants, booties, and a matching cap. Made from hypoallergenic acrylic yarn. A perfect gift set for newborns and baby showers.', 'category' => 'Baby Sets', 'brand' => 'CuddleBug', 'price' => 899, 'mrp' => 1299, 'is_featured' => false, 'sales_count' => 1450, 'img_bg' => 'FFF8E1', 'img_fg' => '8D6E63'],
            ['name' => 'Muslin Swaddle Set - 3 Pack', 'short_description' => 'Breathable muslin swaddle blankets', 'description' => 'Ultra-soft muslin swaddle blankets in 3 beautiful prints (stars, clouds, and rainbows). Made from 100% organic cotton muslin that gets softer with every wash. Large 47x47 inch size for versatile use.', 'category' => 'Swaddles & Blankets', 'brand' => 'CuddleBug', 'price' => 599, 'mrp' => 849, 'is_featured' => false, 'sales_count' => 1680, 'img_bg' => 'E8EAF6', 'img_fg' => '3F51B5'],

            // ── More Girls ──
            ['name' => 'Cotton Pajama Set - Bunny Print', 'short_description' => 'Cute bunny print pajama set for girls', 'description' => 'An adorable pajama set featuring cute bunny prints on soft cotton fabric. Includes a long-sleeve top and matching pants with elastic waist. Perfect for cozy nights and Sunday mornings.', 'category' => 'Nightwear', 'brand' => 'CuddleBug', 'price' => 549, 'mrp' => 749, 'is_featured' => false, 'sales_count' => 920, 'img_bg' => 'FCE4EC', 'img_fg' => 'C2185B'],
            ['name' => 'Pleated Skirt - Navy Check', 'short_description' => 'Classic pleated check skirt for girls', 'description' => 'A timeless navy check pleated skirt with an elastic waistband for comfortable fit. Made from a polycotton blend that is wrinkle-resistant and easy to care for. Great for school and casual wear.', 'category' => 'Skirts & Shorts', 'brand' => 'ForeverKids', 'price' => 499, 'mrp' => 699, 'is_featured' => false, 'sales_count' => 740, 'img_bg' => 'E8EDF2', 'img_fg' => '1A237E'],

            // ── More Boys ──
            ['name' => 'Polo T-Shirt - Pack of 2', 'short_description' => 'Classic polo tees in 2 colors', 'description' => 'A pack of 2 classic polo t-shirts in navy and white. Made from premium pique cotton with a ribbed collar and two-button placket. Smart, versatile, and perfect for school or weekend outings.', 'category' => 'T-Shirts & Shirts', 'brand' => 'ForeverKids', 'price' => 699, 'mrp' => 999, 'is_featured' => false, 'sales_count' => 1450, 'img_bg' => 'E8EDF5', 'img_fg' => '283593'],
            ['name' => 'Dino Pajama Set - Boys', 'short_description' => 'Dinosaur print pajama set for boys', 'description' => 'A fun dinosaur-themed pajama set with an all-over glow-in-the-dark print. Made from 100% cotton jersey for ultimate sleep comfort. Features a crew neck top and elastic waist pants.', 'category' => 'Nightwear', 'brand' => 'Little Munchkins', 'price' => 549, 'mrp' => 749, 'is_featured' => false, 'sales_count' => 830, 'img_bg' => 'E0F7FA', 'img_fg' => '00695C'],
        ];

        foreach ($products as $p) {
            $catId = $categories[$p['category']] ?? null;
            $brandId = $brands[$p['brand']] ?? null;

            if (!$catId) {
                $this->command->warn("Category '{$p['category']}' not found, skipping {$p['name']}");
                continue;
            }

            $product = Product::create([
                'uuid' => (string) Str::uuid(),
                'name' => $p['name'],
                'category_id' => $catId,
                'brand_id' => $brandId,
                'short_description' => $p['short_description'],
                'description' => $p['description'],
                'sku' => 'FK-' . strtoupper(Str::random(8)),
                'price' => $p['price'],
                'mrp' => $p['mrp'],
                'cost_price' => round($p['price'] * 0.4),
                'stock_quantity' => rand(50, 500),
                'low_stock_threshold' => 10,
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_featured' => $p['is_featured'],
                'is_taxable' => true,
                'rating' => rand(40, 50) / 10,
                'review_count' => rand(25, 350),
                'sales_count' => $p['sales_count'],
                'status' => 'approved',
                'published_at' => now()->subDays(rand(1, 60)),
            ]);

            ProductImage::create([
                'product_id' => $product->id,
                'url' => "https://placehold.co/600x600/{$p['img_bg']}/{$p['img_fg']}?text=" . urlencode(Str::limit($p['name'], 22)),
                'alt_text' => $p['name'],
                'position' => 0,
                'is_primary' => true,
            ]);
        }
    }

    private function seedHomepageSections(): void
    {
        $sections = [
            ['key' => 'benefits', 'title' => 'Clothing for children exactly as they love it!', 'subtitle' => 'Quality clothing parents trust', 'type' => 'benefits', 'position' => 0, 'button_text' => 'How We Do It', 'button_link' => '/products', 'content' => [
                ['title' => '100% Safe Materials', 'description' => 'Pure cotton & organic fabrics gentle on little skin', 'icon' => 'shield'],
                ['title' => 'Super Comfortable', 'description' => 'Toddler-tested for all-day play & comfort', 'icon' => 'comfort'],
                ['title' => 'Wash & Wear', 'description' => 'Easy to wash, quick to dry, ready to rewear', 'icon' => 'wash'],
                ['title' => 'Vibrant Colors', 'description' => 'Fade-resistant dyes that stay bright wash after wash', 'icon' => 'colors'],
                ['title' => 'No Pokey Tags', 'description' => 'Tagless labels so nothing irritates your child', 'icon' => 'tagless'],
                ['title' => 'Made With Love', 'description' => 'Each piece crafted with care for your little ones', 'icon' => 'heart'],
            ]],
            ['key' => 'categories', 'title' => 'Shop by Category', 'subtitle' => 'Find the perfect outfit for your little ones', 'type' => 'categories', 'position' => 1],
            ['key' => 'featured', 'title' => 'Featured Products', 'subtitle' => 'Handpicked favorites parents love', 'type' => 'products', 'position' => 2, 'button_text' => 'View All', 'button_link' => '/products'],
            ['key' => 'promo_banner', 'title' => 'Dress Them in Joy', 'subtitle' => 'Adorable outfits crafted with the softest fabrics for happy little ones.', 'type' => 'cta', 'position' => 3, 'button_text' => 'Shop Collection', 'button_link' => '/products'],
            ['key' => 'bestsellers', 'title' => 'Bestsellers', 'subtitle' => 'Most-loved outfits that parents keep coming back for', 'type' => 'products', 'position' => 4, 'button_text' => 'View All', 'button_link' => '/bestsellers'],
            ['key' => 'new_arrivals', 'title' => 'New Arrivals', 'subtitle' => 'Fresh styles just dropped this week', 'type' => 'products', 'position' => 5, 'button_text' => 'View All', 'button_link' => '/new-arrivals'],
            ['key' => 'deals', 'title' => "Today's Deals", 'subtitle' => 'Grab these offers before they are gone', 'type' => 'products', 'position' => 6],
            ['key' => 'testimonials', 'title' => 'Happy Parents', 'subtitle' => 'Real reviews from real families', 'type' => 'testimonials', 'position' => 7],
            ['key' => 'newsletter', 'title' => 'Join the ForeverKids Family', 'subtitle' => 'Get 15% off your first order plus weekly style picks', 'type' => 'newsletter', 'position' => 8],
            ['key' => 'instagram', 'title' => 'Follow @ForeverKids', 'subtitle' => 'Tag us with #ForeverKids and get featured', 'type' => 'content', 'position' => 9],
        ];

        foreach ($sections as $s) {
            HomepageSection::create(array_merge($s, ['is_active' => true]));
        }
    }

    private function seedTestimonials(): void
    {
        $testimonials = [
            ['name' => 'Priya Sharma', 'title' => 'Mom of Two Girls', 'content' => 'The cotton frocks are incredibly soft and my daughters love the floral prints! We have bought 5 dresses so far and every single one has held up beautifully after dozens of washes. Truly great quality for the price.', 'rating' => 5, 'product_name' => 'Floral Print Cotton Frock', 'position' => 1],
            ['name' => 'Rahul Verma', 'title' => 'Father of a 6-Year-Old', 'content' => 'Bought the chikankari kurta set for my son\'s birthday party — he looked absolutely dashing! The embroidery is detailed and the fabric feels premium. We got so many compliments. Already ordered the sherwani for the upcoming wedding.', 'rating' => 5, 'product_name' => 'Chikankari Kurta Pajama', 'position' => 2],
            ['name' => 'Sneha Patel', 'title' => 'New Mom', 'content' => 'The organic cotton rompers are a lifesaver! So gentle on my baby\'s sensitive skin. The snap closures make diaper changes quick and the fabric gets even softer after washing. Best baby clothing brand we have tried.', 'rating' => 5, 'product_name' => 'Organic Cotton Romper', 'position' => 3],
            ['name' => 'Ananya Reddy', 'title' => 'Regular Customer', 'content' => 'My daughter\'s lehenga from ForeverKids stole the show at our family wedding! The embroidery is gorgeous and she was comfortable wearing it all day. The quality rivals brands that charge 3x more.', 'rating' => 5, 'product_name' => 'Embroidered Lehenga Choli', 'position' => 4],
            ['name' => 'Vikram Singh', 'title' => 'Dad Who Shops Online', 'content' => 'The superhero t-shirt pack is amazing value — three high-quality tees with vibrant prints that my son absolutely loves. The cotton is thick but breathable and the colors haven\'t faded even after many washes.', 'rating' => 5, 'product_name' => 'Superhero Graphic Tee Pack', 'position' => 5],
            ['name' => 'Meera Iyer', 'title' => 'Fashion-Loving Mom', 'content' => 'ForeverKids has become our go-to store for all kids\' clothing. From everyday t-shirts to festive lehengas, everything is well-made and reasonably priced. The sharara set for my daughter\'s dance recital was absolutely stunning!', 'rating' => 5, 'product_name' => 'Printed Sharara Set', 'position' => 6],
            ['name' => 'Deepak Joshi', 'title' => 'Proud Father', 'content' => 'The jogger jeans are my son\'s new favorite pants — he literally wants to wear them every day! They look like proper jeans but are as comfortable as track pants. Brilliant concept and great quality stitching.', 'rating' => 4, 'product_name' => 'Slim Fit Jogger Jeans', 'position' => 7],
            ['name' => 'Kavita Nair', 'title' => 'Grandmother', 'content' => 'Ordered the knitted baby sweater set as a gift for my new grandchild. The packaging was beautiful and the quality is exceptional — so soft and delicate. The little booties and cap are absolutely adorable. A perfect gift set!', 'rating' => 5, 'product_name' => 'Knitted Baby Sweater Set', 'position' => 8],
        ];

        foreach ($testimonials as $t) {
            Testimonial::create(array_merge($t, ['is_active' => true]));
        }
    }
}
