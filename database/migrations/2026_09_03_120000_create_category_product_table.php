<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every shelf a product is displayed on, not just the one it is filed under.
 *
 * products.category_id holds a single category, so a unisex shirt could be
 * listed under MEN > Shirts or WOMEN > Shirts but never both - the admin had
 * to pick one and lose the other. It stays as the PRIMARY category, because
 * the breadcrumb, the canonical URL, coupon scoping and every report want one
 * canonical answer to "what is this product". This table answers the different
 * question the storefront listings ask: "should this product appear here".
 *
 * Backfilled from category_id so the pivot is complete the moment it exists -
 * without it every product would drop out of every listing until it was next
 * saved. Product::booted() keeps the primary in the pivot from then on, for
 * the write paths that never touch the admin form (imports, seeders, console
 * commands, the API).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            // A product is on a shelf once or not at all. Doubles as the lookup
            // index for "which products are in these categories".
            $table->primary(['product_id', 'category_id']);

            // The listings ask it the other way round too: category -> products.
            $table->index(['category_id', 'product_id']);
        });

        DB::statement(
            'INSERT INTO category_product (product_id, category_id)
             SELECT id, category_id FROM products WHERE category_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
