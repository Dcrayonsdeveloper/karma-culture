<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('type')->default('content'); // hero, products, categories, cta, promo, testimonials, newsletter, benefits
            $table->json('content')->nullable();
            $table->string('background_color')->nullable();
            $table->string('text_color')->nullable();
            $table->string('image_url')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_link')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('avatar_url')->nullable();
            $table->integer('rating')->default(5);
            $table->string('product_name')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->string('location'); // header, footer_col1, footer_col2, footer_col3, footer_col4
            $table->string('label');
            $table->string('url');
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('open_in_new_tab')->default(false);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('navigation_menus')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_menus');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('homepage_sections');
    }
};
