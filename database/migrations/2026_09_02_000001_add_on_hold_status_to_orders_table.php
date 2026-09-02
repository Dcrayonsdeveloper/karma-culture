<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fraud detection has always written "on_hold" onto orders.status - from the
     * OrderPlaced listener, the ProcessFraudCheck job and the admin review
     * screen - but the enum never had that value. Every one of those writes was
     * rejected outright under strict mode, or silently truncated to "" without
     * it, so a blocked order was never actually held.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','on_hold','confirmed','processing','packed','shipped','out_for_delivery','delivered','cancelled','returned') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("UPDATE orders SET status = 'pending' WHERE status = 'on_hold'");
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','confirmed','processing','packed','shipped','out_for_delivery','delivered','cancelled','returned') NOT NULL DEFAULT 'pending'");
    }
};
