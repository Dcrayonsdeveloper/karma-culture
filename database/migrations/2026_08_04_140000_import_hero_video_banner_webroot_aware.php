<?php

use App\Models\Banner;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Retry of 2026_08_04_130000, which silently created nothing on production.
     *
     * That migration guarded on is_file(public_path(...)). Under CLI that
     * resolves to <app>/public/images, but this host serves the site from a
     * sibling public_html/ directory - the same wrapper deploy.sh already works
     * around - and the media only exists there. The check failed, the migration
     * returned early, and Hero Banners still read "No hero banners yet" while
     * the video played on the homepage.
     *
     * The file is now looked for in every location it legitimately lives in.
     */
    private const VIDEO = '/images/karmaa-kulture-web-banner-v3.mp4';

    public function up(): void
    {
        // Never override a hero someone configured deliberately.
        if (Banner::where('position', 'hero')->exists()) {
            return;
        }

        if (! $this->videoExists()) {
            return;
        }

        Banner::create([
            'name' => 'Homepage hero video',
            'position' => 'hero',
            'video_url' => self::VIDEO,
            'overlay_style' => 'left-dark',
            'priority' => 0,
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        Banner::where('position', 'hero')
            ->where('video_url', self::VIDEO)
            ->delete();
    }

    /** public/ locally; the sibling public_html/ on Hostinger. */
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
