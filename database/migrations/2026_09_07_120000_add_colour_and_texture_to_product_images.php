<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which shade and which fabric a photo actually shows.
     *
     * The product page has offered a colour picker and a texture picker for a
     * while, but the gallery beside them never moved: a shopper tapped Indigo
     * and went on looking at the Rust photograph. These two columns are what
     * lets the gallery answer.
     *
     * Names, not ids, and deliberately so. Colours and textures are a
     * copy-on-pick model everywhere else in this app - picking "Navy #001f3f"
     * out of the library writes {name, hex} into the product's own attributes
     * JSON and never keeps a reference back - so a name is the only join the
     * rest of the codebase has, and TexturePreset swatches are already joined
     * onto the product page by normalised name. A foreign key here would be the
     * one place that worked differently.
     *
     * NOT variant_id, which already exists on this table and is tempting.
     * product_variants rows are SIZES ("M", "XL"), and
     * SizeRetirementService::retire() nulls variant_id whenever a size is
     * retired - so a colour parked there would be erased by an unrelated admin
     * action.
     *
     * Both nullable, and null is meaningful rather than missing: an untagged
     * photo is a SHARED one - the fabric close-up, the size chart, the styling
     * shot - and shows against every colour. That is what makes this safe to
     * ship onto a catalogue whose images are all untagged today: every gallery
     * keeps behaving exactly as it does now until someone tags something.
     *
     * 60 characters to match colours.*.name and textures.* in the product form,
     * and cart_items.colour / cart_items.texture downstream of it.
     */
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (! Schema::hasColumn('product_images', 'colour')) {
                $table->string('colour', 60)->nullable()->after('variant_id');
            }
            if (! Schema::hasColumn('product_images', 'texture')) {
                $table->string('texture', 60)->nullable()->after('colour');
            }
        });

        // The gallery reads a product's whole media list in one go and filters
        // it in the browser, so this index is not for the product page. It is
        // for the admin sweeps that ask the other question - "which photos are
        // tagged Indigo" - and for the backfill/retag commands.
        Schema::table('product_images', function (Blueprint $table) {
            if (! $this->hasIndex('product_images_product_id_colour_index')) {
                $table->index(['product_id', 'colour']);
            }
            if (! $this->hasIndex('product_images_product_id_texture_index')) {
                $table->index(['product_id', 'texture']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            foreach (['product_images_product_id_colour_index', 'product_images_product_id_texture_index'] as $index) {
                if ($this->hasIndex($index)) {
                    $table->dropIndex($index);
                }
            }
        });

        Schema::table('product_images', function (Blueprint $table) {
            foreach (['colour', 'texture'] as $column) {
                if (Schema::hasColumn('product_images', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Re-running a partially applied migration must not die on a duplicate key,
     * the same way the hasColumn guards above let it not die on a column.
     */
    private function hasIndex(string $name): bool
    {
        return collect(Schema::getIndexes('product_images'))
            ->contains(fn ($index) => ($index['name'] ?? null) === $name);
    }
};
