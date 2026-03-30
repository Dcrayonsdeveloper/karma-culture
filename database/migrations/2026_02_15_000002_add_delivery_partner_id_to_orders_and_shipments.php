<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_partner_id')
                  ->nullable()
                  ->after('seller_id')
                  ->constrained('delivery_partners')
                  ->nullOnDelete();

            $table->index(['delivery_partner_id', 'status']);
        });

        Schema::table('order_shipments', function (Blueprint $table) {
            $table->foreignId('delivery_partner_id')
                  ->nullable()
                  ->after('order_id')
                  ->constrained('delivery_partners')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_partner_id']);
            $table->dropIndex(['delivery_partner_id', 'status']);
            $table->dropColumn('delivery_partner_id');
        });

        Schema::table('order_shipments', function (Blueprint $table) {
            $table->dropForeign(['delivery_partner_id']);
            $table->dropColumn('delivery_partner_id');
        });
    }
};
