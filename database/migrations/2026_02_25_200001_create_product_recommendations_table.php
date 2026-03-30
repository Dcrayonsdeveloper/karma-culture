<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('recommended_product_id')->constrained('products')->onDelete('cascade');
            $table->string('type'); // similar, frequently_bought_together, trending
            $table->decimal('score', 8, 4)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'recommended_product_id', 'type'], 'product_rec_unique');
            $table->index(['product_id', 'type', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recommendations');
    }
};
