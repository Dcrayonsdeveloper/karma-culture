<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin user
        $superAdminUser = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_verified' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Admin::create([
            'user_id' => $superAdminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Create Store Manager user
        $managerUser = User::create([
            'first_name' => 'Store',
            'last_name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_verified' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Admin::create([
            'user_id' => $managerUser->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
