<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Texture joins size and colour on the line itself rather than in a JSON
     * blob, for the same reason colour did: a cart line is identified by
     * product + variant + size + colour, and Matte and Glossy of the same
     * black M have to stay two lines. Ordered rows keep their own copy so a
     * past order still reads correctly after the product is edited.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            if (! Schema::hasColumn('cart_items', 'texture')) {
                $table->string('texture', 60)->nullable()->after('colour');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'texture')) {
                $table->string('texture', 60)->nullable()->after('colour');
            }
        });

        Schema::table('cart_items', function (Blueprint $table) {
            // Add first, drop second: the cart_id foreign key needs an index
            // starting with cart_id at all times, so the old one cannot go
            // first. Without texture in the key, adding Glossy to a cart that
            // already holds Matte in the same size and colour fails with a
            // 1062 and 500s the add-to-cart request.
            $table->unique(
                ['cart_id', 'product_id', 'variant_id', 'size', 'colour', 'texture'],
                'cart_items_line_texture_unique'
            );
            $table->dropUnique('cart_items_line_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(
                ['cart_id', 'product_id', 'variant_id', 'size', 'colour'],
                'cart_items_line_unique'
            );
            $table->dropUnique('cart_items_line_texture_unique');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            if (Schema::hasColumn('cart_items', 'texture')) {
                $table->dropColumn('texture');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'texture')) {
                $table->dropColumn('texture');
            }
        });
    }
};
