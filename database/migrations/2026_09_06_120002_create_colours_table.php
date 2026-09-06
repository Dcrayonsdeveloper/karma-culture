<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The list of colours an admin can PICK from - not the list of colours the
     * shop carries.
     *
     * Same deal as `sizes`: nothing joins here. A product keeps its own copy of
     * the colour inside products.attributes -> "Colours", and cart_items.colour
     * and order_items.colour keep theirs, so renaming Maroon to Burgundy on
     * this screen re-labels the picker and leaves every existing product, cart
     * line and invoice exactly as it was. That is deliberate - an order is a
     * record of what was sold, not a live join.
     *
     * `key` is ShopFilterCatalogue::normaliseKey($name) and is UNIQUE, which is
     * what stops Black, black and "Black " becoming three swatches; it is the
     * same grouping the shade rail already does at read time, moved to where
     * the value is typed.
     *
     * Two ways to show a colour, in priority order:
     *   hex_code  - the swatch, and the usual answer. 7 chars, "#RRGGBB".
     *   image_url - a fabric photo for the ones a flat hex lies about: a
     *               print, a weave, anything with a pattern. Null by default;
     *               the admin uploads one when the swatch is not enough.
     *
     * name is 60 to match cart_items.colour / order_items.colour, which are
     * varchar(60) - a colour that will not fit in a cart line must not be
     * pickable on a product.
     */
    public function up(): void
    {
        Schema::create('colours', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('key', 60)->unique();
            // "#RRGGBB" is seven characters; there is no room here for rgba()
            // or a colour name, and that is on purpose - the swatch has to be
            // renderable as a flat block of colour in a 20px circle.
            $table->string('hex_code', 7)->nullable();
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
        Schema::dropIfExists('colours');
    }
};
