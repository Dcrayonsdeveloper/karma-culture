<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ties a menu row back to the page that owns it.
 *
 * Creating a page and putting it in a menu were two unrelated jobs on two
 * screens: the page form had no placement field at all, so a new policy page
 * existed at its URL and appeared in no menu until someone went to Navigation
 * and hand-typed a link to it. The page form now offers the placement, which
 * needs a way to find that page's own menu row again on the next save.
 *
 * Matching on the URL would have done it until the first slug change, and it
 * cannot tell a generated row from one an admin hand-made to the same page -
 * deleting the page would then take the hand-made link with it.
 *
 * Nullable because every row that already exists, and every link an admin adds
 * in the Navigation editor, belongs to no page. cascadeOnDelete so a deleted
 * page cannot leave a menu item pointing at a 404 even if it is removed by
 * something other than the controller.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('navigation_menus', function (Blueprint $table) {
            $table->foreignId('page_id')
                ->nullable()
                ->after('id')
                ->constrained('pages')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('navigation_menus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('page_id');
        });
    }
};
