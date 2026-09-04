<?php

use App\Support\ShopFilterCatalogue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The admin's half of the Shop Filters page.
     *
     * WHICH filter values exist is derived from the catalogue and never stored
     * (see {@see ShopFilterCatalogue}); this table records only
     * the values an admin has decided a shopper should not be offered. A row
     * here hides a value from the filters - it never touches the product that
     * carries it - and it outlives the products: hiding "Rough" keeps Rough
     * hidden when a new product brings it back months later.
     *
     * value_key is the normalised form ("black" for Black / BLACK / "Black "),
     * so one row covers every spelling. label keeps the display spelling as it
     * read when it was hidden, which is the only thing left to show in the
     * admin list once no product carries the value at all.
     */
    public function up(): void
    {
        Schema::create('shop_filter_exclusions', function (Blueprint $table) {
            $table->id();
            // Public identifier: the admin routes bind on this, so a filter
            // exclusion is never addressed by a guessable auto-increment.
            $table->uuid('uuid')->unique();
            // Not an enum: adding a fourth filter type to one would need a
            // MODIFY COLUMN on every deploy, and the set is validated in the
            // controller anyway. Values: size | shade | texture | price.
            $table->string('type', 20);
            $table->string('value_key', 191);
            $table->string('label', 191);
            $table->timestamps();

            // One row per value per type - the concurrency guard. Two admins
            // hiding "Pink" at the same moment produce one row, not two.
            $table->unique(['type', 'value_key'], 'shop_filter_exclusions_value_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_filter_exclusions');
    }
};
