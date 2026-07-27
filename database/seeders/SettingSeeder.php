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
            ['group' => 'general', 'key' => 'site_name', 'value' => 'Karmaa Kulture', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_tagline', 'value' => 'Premium tailored essentials', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_email', 'value' => 'support@karmaakulture.com', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_phone', 'value' => '+91 00000 00000', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_address', 'value' => 'India', 'type' => 'string'],
            ['group' => 'general', 'key' => 'timezone', 'value' => 'Asia/Kolkata', 'type' => 'string'],
            ['group' => 'general', 'key' => 'date_format', 'value' => 'M d, Y', 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency', 'value' => 'INR', 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency_symbol', 'value' => '₹', 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency_position', 'value' => 'before', 'type' => 'string'],

            // Payment Settings
            ['group' => 'payment', 'key' => 'stripe_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'paypal_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'paypal_mode', 'value' => 'sandbox', 'type' => 'string'],
            ['group' => 'payment', 'key' => 'cod_enabled', 'value' => '1', 'type' => 'boolean'],

            // Shipping Settings
            ['group' => 'shipping', 'key' => 'free_shipping_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'shipping', 'key' => 'free_shipping_threshold', 'value' => '999', 'type' => 'integer'],
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
            ['group' => 'email', 'key' => 'mail_from_address', 'value' => 'noreply@karmaakulture.com', 'type' => 'string'],
            ['group' => 'email', 'key' => 'mail_from_name', 'value' => 'Karmaa Kulture', 'type' => 'string'],

            // SEO Settings
            ['group' => 'seo', 'key' => 'meta_title', 'value' => 'Karmaa Kulture - Premium Tailored Essentials', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'meta_description', 'value' => 'Shop premium tailored essentials at Karmaa Kulture. Curated fashion for the modern individual, crafted with care.', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'meta_keywords', 'value' => 'fashion, clothing, premium, tailored, karmaa kulture', 'type' => 'string'],

            // Offer popup (Task 1) — placeholder content, admin-editable
            ['group' => 'offer_popup', 'key' => 'offer_popup_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'offer_popup', 'key' => 'offer_popup_title', 'value' => 'Unlock Exciting Offers!', 'type' => 'string'],
            ['group' => 'offer_popup', 'key' => 'offer_popup_subtitle', 'value' => 'Join our list and be the first to hear about exclusive deals and new drops.', 'type' => 'string'],

            // Social proof / purchase notifications (Task 9)
            ['group' => 'social_proof', 'key' => 'purchase_notif_enabled', 'value' => '1', 'type' => 'boolean'],
        ];

        foreach ($settings as $settingData) {
            Setting::create($settingData);
        }
    }
}
