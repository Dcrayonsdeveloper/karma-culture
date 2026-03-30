<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('icon', 50)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedTinyInteger('level')->default(0);
            $table->string('path')->nullable(); // For nested set: 1/5/12
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->json('seo_data')->nullable();
            $table->json('attributes_schema')->nullable(); // Defines which attributes apply
            $table->timestamps();

            $table->index(['parent_id', 'is_active', 'position']);
            $table->index(['is_active', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
