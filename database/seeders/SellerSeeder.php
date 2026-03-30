<?php

namespace Database\Seeders;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SellerSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = [
            [
                'store_name' => 'TechHub Store',
                'business_name' => 'TechHub Electronics LLC',
                'email' => 'seller1@example.com',
                'description' => 'Your one-stop shop for the latest electronics and gadgets.',
            ],
            [
                'store_name' => 'Fashion Forward',
                'business_name' => 'Fashion Forward Inc.',
                'email' => 'seller2@example.com',
                'description' => 'Trendy fashion at affordable prices.',
            ],
            [
                'store_name' => 'Home Essentials',
                'business_name' => 'Home Essentials Co.',
                'email' => 'seller3@example.com',
                'description' => 'Quality home products for modern living.',
            ],
            [
                'store_name' => 'Sports Zone',
                'business_name' => 'Sports Zone Trading',
                'email' => 'seller4@example.com',
                'description' => 'Premium sports equipment and accessories.',
            ],
        ];

        foreach ($sellers as $sellerData) {
            // Create user for seller
            $user = User::create([
                'first_name' => explode(' ', $sellerData['store_name'])[0],
                'last_name' => 'Seller',
                'email' => $sellerData['email'],
                'password' => Hash::make('password'),
                'role' => 'seller',
                'is_verified' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            Seller::create([
                'user_id' => $user->id,
                'store_name' => $sellerData['store_name'],
                'business_name' => $sellerData['business_name'],
                'slug' => \Illuminate\Support\Str::slug($sellerData['store_name']),
                'store_description' => $sellerData['description'],
                'description' => $sellerData['description'],
                'status' => 'approved',
                'commission_rate' => 15,
                'available_balance' => rand(100, 5000),
                'pending_balance' => rand(50, 1000),
                'email_notifications' => true,
                'order_notifications' => true,
                'review_notifications' => true,
                'approved_at' => now(),
            ]);
        }
    }
}
