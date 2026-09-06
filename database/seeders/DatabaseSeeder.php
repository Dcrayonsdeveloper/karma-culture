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
            // The size/colour/texture picker lists. Before ProductSeeder so a
            // freshly seeded database has the pickers filled by the time the
            // catalogue exists, and all three are firstOrCreate-based, so
            // re-seeding neither duplicates them nor overwrites an admin's
            // renames, ordering or hidden entries.
            SizeSeeder::class,
            ColourSeeder::class,
            TextureSeeder::class,
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
