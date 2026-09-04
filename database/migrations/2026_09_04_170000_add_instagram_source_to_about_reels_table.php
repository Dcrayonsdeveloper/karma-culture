<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an About Us reel remember that it came from Instagram.
 *
 * The strip already holds uploaded clips, and those keep working untouched -
 * a row with a null instagram_media_id is a manual upload and the sync never
 * looks at it. The id is what makes a sync idempotent: re-running it matches
 * each reel back to the post it came from instead of adding a second copy.
 *
 * The poster matters more here than for an upload. Instagram's media_url is a
 * signed CDN link that expires within days, so the clip has to be downloaded
 * rather than hot-linked - and while a downloaded clip is still loading the
 * card is a dark rectangle, which is exactly what the strip looks like today.
 * thumbnail_url comes back with the same request, so the frame has something to
 * show from the first paint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('about_reels', function (Blueprint $table) {
            // Null for an uploaded clip. Unique so two syncs cannot both insert
            // the same post, whatever else goes wrong.
            $table->string('instagram_media_id', 64)->nullable()->unique()->after('video_path');
            $table->string('permalink', 255)->nullable()->after('instagram_media_id');
            $table->string('poster_path', 255)->nullable()->after('permalink');
            $table->timestamp('synced_at')->nullable()->after('poster_path');
        });
    }

    public function down(): void
    {
        Schema::table('about_reels', function (Blueprint $table) {
            $table->dropUnique(['instagram_media_id']);
            $table->dropColumn(['instagram_media_id', 'permalink', 'poster_path', 'synced_at']);
        });
    }
};
