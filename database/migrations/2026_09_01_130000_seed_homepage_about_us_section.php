<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Homepage → Sections screen can only list and edit rows that already
 * exist — there is no create route and nothing ever inserted any — so it
 * showed an empty panel and the About Us heading stayed stuck on the
 * hardcoded fallback in the view.
 *
 * This inserts the one section the home page actually reads, with the current
 * fallback wording as its starting values, so editing it in the admin now
 * changes the page. Idempotent: an existing row is left exactly as it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('homepage_sections')->where('key', 'about_us')->exists()) {
            return;
        }

        DB::table('homepage_sections')->insert([
            'key' => 'about_us',
            'title' => 'Crafted to Last',
            'subtitle' => 'A closer look at the cloth, cut and craft.',
            'type' => 'content',
            'button_text' => 'Read our story',
            'button_link' => '/about',
            'position' => 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Left in place: by the time this rolls back an admin may have edited
        // the copy, and deleting the row would take their wording with it.
    }
};
