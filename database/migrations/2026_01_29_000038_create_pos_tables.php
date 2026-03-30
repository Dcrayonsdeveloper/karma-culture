<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('device_id', 50)->unique();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->json('settings')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
        });

        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number', 30)->unique();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('register_id')->constrained('pos_registers')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('paid_amount', 12, 2);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->enum('payment_method', ['cash', 'card', 'upi', 'split'])->default('cash');
            $table->json('payment_details')->nullable();
            $table->enum('status', ['completed', 'voided', 'refunded'])->default('completed');
            $table->json('receipt_data')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
            $table->index(['staff_id', 'created_at']);
        });

        Schema::create('pos_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('barcode', 50)->nullable();
            $table->string('product_name');
            $table->unsignedSmallInteger('quantity');
            $table->decimal('price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('pos_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 30)->unique();
            $table->foreignId('pos_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('refund_method', ['cash', 'original_payment', 'credit_note'])->default('credit_note');
            $table->foreignId('credit_note_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason')->nullable();
            $table->enum('status', ['completed', 'pending'])->default('completed');
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
        });

        Schema::create('barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('barcode', 50)->unique();
            $table->enum('type', ['ean13', 'ean8', 'upc', 'code128', 'qr'])->default('ean13');
            $table->string('image_url')->nullable();
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcodes');
        Schema::dropIfExists('pos_returns');
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');
        Schema::dropIfExists('pos_registers');
    }
};
