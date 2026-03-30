<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class ReviewSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'review_gen_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'reviews'],
            ['key' => 'review_gen_conversion_rate_min', 'value' => '30', 'type' => 'integer', 'group' => 'reviews'],
            ['key' => 'review_gen_conversion_rate_max', 'value' => '50', 'type' => 'integer', 'group' => 'reviews'],
            ['key' => 'review_gen_delay_min_days', 'value' => '2', 'type' => 'integer', 'group' => 'reviews'],
            ['key' => 'review_gen_delay_max_days', 'value' => '14', 'type' => 'integer', 'group' => 'reviews'],
            ['key' => 'review_gen_max_per_product_day', 'value' => '2', 'type' => 'integer', 'group' => 'reviews'],
            ['key' => 'review_coupon_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'reviews'],
            ['key' => 'review_coupon_value', 'value' => '5', 'type' => 'integer', 'group' => 'reviews'],
            ['key' => 'review_invitation_delay_hours', 'value' => '48', 'type' => 'integer', 'group' => 'reviews'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
