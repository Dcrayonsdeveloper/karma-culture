<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The list of sizes an admin can PICK from - not the list of sizes the
     * shop carries.
     *
     * Nothing joins to this table. A variant still stores its own copy of the
     * label ("M"), and so do cart_items.size and order_items.size, which is
     * what lets an order printed last winter still say what was bought after
     * the size has been renamed or retired here. This table exists so the
     * admin stops re-typing "M", "m" and " M " into three products and getting
     * three chips on the shop's size rail.
     *
     * `key` is the normalised name - ShopFilterCatalogue::normaliseKey(), the
     * same trim/collapse/lowercase the storefront rails already group by - and
     * it is UNIQUE, which is the whole point of the column: it is the
     * constraint that makes "M", "m" and " M " one entry here as well as one
     * chip out there. Never write it by hand; the model sets it on save.
     *
     * name is capped at 50 to match cart_items.size and order_items.size. A
     * longer name would be pickable on a product and then silently truncated -
     * or rejected outright - the first time a shopper tried to add it to a
     * cart, which is a bug that only shows up at the till.
     */
    public function up(): void
    {
        Schema::create('sizes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('key', 50)->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // The only read this table gets in anger: the live entries, in the
            // admin's own order, to fill a picker.
            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sizes');
    }
};
