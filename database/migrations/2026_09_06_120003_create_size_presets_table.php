<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('size_presets', function (Blueprint $table) {
            $table->id();
            // The size label a variant row carries, e.g. "M-40".
            $table->string('name', 100);
            // Optional default measurements copied onto the size row when picked;
            // price, stock and SKU stay per-product and are filled in there.
            $table->string('measurements', 160)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('size_presets');
    }
};
