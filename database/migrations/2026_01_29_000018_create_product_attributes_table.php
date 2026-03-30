<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['text', 'number', 'boolean', 'select', 'multi_select', 'color'])->default('text');
            $table->json('options')->nullable(); // For select/multi_select types
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['is_filterable', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
