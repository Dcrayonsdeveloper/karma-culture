<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Apple', 'description' => 'Think Different', 'is_featured' => true],
            ['name' => 'Samsung', 'description' => 'Do What You Can\'t', 'is_featured' => true],
            ['name' => 'Nike', 'description' => 'Just Do It', 'is_featured' => true],
            ['name' => 'Adidas', 'description' => 'Impossible Is Nothing', 'is_featured' => true],
            ['name' => 'Sony', 'description' => 'Be Moved', 'is_featured' => true],
            ['name' => 'LG', 'description' => 'Life\'s Good', 'is_featured' => false],
            ['name' => 'Dell', 'description' => 'The power to do more', 'is_featured' => false],
            ['name' => 'HP', 'description' => 'Keep Reinventing', 'is_featured' => false],
            ['name' => 'Lenovo', 'description' => 'Different is better', 'is_featured' => false],
            ['name' => 'Asus', 'description' => 'In Search of Incredible', 'is_featured' => false],
            ['name' => 'Puma', 'description' => 'Forever Faster', 'is_featured' => false],
            ['name' => 'Under Armour', 'description' => 'I Will', 'is_featured' => false],
            ['name' => 'New Balance', 'description' => 'Fearlessly Independent', 'is_featured' => false],
            ['name' => 'Bose', 'description' => 'Better Sound Through Research', 'is_featured' => false],
            ['name' => 'JBL', 'description' => 'Dare to Listen', 'is_featured' => false],
            ['name' => 'Canon', 'description' => 'Delighting You Always', 'is_featured' => false],
            ['name' => 'Nikon', 'description' => 'At the Heart of the Image', 'is_featured' => false],
            ['name' => 'Dyson', 'description' => 'Engineered for better', 'is_featured' => true],
            ['name' => 'Philips', 'description' => 'Innovation and You', 'is_featured' => false],
            ['name' => 'Panasonic', 'description' => 'A Better Life, A Better World', 'is_featured' => false],
        ];

        $position = 1;
        foreach ($brands as $brandData) {
            Brand::create([
                'name' => $brandData['name'],
                'slug' => Str::slug($brandData['name']),
                'description' => $brandData['description'],
                'is_active' => true,
                'is_featured' => $brandData['is_featured'],
                'position' => $position++,
            ]);
        }
    }
}
