<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Video support for hero banners.
     *
     * The homepage hero had a hard-coded <video> pointing at a file in
     * public/images, so the only way to change it was to edit a Blade template.
     * A banner can now carry a video instead of an image and be managed from
     * the admin panel like any other.
     *
     * Kept separate from image_url rather than overloading it: a banner may
     * legitimately have both — the image doubles as the poster frame shown
     * while the video loads, and as the fallback on connections where
     * autoplaying a large file is wasteful.
     */
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('mobile_image_url');
            // image_url was NOT NULL, which makes a video-only banner impossible to
            // save — inserting one fails with "Field 'image_url' doesn't have a
            // default value". A banner now needs an image or a video, not both.
            $table->string('image_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('video_url');
        });

        // Restoring NOT NULL would fail while video-only rows exist, so backfill
        // them with the placeholder the model already falls back to.
        DB::table('banners')->whereNull('image_url')->update(['image_url' => '']);

        Schema::table('banners', function (Blueprint $table) {
            $table->string('image_url')->nullable(false)->change();
        });
    }
};
