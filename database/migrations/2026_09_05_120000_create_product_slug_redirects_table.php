<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Old product URLs, kept so that changing a slug does not throw away the
 * address search engines and other people's links already point at.
 *
 * The catalogue was imported from an outside source and the product names were
 * later corrected with a bulk find-and-replace that touched `name` and
 * `description` but never `slug`. The result is live URLs whose wording no
 * longer matches the page they open. Fixing the slugs without this table would
 * turn every one of those indexed addresses into a 404 on the day of the fix;
 * with it, each one answers 301 and points at the current address instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_slug_redirects', function (Blueprint $table) {
            $table->id();

            // The address that used to work. Unique because one old slug can
            // only ever mean one product, and the 191 length keeps the index
            // inside the utf8mb4 key limit on older MySQL.
            $table->string('old_slug', 191)->unique();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Slugs can be rewritten more than once. Keeping every hop lets an
            // address from two renames ago still find its way home, and makes
            // it obvious which run of the command created a given row.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_slug_redirects');
    }
};
