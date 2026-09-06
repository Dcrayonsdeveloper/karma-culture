<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Retry of 2026_08_04_130000, which silently created nothing on production.
     *
     * That migration guarded on is_file(public_path(...)). Under CLI that
     * resolves to <app>/public/images, but the server it ran on served the site
     * from a sibling public_html/ directory, and the media only existed there.
     * The check failed, the migration returned early, and Hero Banners still
     * read "No hero banners yet" while the video played on the homepage.
     *
     * The file is now looked for in every location it legitimately lives in.
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
        // Never override a hero someone configured deliberately.
        if (DB::table('banners')->where('position', 'hero')->exists()) {
            return;
        }

        if (! $this->videoExists()) {
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

    /** public/ normally; the sibling public_html/ on shared hosting. */
    private function videoExists(): bool
    {
        $relative = ltrim(self::VIDEO, '/');

        foreach ([
            public_path($relative),
            dirname(base_path()).'/public_html/'.$relative,
        ] as $candidate) {
            if (is_file($candidate)) {
                return true;
            }
        }

        return false;
    }
};
