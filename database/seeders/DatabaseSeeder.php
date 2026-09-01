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
            ProductSeeder::class,
            BannerSeeder::class,
            CouponSeeder::class,
            SettingSeeder::class,
            // Without these the /privacy-policy, /terms-of-service,
            // /cookie-policy and /gdpr routes firstOrFail() into a 404.
            KarmaaLegalPagesSeeder::class,
            BeautySeeder::class,
        ]);
    }
}
