<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('related_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->enum('type', ['similar', 'frequently_bought', 'upsell', 'cross_sell'])->default('similar');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'related_product_id', 'type']);
            $table->index(['product_id', 'type', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('related_products');
    }
};
