<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->after('seller_id')->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            $table->string('subject')->nullable()->after('product_id');
            $table->unsignedInteger('user_unread_count')->default(0)->after('last_message_at');
            $table->unsignedInteger('seller_unread_count')->default(0)->after('user_unread_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->dropForeign(['order_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn(['seller_id', 'order_id', 'product_id', 'subject', 'user_unread_count', 'seller_unread_count']);
        });
    }
};
