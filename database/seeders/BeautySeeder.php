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
use Illuminate\Support\Str;

class BeautySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedCategories();
        $this->seedBrands();
        $this->seedProducts();
        $this->seedBanners();
        $this->seedHomepageSections();
        $this->seedTestimonials();
    }

    private function seedSettings(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'ForeverKids', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'site_tagline', 'value' => 'Adorable Clothing for Little Ones', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'site_description', 'value' => 'Trendy, comfortable kids\' clothing for boys and girls. Shop the cutest outfits, accessories, and more.', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'footer_about', 'value' => 'ForeverKids is your destination for adorable, comfortable, and stylish kids\' clothing. We believe every child deserves to look and feel great. Our curated collection features the finest outfits for boys and girls.', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'footer_copyright', 'value' => 'ForeverKids. All rights reserved.', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'social_facebook', 'value' => 'https://facebook.com/foreverkids', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'social_instagram', 'value' => 'https://instagram.com/foreverkids', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'social_twitter', 'value' => 'https://x.com/foreverkids', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'social_youtube', 'value' => 'https://youtube.com/@foreverkids', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'social_tiktok', 'value' => 'https://tiktok.com/@foreverkids', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'social_pinterest', 'value' => 'https://pinterest.com/foreverkids', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'contact_email', 'value' => 'hello@foreverkids.in', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'contact_phone', 'value' => '+91 98765 43210', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'contact_address', 'value' => '123 Fashion Street, Andheri West, Mumbai, Maharashtra 400058', 'type' => 'string', 'group' => 'homepage'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    private function seedCategories(): void
    {
        $categories = [
            ['name' => 'Girls Dresses', 'description' => 'Beautiful dresses, frocks, and gowns for girls', 'icon' => 'dress', 'position' => 1],
            ['name' => 'Boys Wear', 'description' => 'Shirts, t-shirts, kurtas, and outfits for boys', 'icon' => 'shirt', 'position' => 2],
            ['name' => 'Baby Clothing', 'description' => 'Soft and comfy clothing for babies and toddlers', 'icon' => 'baby', 'position' => 3],
            ['name' => 'Ethnic Wear', 'description' => 'Traditional Indian outfits for kids — lehengas, kurtas, sherwanis', 'icon' => 'ethnic', 'position' => 4],
            ['name' => 'Winter Wear', 'description' => 'Jackets, sweaters, hoodies, and warm clothing for kids', 'icon' => 'winter', 'position' => 5],
            ['name' => 'Footwear', 'description' => 'Shoes, sandals, and slippers for boys and girls', 'icon' => 'shoe', 'position' => 6],
            ['name' => 'Accessories', 'description' => 'Hair accessories, bags, caps, and more for kids', 'icon' => 'accessories', 'position' => 7],
            ['name' => 'Toys & Gifts', 'description' => 'Fun toys, plush animals, and gift sets for kids', 'icon' => 'toys', 'position' => 8],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['name' => $cat['name']],
                array_merge($cat, [
                    'is_active' => true,
                    'is_featured' => true,
                ])
            );
        }
    }

    private function seedBrands(): void
    {
        $brands = [
            ['name' => 'FK Premium', 'description' => 'Our premium kids\' fashion line'],
            ['name' => 'FK Organics', 'description' => 'Natural and organic kids\' clothing'],
            ['name' => 'Little Stars', 'description' => 'Trendy everyday wear for kids'],
            ['name' => 'Tiny Threads', 'description' => 'Comfortable basics and essentials'],
            ['name' => 'KidVogue', 'description' => 'Designer-inspired kids\' fashion'],
            ['name' => 'CuddleSoft', 'description' => 'Ultra-soft baby and toddler clothing'],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['name' => $brand['name']],
                array_merge($brand, ['is_active' => true])
            );
        }
    }

    private function seedProducts(): void
    {
        $categories = Category::pluck('id', 'name');
        $brands = Brand::pluck('id', 'name');

        $products = [
            // Girls Dresses
            [
                'name' => 'Cotton Floral Frock - Pink',
                'short_description' => 'Adorable floral print cotton frock for girls',
                'description' => 'This beautiful floral frock is made from 100% breathable cotton, perfect for summer days. Features a comfortable A-line cut, back button closure, and a matching belt. Available in multiple sizes for girls aged 2-8 years.',
                'category' => 'Girls Dresses', 'brand' => 'FK Premium',
                'price' => 699, 'mrp' => 999,
                'is_featured' => true, 'sales_count' => 1250,
            ],
            [
                'name' => 'Party Wear Lehenga Set - Red & Gold',
                'short_description' => 'Gorgeous lehenga choli set for festive occasions',
                'description' => 'Make your little princess shine at every celebration with this stunning lehenga choli set. Features intricate embroidery, a flared skirt, and a matching dupatta. Perfect for weddings, Diwali, and birthday parties.',
                'category' => 'Girls Dresses', 'brand' => 'KidVogue',
                'price' => 1499, 'mrp' => 2199,
                'is_featured' => true, 'sales_count' => 890,
            ],
            [
                'name' => 'Denim Dungaree Dress - Blue',
                'short_description' => 'Casual denim dungaree dress for everyday wear',
                'description' => 'A trendy denim dungaree dress that pairs perfectly with t-shirts and tops. Made from soft stretch denim for all-day comfort. Features adjustable straps and front pockets.',
                'category' => 'Girls Dresses', 'brand' => 'Little Stars',
                'price' => 849, 'mrp' => 849,
                'is_featured' => false, 'sales_count' => 650,
            ],

            // Boys Wear
            [
                'name' => 'Cotton Kurta Pajama Set - White',
                'short_description' => 'Classic white cotton kurta pajama for boys',
                'description' => 'A timeless white cotton kurta pajama set perfect for festive occasions and family gatherings. Made from premium cotton with chikankari embroidery on the front. Comfortable elastic waist pajama included.',
                'category' => 'Boys Wear', 'brand' => 'FK Premium',
                'price' => 899, 'mrp' => 1299,
                'is_featured' => true, 'sales_count' => 2100,
            ],
            [
                'name' => 'Printed T-Shirt & Shorts Set - Dinosaur',
                'short_description' => 'Fun dinosaur print t-shirt and shorts combo',
                'description' => 'Kids love dinosaurs! This vibrant printed t-shirt and shorts set features fun dinosaur graphics on soft, breathable cotton. Perfect for playtime, school, or casual outings.',
                'category' => 'Boys Wear', 'brand' => 'Tiny Threads',
                'price' => 549, 'mrp' => 749,
                'is_featured' => true, 'sales_count' => 1800,
            ],
            [
                'name' => 'Nehru Jacket Set - Navy Blue',
                'short_description' => 'Smart Nehru jacket with kurta for boys',
                'description' => 'Dress up your little gentleman with this dapper Nehru jacket and kurta set. Features a textured Nehru jacket, matching kurta, and comfortable pajama. Ideal for weddings and celebrations.',
                'category' => 'Boys Wear', 'brand' => 'KidVogue',
                'price' => 1299, 'mrp' => 1799,
                'is_featured' => true, 'sales_count' => 1450,
            ],
            [
                'name' => 'Organic Cotton Bodysuit Set - 3 Pack',
                'short_description' => 'Soft organic cotton bodysuits for babies',
                'description' => 'Ultra-soft organic cotton bodysuits in a pack of 3, with adorable animal prints. Features envelope neckline for easy dressing and snap closures for quick diaper changes. For ages 0-18 months.',
                'category' => 'Baby Clothing', 'brand' => 'FK Organics',
                'price' => 599, 'mrp' => 799,
                'is_featured' => false, 'sales_count' => 980,
            ],

            // Ethnic Wear
            [
                'name' => 'Sharara Set - Teal & Pink',
                'short_description' => 'Beautiful sharara set for girls\' festive occasions',
                'description' => 'A gorgeous sharara set featuring a teal kurta with pink embroidery and flared sharara pants. Complete with a matching dupatta. Made from comfortable georgette fabric with cotton lining.',
                'category' => 'Ethnic Wear', 'brand' => 'FK Premium',
                'price' => 1699, 'mrp' => 2499,
                'is_featured' => true, 'sales_count' => 1650,
            ],
            [
                'name' => 'Kids Sherwani Set - Maroon',
                'short_description' => 'Royal sherwani set for boys',
                'description' => 'Make your little prince look regal with this maroon sherwani set. Features detailed embroidery, mandarin collar, and matching churidar. Perfect for weddings and special celebrations.',
                'category' => 'Ethnic Wear', 'brand' => 'KidVogue',
                'price' => 1899, 'mrp' => 2699,
                'is_featured' => false, 'sales_count' => 720,
            ],
            [
                'name' => 'Pattu Pavadai Set - Magenta',
                'short_description' => 'Traditional South Indian silk pavadai for girls',
                'description' => 'A beautiful traditional pattu pavadai set in vibrant magenta with gold zari border. Made from soft art silk for comfort. Perfect for temple visits and traditional functions.',
                'category' => 'Ethnic Wear', 'brand' => 'FK Premium',
                'price' => 1199, 'mrp' => 1599,
                'is_featured' => false, 'sales_count' => 560,
            ],

            // Winter Wear
            [
                'name' => 'Puffer Jacket - Red',
                'short_description' => 'Warm and stylish puffer jacket for kids',
                'description' => 'Keep your little one warm and stylish with this lightweight puffer jacket. Water-resistant outer shell, soft fleece lining, and a detachable hood. Available for ages 3-12 years.',
                'category' => 'Winter Wear', 'brand' => 'Little Stars',
                'price' => 1299, 'mrp' => 1899,
                'is_featured' => true, 'sales_count' => 1380,
            ],
            [
                'name' => 'Knitted Sweater - Rainbow Stripes',
                'short_description' => 'Colorful knitted sweater for kids',
                'description' => 'A cheerful rainbow-striped knitted sweater made from soft acrylic yarn. Ribbed cuffs and hem for a snug fit. Machine washable and perfect for the cold season.',
                'category' => 'Winter Wear', 'brand' => 'Tiny Threads',
                'price' => 749, 'mrp' => 999,
                'is_featured' => false, 'sales_count' => 1100,
            ],

            // Footwear
            [
                'name' => 'LED Light-Up Sneakers - White',
                'short_description' => 'Fun LED light-up sneakers for kids',
                'description' => 'Kids go crazy for these LED light-up sneakers! Features 7 color-changing LED lights in the sole, velcro straps for easy on/off, and a comfortable cushioned insole. USB rechargeable.',
                'category' => 'Footwear', 'brand' => 'Little Stars',
                'price' => 899, 'mrp' => 1299,
                'is_featured' => false, 'sales_count' => 780,
            ],
            [
                'name' => 'Jelly Sandals - Glitter Pink',
                'short_description' => 'Sparkling jelly sandals for girls',
                'description' => 'Adorable glitter-infused jelly sandals that are waterproof and easy to clean. Perfect for beach trips, rainy days, and everyday wear. Non-slip sole for safety.',
                'category' => 'Footwear', 'brand' => 'CuddleSoft',
                'price' => 449, 'mrp' => 599,
                'is_featured' => true, 'sales_count' => 920,
            ],
            [
                'name' => 'Canvas Slip-On Shoes - Navy',
                'short_description' => 'Casual canvas shoes for boys',
                'description' => 'Classic canvas slip-on shoes in navy blue with elastic goring for easy wear. Lightweight, breathable, and perfect for school or play. Rubber sole for good grip.',
                'category' => 'Footwear', 'brand' => 'Tiny Threads',
                'price' => 549, 'mrp' => 749,
                'is_featured' => false, 'sales_count' => 670,
            ],

            // Accessories
            [
                'name' => 'Hair Accessories Box - 50 Pieces',
                'short_description' => 'Assorted hair accessories set for girls',
                'description' => 'A delightful box of 50 hair accessories including bows, clips, headbands, rubber bands, and scrunchies in various colors and designs. Perfect gift for little girls.',
                'category' => 'Accessories', 'brand' => 'FK Premium',
                'price' => 399, 'mrp' => 599,
                'is_featured' => false, 'sales_count' => 540,
            ],

            // Toys
            [
                'name' => 'Plush Teddy Bear - Large',
                'short_description' => 'Cuddly large plush teddy bear',
                'description' => 'A super-soft, huggable teddy bear standing 24 inches tall. Made from premium plush fabric with embroidered eyes for child safety. Perfect birthday or baby shower gift.',
                'category' => 'Toys & Gifts', 'brand' => 'CuddleSoft',
                'price' => 799, 'mrp' => 1199,
                'is_featured' => true, 'sales_count' => 1050,
            ],

            // More Accessories
            [
                'name' => 'Kids Backpack - Unicorn Print',
                'short_description' => 'Adorable unicorn print school backpack',
                'description' => 'A cute unicorn-themed backpack perfect for school or outings. Features padded straps, water bottle holder, multiple compartments, and water-resistant material. Suitable for ages 3-8.',
                'category' => 'Accessories', 'brand' => 'Little Stars',
                'price' => 699, 'mrp' => 999,
                'is_featured' => false, 'sales_count' => 830,
            ],
        ];

        foreach ($products as $productData) {
            $category = $categories[$productData['category']] ?? null;
            $brand = $brands[$productData['brand']] ?? null;

            $product = Product::updateOrCreate(
                ['name' => $productData['name']],
                [
                    'uuid' => (string) Str::uuid(),
                    'category_id' => $category,
                    'brand_id' => $brand,
                    'short_description' => $productData['short_description'],
                    'description' => $productData['description'],
                    'sku' => 'FK-' . strtoupper(Str::random(8)),
                    'price' => $productData['price'],
                    'mrp' => $productData['mrp'],
                    'cost_price' => $productData['price'] * 0.4,
                    'stock_quantity' => rand(50, 500),
                    'low_stock_threshold' => 10,
                    'stock_status' => 'in_stock',
                    'is_active' => true,
                    'is_featured' => $productData['is_featured'],
                    'is_taxable' => true,
                    'rating' => rand(40, 50) / 10,
                    'review_count' => rand(20, 300),
                    'sales_count' => $productData['sales_count'],
                    'status' => 'approved',
                    'published_at' => now()->subDays(rand(1, 90)),
                ]
            );

            // Create a placeholder product image if none exists
            if ($product->images()->count() === 0) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => 'https://placehold.co/600x600/e0f2f1/6f9ca2?text=' . urlencode(Str::limit($product->name, 20)),
                    'alt_text' => $product->name,
                    'position' => 0,
                    'is_primary' => true,
                ]);
            }
        }
    }

    private function seedBanners(): void
    {
        $banners = [
            [
                'name' => 'New Kids Collection',
                'position' => 'hero',
                'image_url' => 'https://placehold.co/1920x700/6f9ca2/ffffff?text=New+Kids+Collection',
                'link' => '/products',
                'priority' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Festive Ethnic Wear',
                'position' => 'hero',
                'image_url' => 'https://placehold.co/1920x700/f8931d/ffffff?text=Festive+Ethnic+Wear',
                'link' => '/category/ethnic-wear',
                'priority' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Shop New Arrivals',
                'position' => 'hero',
                'image_url' => 'https://placehold.co/1920x700/6f9ca2/ffffff?text=New+Arrivals',
                'link' => '/new-arrivals',
                'priority' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::updateOrCreate(
                ['name' => $banner['name']],
                $banner
            );
        }
    }

    private function seedHomepageSections(): void
    {
        $sections = [
            [
                'key' => 'benefits',
                'title' => 'Why Choose ForeverKids',
                'subtitle' => 'Quality kids\' clothing you can trust',
                'type' => 'benefits',
                'position' => 0,
                'content' => [
                    ['title' => 'Free Shipping', 'description' => 'On orders over ₹500', 'icon' => 'shipping'],
                    ['title' => '100% Safe Fabrics', 'description' => 'Certified kid-safe materials', 'icon' => 'shield'],
                    ['title' => 'Made in India', 'description' => 'Proudly Indian-made', 'icon' => 'heart'],
                    ['title' => 'Easy Returns', 'description' => '7-day return policy', 'icon' => 'return'],
                ],
            ],
            [
                'key' => 'categories',
                'title' => 'Shop by Category',
                'subtitle' => 'Explore our curated collections for your little ones',
                'type' => 'categories',
                'position' => 1,
            ],
            [
                'key' => 'featured',
                'title' => 'Featured Products',
                'subtitle' => 'Handpicked favorites parents love',
                'type' => 'products',
                'position' => 2,
                'button_text' => 'View All',
                'button_link' => '/products',
            ],
            [
                'key' => 'promo_banner',
                'title' => 'Dress Them in Joy',
                'subtitle' => 'Discover our exclusive collection of adorable kids\' clothing, crafted with the softest fabrics for happy little ones.',
                'type' => 'cta',
                'position' => 3,
                'button_text' => 'Shop Collection',
                'button_link' => '/products',
            ],
            [
                'key' => 'bestsellers',
                'title' => 'Bestsellers',
                'subtitle' => 'Our most-loved outfits that parents keep coming back for',
                'type' => 'products',
                'position' => 4,
                'button_text' => 'View All',
                'button_link' => '/bestsellers',
            ],
            [
                'key' => 'new_arrivals',
                'title' => 'New Arrivals',
                'subtitle' => 'Fresh styles to keep your kids looking adorable',
                'type' => 'products',
                'position' => 5,
                'button_text' => 'View All',
                'button_link' => '/new-arrivals',
            ],
            [
                'key' => 'deals',
                'title' => "Today's Special Offers",
                'subtitle' => 'Grab these amazing deals before they are gone',
                'type' => 'products',
                'position' => 6,
            ],
            [
                'key' => 'testimonials',
                'title' => 'What Parents Say',
                'subtitle' => 'Real reviews from real parents who trust ForeverKids',
                'type' => 'testimonials',
                'position' => 7,
            ],
            [
                'key' => 'newsletter',
                'title' => 'Join the ForeverKids Family',
                'subtitle' => 'Subscribe for exclusive offers, parenting tips, and 15% off your first order',
                'type' => 'newsletter',
                'position' => 8,
            ],
            [
                'key' => 'instagram',
                'title' => 'Follow @ForeverKids',
                'subtitle' => 'Share your kids\' looks with #ForeverKids and get featured',
                'type' => 'content',
                'position' => 9,
            ],
        ];

        foreach ($sections as $section) {
            HomepageSection::updateOrCreate(
                ['key' => $section['key']],
                array_merge($section, ['is_active' => true])
            );
        }
    }

    private function seedTestimonials(): void
    {
        $testimonials = [
            [
                'name' => 'Priya Sharma',
                'title' => 'Happy Parent',
                'content' => 'The cotton frocks are so soft and comfortable — my daughter loves wearing them! The quality is amazing for the price. We\'ve ordered multiple times now and every piece has been perfect.',
                'rating' => 5,
                'product_name' => 'Cotton Floral Frock',
                'position' => 1,
            ],
            [
                'name' => 'Rahul Verma',
                'title' => 'Father of Two',
                'content' => 'Bought the kurta pajama set for my son\'s birthday party and he looked so handsome! The fabric is premium quality and the embroidery is beautiful. Will definitely order again for Diwali.',
                'rating' => 5,
                'product_name' => 'Cotton Kurta Pajama Set',
                'position' => 2,
            ],
            [
                'name' => 'Sneha Patel',
                'title' => 'Mom of a Toddler',
                'content' => 'The organic cotton bodysuits are a lifesaver! So soft on my baby\'s skin and the snap closures make diaper changes so easy. I love that they use safe, organic fabrics.',
                'rating' => 5,
                'product_name' => 'Organic Cotton Bodysuit Set',
                'position' => 3,
            ],
            [
                'name' => 'Ananya Reddy',
                'title' => 'Regular Customer',
                'content' => 'My daughter is obsessed with the LED sneakers! They\'re not just fun — they\'re actually well-made and comfortable. She gets compliments at school every day.',
                'rating' => 5,
                'product_name' => 'LED Light-Up Sneakers',
                'position' => 4,
            ],
            [
                'name' => 'Meera Iyer',
                'title' => 'Fashion-loving Mom',
                'content' => 'ForeverKids has become our go-to store for all kids\' clothing. The quality is fantastic and the designs are so trendy. The sharara set we got for my daughter\'s dance recital was absolutely stunning!',
                'rating' => 5,
                'product_name' => 'Sharara Set',
                'position' => 5,
            ],
            [
                'name' => 'Deepak Joshi',
                'title' => 'Proud Dad',
                'content' => 'The puffer jacket kept my son warm throughout the winter trip to Manali. It\'s lightweight, water-resistant, and he says it\'s the most comfortable jacket he\'s ever had.',
                'rating' => 4,
                'product_name' => 'Puffer Jacket',
                'position' => 6,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::updateOrCreate(
                ['name' => $testimonial['name']],
                array_merge($testimonial, ['is_active' => true])
            );
        }
    }
}
