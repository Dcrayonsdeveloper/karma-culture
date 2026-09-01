<?php

use App\Models\Banner;
use Illuminate\Database\Migrations\Migration;

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
     * video_url keeps the leading slash so Banner::getVideoAttribute() resolves
     * it against public/ rather than the storage disk - the file stays exactly
     * where it is and where deploy.sh already ships it. Nothing is copied, so
     * the homepage renders the same file it did before.
     */
    private const VIDEO = '/images/karmaa-kulture-web-banner-v3.mp4';

    public function up(): void
    {
        // Only seed an empty hero. If banners already exist, someone has
        // configured this deliberately and must not be overridden.
        if (Banner::where('position', 'hero')->exists()) {
            return;
        }

        if (! is_file(public_path(ltrim(self::VIDEO, '/')))) {
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
};
