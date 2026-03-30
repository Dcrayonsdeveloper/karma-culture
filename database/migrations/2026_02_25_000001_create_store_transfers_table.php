<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 30)->unique();
            $table->foreignId('from_store_id')->constrained('stores');
            $table->foreignId('to_store_id')->constrained('stores');
            $table->foreignId('requested_by')->constrained('staff');
            $table->foreignId('approved_by')->nullable()->constrained('staff');
            $table->enum('status', ['pending', 'approved', 'in_transit', 'completed', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('store_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_transfer_id')->constrained('store_transfers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants');
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->integer('quantity_requested');
            $table->integer('quantity_sent')->default(0);
            $table->integer('quantity_received')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_transfer_items');
        Schema::dropIfExists('store_transfers');
    }
};
