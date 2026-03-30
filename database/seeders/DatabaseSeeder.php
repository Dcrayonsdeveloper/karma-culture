<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            SellerSeeder::class,
            ProductSeeder::class,
            BannerSeeder::class,
            CouponSeeder::class,
            SettingSeeder::class,
            BeautySeeder::class,
        ]);
    }
}
