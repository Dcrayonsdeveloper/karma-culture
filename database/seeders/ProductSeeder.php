<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = Seller::all();
        $categories = Category::whereNotNull('parent_id')->get();
        $brands = Brand::all();

        if ($sellers->isEmpty() || $categories->isEmpty()) {
            $this->command->warn('Please run SellerSeeder and CategorySeeder first.');
            return;
        }

        $products = [
            // Electronics
            ['name' => 'iPhone 15 Pro Max', 'mrp' => 1199.99, 'price' => 1099.99, 'stock' => 50],
            ['name' => 'Samsung Galaxy S24 Ultra', 'mrp' => 1299.99, 'price' => 1199.99, 'stock' => 45],
            ['name' => 'MacBook Pro 16"', 'mrp' => 2499.99, 'price' => 2399.99, 'stock' => 25],
            ['name' => 'Dell XPS 15', 'mrp' => 1799.99, 'price' => 1599.99, 'stock' => 30],
            ['name' => 'Sony WH-1000XM5 Headphones', 'mrp' => 399.99, 'price' => 349.99, 'stock' => 100],
            ['name' => 'AirPods Pro 2nd Gen', 'mrp' => 249.99, 'price' => 229.99, 'stock' => 150],
            ['name' => 'iPad Pro 12.9"', 'mrp' => 1099.99, 'price' => 999.99, 'stock' => 40],
            ['name' => 'Samsung 65" OLED TV', 'mrp' => 2199.99, 'price' => 1899.99, 'stock' => 15],

            // Fashion
            ['name' => 'Nike Air Max 270', 'mrp' => 159.99, 'price' => 129.99, 'stock' => 200],
            ['name' => 'Adidas Ultraboost 23', 'mrp' => 189.99, 'price' => 159.99, 'stock' => 180],
            ['name' => 'Levi\'s 501 Original Jeans', 'mrp' => 79.99, 'price' => 59.99, 'stock' => 300],
            ['name' => 'North Face Puffer Jacket', 'mrp' => 299.99, 'price' => 249.99, 'stock' => 75],
            ['name' => 'Ray-Ban Aviator Sunglasses', 'mrp' => 179.99, 'price' => 149.99, 'stock' => 120],
            ['name' => 'Michael Kors Handbag', 'mrp' => 298.00, 'price' => 248.00, 'stock' => 50],

            // Home & Living
            ['name' => 'Dyson V15 Vacuum', 'mrp' => 749.99, 'price' => 649.99, 'stock' => 40],
            ['name' => 'Instant Pot Duo 7-in-1', 'mrp' => 119.99, 'price' => 89.99, 'stock' => 150],
            ['name' => 'Nespresso Vertuo Coffee Machine', 'mrp' => 199.99, 'price' => 159.99, 'stock' => 80],
            ['name' => 'Egyptian Cotton Sheet Set', 'mrp' => 149.99, 'price' => 119.99, 'stock' => 200],
            ['name' => 'Memory Foam Pillow Set', 'mrp' => 79.99, 'price' => 59.99, 'stock' => 250],

            // Sports
            ['name' => 'Peloton Bike+', 'mrp' => 2495.00, 'price' => 2295.00, 'stock' => 20],
            ['name' => 'Bowflex Adjustable Dumbbells', 'mrp' => 549.99, 'price' => 449.99, 'stock' => 60],
            ['name' => 'Yoga Mat Premium', 'mrp' => 69.99, 'price' => 49.99, 'stock' => 300],
            ['name' => 'Wilson Tennis Racket Pro', 'mrp' => 249.99, 'price' => 199.99, 'stock' => 45],
            ['name' => 'Garmin Fenix 7X Watch', 'mrp' => 899.99, 'price' => 799.99, 'stock' => 35],

            // Beauty
            ['name' => 'La Mer Moisturizing Cream', 'mrp' => 380.00, 'price' => 350.00, 'stock' => 40],
            ['name' => 'Dyson Airwrap Complete', 'mrp' => 599.99, 'price' => 549.99, 'stock' => 30],
            ['name' => 'Charlotte Tilbury Pillow Talk Set', 'mrp' => 75.00, 'price' => 65.00, 'stock' => 100],
            ['name' => 'Olaplex Hair Repair Set', 'mrp' => 84.00, 'price' => 72.00, 'stock' => 150],
        ];

        foreach ($products as $index => $productData) {
            $seller = $sellers->random();
            $category = $categories->random();
            $brand = $brands->isNotEmpty() ? $brands->random() : null;

            Product::create([
                'uuid' => Str::uuid(),
                'seller_id' => $seller->id,
                'category_id' => $category->id,
                'brand_id' => $brand?->id,
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']),
                'short_description' => 'High-quality ' . strtolower($productData['name']) . ' with premium features.',
                'description' => '<p>Experience excellence with our ' . $productData['name'] . '. This premium product offers outstanding quality and performance that exceeds expectations.</p><p>Features include premium materials, expert craftsmanship, and attention to detail that sets it apart from the competition.</p>',
                'sku' => 'SKU-' . strtoupper(Str::random(8)),
                'mrp' => $productData['mrp'],
                'price' => $productData['price'],
                'cost_price' => $productData['price'] * 0.6,
                'stock_quantity' => $productData['stock'],
                'low_stock_threshold' => 10,
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_featured' => $index < 8,
                'is_taxable' => true,
                'tax_rate' => 10,
                'rating' => rand(35, 50) / 10,
                'review_count' => rand(10, 500),
                'view_count' => rand(100, 5000),
                'sales_count' => rand(20, 1000),
                'status' => 'approved',
                'published_at' => now()->subDays(rand(1, 90)),
            ]);
        }
    }
}
