<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A phone-sized companion for a hero banner's video.
     *
     * The desktop hero is a wide strip - the clip the storefront ships with is
     * 1426x370 - and a phone crops that to a letterbox slot barely taller than
     * the caption sitting on it. `mobile_image_url` already existed for the
     * still; the clip had nowhere to go, so a store wanting a portrait hero on
     * mobile had to choose which breakpoint to sacrifice.
     *
     * Both mobile columns are overrides, not requirements: a banner with
     * neither still renders its desktop media everywhere, exactly as before.
     */
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('mobile_video_url')->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('mobile_video_url');
        });
    }
};
