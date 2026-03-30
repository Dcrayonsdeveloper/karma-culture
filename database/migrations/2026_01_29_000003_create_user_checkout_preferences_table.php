<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_checkout_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('default_shipping_address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
            $table->foreignId('default_billing_address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
            $table->string('default_payment_method', 50)->nullable(); // card_xxx, upi_xxx
            $table->string('default_shipping_speed', 20)->default('standard');
            $table->boolean('same_as_shipping')->default(true);
            $table->boolean('save_card_for_future')->default(true);
            $table->boolean('enable_one_click')->default(false);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_checkout_preferences');
    }
};
