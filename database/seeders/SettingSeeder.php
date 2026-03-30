<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            ['group' => 'general', 'key' => 'site_name', 'value' => 'ForeverKids', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_tagline', 'value' => 'Your Trusted Online Marketplace', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_email', 'value' => 'support@larashop.test', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_phone', 'value' => '+1 (234) 567-890', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_address', 'value' => '123 Commerce Street, Business City, BC 12345', 'type' => 'string'],
            ['group' => 'general', 'key' => 'timezone', 'value' => 'America/New_York', 'type' => 'string'],
            ['group' => 'general', 'key' => 'date_format', 'value' => 'M d, Y', 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency', 'value' => 'USD', 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency_position', 'value' => 'before', 'type' => 'string'],

            // Payment Settings
            ['group' => 'payment', 'key' => 'stripe_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'paypal_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'paypal_mode', 'value' => 'sandbox', 'type' => 'string'],
            ['group' => 'payment', 'key' => 'cod_enabled', 'value' => '1', 'type' => 'boolean'],

            // Shipping Settings
            ['group' => 'shipping', 'key' => 'free_shipping_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'shipping', 'key' => 'free_shipping_threshold', 'value' => '50', 'type' => 'integer'],
            ['group' => 'shipping', 'key' => 'flat_rate_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'shipping', 'key' => 'flat_rate_amount', 'value' => '5.99', 'type' => 'string'],
            ['group' => 'shipping', 'key' => 'local_pickup_enabled', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'shipping', 'key' => 'shipping_origin_country', 'value' => 'US', 'type' => 'string'],

            // Tax Settings
            ['group' => 'tax', 'key' => 'tax_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'tax', 'key' => 'tax_calculation', 'value' => 'exclusive', 'type' => 'string'],
            ['group' => 'tax', 'key' => 'tax_based_on', 'value' => 'shipping', 'type' => 'string'],
            ['group' => 'tax', 'key' => 'tax_display_cart', 'value' => 'excluding', 'type' => 'string'],

            // Email Settings
            ['group' => 'email', 'key' => 'mail_driver', 'value' => 'smtp', 'type' => 'string'],
            ['group' => 'email', 'key' => 'mail_from_address', 'value' => 'noreply@foreverkids.com', 'type' => 'string'],
            ['group' => 'email', 'key' => 'mail_from_name', 'value' => 'ForeverKids', 'type' => 'string'],

            // SEO Settings
            ['group' => 'seo', 'key' => 'meta_title', 'value' => 'ForeverKids - Your Trusted Kids Online Marketplace', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'meta_description', 'value' => 'Shop the latest kids products at ForeverKids. Discover clothing, toys, accessories & more at unbeatable prices with fast shipping.', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'meta_keywords', 'value' => 'online shopping, ecommerce, electronics, fashion, home', 'type' => 'string'],
        ];

        foreach ($settings as $settingData) {
            Setting::create($settingData);
        }
    }
}
