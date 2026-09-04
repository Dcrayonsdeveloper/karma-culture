<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scheduling comes back to banners, with the contradiction that removed it
 * fixed rather than reintroduced.
 *
 * The window was dropped on 2026-09-02 because it gave a banner a second,
 * invisible way to be off: the Active switch said Active and the storefront
 * showed nothing, with no way to tell from the admin screen which it was. That
 * was a reporting problem, not a scheduling one - a campaign that starts on
 * Monday is a real thing to want - so the columns return alongside
 * the Banner model's own `state` accessor, which every admin screen prints. A
 * banner is now Live, Scheduled, Expired or Hidden, and the switch never has to
 * answer for the window on its own. (Named in prose rather than as a {@see}
 * tag on purpose: the formatter turns a qualified reference into a real import,
 * and a migration that imports a model is one model change away from failing on
 * a schema that does not have the column yet - which is exactly what the two
 * hero-import migrations had to be rewritten for.)
 *
 * Every column here is nullable: several tests, and the two link-repoint
 * migrations, insert into `banners` with a hand-written column list, and a
 * NOT NULL addition would fail those inserts rather than the assertions in
 * them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (! Schema::hasColumn('banners', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('banners', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('starts_at');
            }

            // Read to a screen reader in place of the artwork. Falls back to the
            // banner's title, which is what the storefront printed before this
            // column existed - so an old banner keeps exactly the alt text it
            // already had.
            if (! Schema::hasColumn('banners', 'alt_text')) {
                $table->string('alt_text', 255)->nullable()->after('link');
            }

            // Deleting a banner used to be final, and it takes four uploaded
            // files with it. Soft deletes give the same undo the products table
            // has had all along.
            if (! Schema::hasColumn('banners', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('banners', function (Blueprint $table) {
            // The storefront's one banner query filters on all three, so they
            // are indexed together. Named explicitly because the column drop
            // that removed the last one had to name it to get rid of it.
            $table->index(['is_active', 'starts_at', 'ends_at'], 'banners_visibility_index');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropIndex('banners_visibility_index');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'ends_at', 'alt_text', 'deleted_at']);
        });
    }
};
