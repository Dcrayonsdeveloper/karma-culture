<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_filter_items', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['size', 'price', 'shade']);
            $table->string('label');                  // e.g. 'M', '₹1k – 2k', 'Cinnamon'
            $table->string('sub_label')->nullable(); // e.g. '210 Styles'
            $table->string('shade_hex', 9)->nullable(); // hex for shirt tint, e.g. #b8895a
            $table->string('query_string')->nullable(); // e.g. 'size=M' or 'price_min=1000&price_max=2000'
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_filter_items');
    }
};
