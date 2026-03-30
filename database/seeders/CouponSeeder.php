<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'name' => 'Welcome Discount',
                'description' => '10% off your first order',
                'type' => 'percentage',
                'value' => 10,
                'min_order_amount' => 50,
                'max_discount' => 100,
                'usage_limit' => null,
                'usage_per_user' => 1,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
                'is_active' => true,
            ],
            [
                'code' => 'SAVE20',
                'name' => 'Save $20',
                'description' => '$20 off orders over $100',
                'type' => 'fixed',
                'value' => 20,
                'min_order_amount' => 100,
                'max_discount' => 20,
                'usage_limit' => 1000,
                'usage_per_user' => 3,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'FREESHIP',
                'name' => 'Free Shipping',
                'description' => 'Free shipping on any order',
                'type' => 'free_shipping',
                'value' => 0,
                'min_order_amount' => 25,
                'max_discount' => null,
                'usage_limit' => 500,
                'usage_per_user' => 2,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(1),
                'is_active' => true,
            ],
            [
                'code' => 'SUMMER25',
                'name' => 'Summer Sale',
                'description' => '25% off summer collection',
                'type' => 'percentage',
                'value' => 25,
                'min_order_amount' => 75,
                'max_discount' => 150,
                'usage_limit' => 2000,
                'usage_per_user' => 5,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(2),
                'is_active' => true,
            ],
            [
                'code' => 'VIP50',
                'name' => 'VIP Discount',
                'description' => '$50 off for VIP members',
                'type' => 'fixed',
                'value' => 50,
                'min_order_amount' => 200,
                'max_discount' => 50,
                'usage_limit' => 100,
                'usage_per_user' => 1,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $couponData) {
            Coupon::create($couponData);
        }
    }
}
