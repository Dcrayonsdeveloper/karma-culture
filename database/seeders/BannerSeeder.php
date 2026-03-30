<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'name' => 'New Season Collection - Up to 50% off',
                'position' => 'hero',
                'image_url' => 'banners/hero-1.jpg',
                'mobile_image_url' => 'banners/hero-1-mobile.jpg',
                'link' => '/collections/new-season',
                'priority' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Tech Week Sale - Latest Gadgets',
                'position' => 'hero',
                'image_url' => 'banners/hero-2.jpg',
                'mobile_image_url' => 'banners/hero-2-mobile.jpg',
                'link' => '/deals/tech-week',
                'priority' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Free Shipping Weekend - Orders Over $50',
                'position' => 'hero',
                'image_url' => 'banners/hero-3.jpg',
                'mobile_image_url' => 'banners/hero-3-mobile.jpg',
                'link' => '/free-shipping',
                'priority' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Summer Fashion - Hot Styles',
                'position' => 'sidebar',
                'image_url' => 'banners/promo-1.jpg',
                'mobile_image_url' => null,
                'link' => '/category/fashion',
                'priority' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Home Makeover - Transform Your Space',
                'position' => 'sidebar',
                'image_url' => 'banners/promo-2.jpg',
                'mobile_image_url' => null,
                'link' => '/category/home',
                'priority' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($banners as $bannerData) {
            Banner::create($bannerData);
        }
    }
}
