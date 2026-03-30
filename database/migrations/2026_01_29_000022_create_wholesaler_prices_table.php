<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesaler_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum']);
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('min_quantity')->default(1);
            $table->timestamps();

            $table->unique(['product_id', 'tier', 'min_quantity']);
            $table->index(['tier', 'min_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesaler_prices');
    }
};
