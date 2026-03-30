<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('customer', 'seller', 'staff', 'admin', 'delivery_partner') DEFAULT 'customer'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('customer', 'seller', 'staff', 'admin') DEFAULT 'customer'");
    }
};
