<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add packed and out_for_delivery to orders.status enum
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','confirmed','processing','packed','shipped','out_for_delivery','delivered','cancelled','returned') DEFAULT 'pending'");

        // Add packed and out_for_delivery to order_items.status enum
        DB::statement("ALTER TABLE order_items MODIFY COLUMN status ENUM('pending','confirmed','packed','shipped','out_for_delivery','delivered','cancelled','returned') DEFAULT 'pending'");

        // Add packed_at and out_for_delivery_at timestamps
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('packed_at')->nullable()->after('confirmed_at');
            $table->timestamp('out_for_delivery_at')->nullable()->after('shipped_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['packed_at', 'out_for_delivery_at']);
        });

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','confirmed','processing','shipped','delivered','cancelled','returned') DEFAULT 'pending'");
        DB::statement("ALTER TABLE order_items MODIFY COLUMN status ENUM('pending','confirmed','shipped','delivered','cancelled','returned') DEFAULT 'pending'");
    }
};
