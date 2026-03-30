<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo user
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'phone' => '+1234567890',
            'email_verified_at' => now(),
        ]);

        // Create customer record
        Customer::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => 'active',
        ]);

        // Create address for demo user
        UserAddress::create([
            'user_id' => $user->id,
            'label' => 'Home',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+1234567890',
            'address_line_1' => '123 Main Street',
            'address_line_2' => 'Apt 4B',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'US',
            'is_default' => true,
        ]);

        // Create additional test users
        $testUsers = [
            ['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com'],
            ['first_name' => 'Bob', 'last_name' => 'Wilson', 'email' => 'bob@example.com'],
            ['first_name' => 'Alice', 'last_name' => 'Johnson', 'email' => 'alice@example.com'],
            ['first_name' => 'Charlie', 'last_name' => 'Brown', 'email' => 'charlie@example.com'],
        ];

        foreach ($testUsers as $userData) {
            $testUser = User::create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            Customer::create([
                'user_id' => $testUser->id,
                'first_name' => $testUser->first_name,
                'last_name' => $testUser->last_name,
                'email' => $testUser->email,
                'status' => 'active',
            ]);
        }
    }
}
