<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('seller_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('sku', 50)->unique();
            $table->string('barcode', 50)->unique()->nullable();
            $table->decimal('mrp', 12, 2);
            $table->decimal('price', 12, 2);
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedSmallInteger('low_stock_threshold')->default(10);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'backorder'])->default('in_stock');
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->enum('weight_unit', ['g', 'kg', 'lb', 'oz'])->default('g');
            $table->enum('dimension_unit', ['cm', 'm', 'in', 'ft'])->default('cm');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_taxable')->default(true);
            $table->decimal('tax_rate', 5, 2)->default(18.00);
            $table->string('hsn_code', 20)->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('sales_count')->default(0);
            $table->unsignedInteger('wishlist_count')->default(0);
            $table->json('seo_data')->nullable();
            $table->json('attributes')->nullable();
            $table->json('specifications')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for search and filtering
            $table->index(['is_active', 'status', 'category_id']);
            $table->index(['is_active', 'status', 'brand_id']);
            $table->index(['is_active', 'price']);
            $table->index(['is_active', 'rating']);
            $table->index(['is_active', 'sales_count']);
            $table->index(['seller_id', 'status']);
            $table->index('created_at');
            $table->fullText(['name', 'description']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
