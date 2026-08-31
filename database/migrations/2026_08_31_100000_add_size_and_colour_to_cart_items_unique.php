<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A cart line is identified by product + variant + size + colour, but the
     * unique index only covered the first three. Two colours of the same size
     * share a variant_id, so adding the second one failed with a 1062 duplicate
     * entry and 500'd the add-to-cart request.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Add first, drop second: the cart_id foreign key needs an index
            // starting with cart_id at all times, so the old one cannot go first.
            $table->unique(
                ['cart_id', 'product_id', 'variant_id', 'size', 'colour'],
                'cart_items_line_unique'
            );
            $table->dropUnique('cart_items_cart_id_product_id_variant_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(
                ['cart_id', 'product_id', 'variant_id'],
                'cart_items_cart_id_product_id_variant_id_unique'
            );
            $table->dropUnique('cart_items_line_unique');
        });
    }
};
