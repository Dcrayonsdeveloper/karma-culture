<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('guest_name')->nullable()->after('order_item_id');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->boolean('is_generated')->default(false)->after('status');
            $table->foreignId('generated_from_order_item_id')->nullable()->after('is_generated')
                  ->constrained('order_items')->nullOnDelete();
        });

        Schema::create('review_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_invitations');

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['generated_from_order_item_id']);
            $table->dropColumn(['guest_name', 'guest_email', 'is_generated', 'generated_from_order_item_id']);
        });
    }
};
