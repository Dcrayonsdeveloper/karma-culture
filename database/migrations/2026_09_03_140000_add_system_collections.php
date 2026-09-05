<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the header's built-in listings pickable from the product form.
 *
 * New In, Bestsellers and Introductory Offer are computed - newest by date,
 * best by sales count, whatever is discounted - so there was no list to add a
 * product to and the product form could not offer them. They exist as
 * collections now, which is what puts them in the tick list beside every other
 * shelf.
 *
 * They do NOT stop computing. A system collection with nothing ticked leaves
 * its page exactly as it was; tick one product and the page shows the picks
 * instead. So the automatic behaviour is the default and the override is
 * opt-in, per page, and reversible by unticking everything.
 *
 * `handle` is what ties a row to the page it overrides - the name and slug are
 * the admin's to read, and matching on either would break the link the moment
 * one was edited.
 */
return new class extends Migration
{
    /** handle => [name, slug] */
    private const SYSTEM = [
        'new_in' => ['New In', 'new-in'],
        'bestsellers' => ['Bestsellers', 'bestsellers-picks'],
        'deals' => ['Introductory Offer', 'introductory-offer'],
    ];

    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            // Null for the ones an admin makes; set for the three built-ins.
            $table->string('handle', 40)->nullable()->unique()->after('slug');
            $table->boolean('is_system')->default(false)->after('handle');
        });

        // The query builder, not the model. A migration outlives the classes
        // around it: App\Models\ProductCollection was later deleted when
        // collections became rows in `categories`, and a migration that named
        // it stopped being replayable at all - every test run rebuilds the
        // schema from zero, so the whole suite died on a class that no longer
        // exists. Migrations describe the database, so they talk to it directly.
        $now = now();

        foreach (self::SYSTEM as $handle => [$name, $slug]) {
            DB::table('collections')->updateOrInsert(
                ['handle' => $handle],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'is_system' => true,
                    'is_active' => true,
                    // The header already links these pages by their own routes;
                    // listing them again as collections would double them up.
                    'show_in_header' => false,
                    'position' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        ProductCollection::whereIn('handle', array_keys(self::SYSTEM))->delete();

        Schema::table('collections', function (Blueprint $table) {
            $table->dropUnique(['handle']);
            $table->dropColumn(['handle', 'is_system']);
        });
    }
};
