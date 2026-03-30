<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Smartphones, laptops, gadgets and more',
                'icon' => 'device-mobile',
                'children' => [
                    ['name' => 'Smartphones', 'description' => 'Latest mobile phones'],
                    ['name' => 'Laptops', 'description' => 'Notebooks and ultrabooks'],
                    ['name' => 'Tablets', 'description' => 'iPads and Android tablets'],
                    ['name' => 'Audio', 'description' => 'Headphones, speakers, earbuds'],
                    ['name' => 'Cameras', 'description' => 'Digital cameras and accessories'],
                    ['name' => 'Accessories', 'description' => 'Cases, chargers, cables'],
                ],
            ],
            [
                'name' => 'Fashion',
                'description' => 'Clothing, shoes and accessories',
                'icon' => 'shirt',
                'children' => [
                    ['name' => 'Men\'s Clothing', 'description' => 'Shirts, pants, jackets'],
                    ['name' => 'Women\'s Clothing', 'description' => 'Dresses, tops, bottoms'],
                    ['name' => 'Kids\' Clothing', 'description' => 'Children\'s apparel'],
                    ['name' => 'Shoes', 'description' => 'Sneakers, boots, sandals'],
                    ['name' => 'Bags', 'description' => 'Handbags, backpacks, wallets'],
                    ['name' => 'Jewelry', 'description' => 'Necklaces, rings, earrings'],
                ],
            ],
            [
                'name' => 'Home & Living',
                'description' => 'Furniture, decor and home essentials',
                'icon' => 'home',
                'children' => [
                    ['name' => 'Furniture', 'description' => 'Sofas, beds, tables'],
                    ['name' => 'Bedding', 'description' => 'Sheets, pillows, blankets'],
                    ['name' => 'Kitchen', 'description' => 'Cookware, utensils, appliances'],
                    ['name' => 'Decor', 'description' => 'Wall art, vases, rugs'],
                    ['name' => 'Lighting', 'description' => 'Lamps, bulbs, fixtures'],
                ],
            ],
            [
                'name' => 'Beauty & Health',
                'description' => 'Skincare, makeup and wellness',
                'icon' => 'sparkles',
                'children' => [
                    ['name' => 'Skincare', 'description' => 'Cleansers, moisturizers, serums'],
                    ['name' => 'Makeup', 'description' => 'Foundation, lipstick, eyeshadow'],
                    ['name' => 'Hair Care', 'description' => 'Shampoo, conditioner, styling'],
                    ['name' => 'Fragrances', 'description' => 'Perfumes and colognes'],
                    ['name' => 'Personal Care', 'description' => 'Bath, body, oral care'],
                ],
            ],
            [
                'name' => 'Sports & Outdoors',
                'description' => 'Sports equipment and outdoor gear',
                'icon' => 'trophy',
                'children' => [
                    ['name' => 'Exercise Equipment', 'description' => 'Weights, yoga, cardio'],
                    ['name' => 'Sports Gear', 'description' => 'Team sports equipment'],
                    ['name' => 'Outdoor Recreation', 'description' => 'Camping, hiking gear'],
                    ['name' => 'Sportswear', 'description' => 'Athletic clothing'],
                ],
            ],
            [
                'name' => 'Books & Media',
                'description' => 'Books, music and entertainment',
                'icon' => 'book-open',
                'children' => [
                    ['name' => 'Books', 'description' => 'Fiction, non-fiction, textbooks'],
                    ['name' => 'eBooks', 'description' => 'Digital books'],
                    ['name' => 'Music', 'description' => 'CDs, vinyl, instruments'],
                    ['name' => 'Movies', 'description' => 'DVDs, Blu-ray, streaming'],
                ],
            ],
        ];

        $position = 1;
        foreach ($categories as $categoryData) {
            $parent = Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'description' => $categoryData['description'],
                'icon' => $categoryData['icon'] ?? null,
                'position' => $position++,
                'is_active' => true,
                'is_featured' => true,
            ]);

            if (!empty($categoryData['children'])) {
                $childPosition = 1;
                foreach ($categoryData['children'] as $childData) {
                    Category::create([
                        'parent_id' => $parent->id,
                        'name' => $childData['name'],
                        'slug' => Str::slug($childData['name']),
                        'description' => $childData['description'],
                        'position' => $childPosition++,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
