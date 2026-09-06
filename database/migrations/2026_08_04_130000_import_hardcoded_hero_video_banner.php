<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Bring the hard-coded hero video into the admin panel.
     *
     * The homepage hero pointed at a file in public/images directly, so it never
     * existed as a banner row and the Hero Banners screen showed "No hero
     * banners yet" while a video was plainly playing on the site. This creates
     * the matching row so the current hero is visible and editable.
     *
     * video_url keeps the leading slash so the model's video accessor resolves
     * it against public/ rather than the storage disk - the file stays exactly
     * where it is, alongside the rest of the deployed media. Nothing is copied, so
     * the homepage renders the same file it did before.
     *
     * Written against the query builder rather than the Banner model on
     * purpose. A migration runs against the schema as it stood at the time, but
     * a model is always the CURRENT one - so the day SoftDeletes was added to
     * Banner, this migration began asking for a `deleted_at` column that would
     * not exist for another month of migrations, and every fresh install and
     * every test run died right here.
     */
    private const VIDEO = '/images/karmaa-kulture-web-banner-v3.mp4';

    public function up(): void
    {
        // Only seed an empty hero. If banners already exist, someone has
        // configured this deliberately and must not be overridden.
        if (DB::table('banners')->where('position', 'hero')->exists()) {
            return;
        }

        if (! is_file(public_path(ltrim(self::VIDEO, '/')))) {
            return;
        }

        DB::table('banners')->insert([
            'name' => 'Homepage hero video',
            'position' => 'hero',
            'video_url' => self::VIDEO,
            'overlay_style' => 'left-dark',
            'priority' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('banners')
            ->where('position', 'hero')
            ->where('video_url', self::VIDEO)
            ->delete();
    }
};
