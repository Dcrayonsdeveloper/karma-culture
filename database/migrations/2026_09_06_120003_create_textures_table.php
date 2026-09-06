<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The list of textures an admin can PICK from - not the list of textures
     * the shop carries.
     *
     * Same shape as `colours`, minus hex_code: a texture is a surface, not a
     * colour, so there is nothing sensible for a flat swatch to show. Matte and
     * Glossy in the same grey are the same hex and completely different
     * fabrics. The picture IS the value here, which is why image_url is seeded
     * with a tile for every stock texture rather than left null the way the
     * colour photos are.
     *
     * Nothing joins to this table either. A product carries its own copy in
     * products.attributes -> "Textures", and cart_items.texture /
     * order_items.texture carry theirs - both varchar(60), which is where the
     * 60 here comes from.
     *
     * `key` is ShopFilterCatalogue::normaliseKey($name), UNIQUE, and set by the
     * model on save - the same normalisation the texture rail groups by.
     */
    public function up(): void
    {
        Schema::create('textures', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('key', 60)->unique();
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('textures');
    }
};
