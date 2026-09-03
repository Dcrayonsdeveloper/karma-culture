<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The About Us reels become rows instead of three fixed settings keys.
 *
 * `about_us_video_url`, `_2` and `_3` were three hardcoded slots: a store with
 * one clip left two empty, and a store with four had nowhere to put the fourth.
 * The admin could swap a slot's file or clear it, but could not add a reel or
 * take one out of the middle without shuffling files between slots by hand.
 *
 * The backfill below is what keeps the live section looking exactly as it does
 * today, so the three-slot rules have to be reproduced here one for one:
 *
 *   - a key holding a path or URL  -> a reel, in slot order
 *   - a key that exists but is EMPTY -> the admin cleared that slot: no reel
 *   - a key with no row at all     -> never configured, so the home page was
 *                                     falling back to its bundled clip: a reel
 *                                     pointing at that default
 *
 * The settings rows are left where they are. Nothing reads them after this, and
 * deleting them would take the only copy of a path with it if this ever has to
 * be rolled back.
 */
return new class extends Migration
{
    private const SLOTS = [
        ['key' => 'about_us_video_url', 'default' => 'videos/karmaa-about.mp4'],
        ['key' => 'about_us_video_url_2', 'default' => 'videos/karmaa-about-2.mp4'],
        ['key' => 'about_us_video_url_3', 'default' => 'videos/karmaa-about-3.mp4'],
    ];

    public function up(): void
    {
        Schema::create('about_reels', function (Blueprint $table) {
            $table->id();
            // Either a path under the public disk ("storage/storefront/about/x.mp4"),
            // a path to a bundled asset ("videos/karmaa-about.mp4"), or an absolute
            // https:// URL. All three already occur in the settings this replaces.
            $table->string('video_path', 255);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // The home page asks for "active reels in order" and nothing else.
            $table->index(['is_active', 'position']);
        });

        $rows = [];
        $now = now();

        foreach (self::SLOTS as $i => $slot) {
            $setting = DB::table('settings')->where('key', $slot['key'])->first();
            $value = $setting ? trim((string) $setting->value) : null;

            // Cleared on purpose - the card is meant to be gone.
            if ($setting && $value === '') {
                continue;
            }

            $rows[] = [
                'video_path' => $value !== null && $value !== '' ? $value : $slot['default'],
                'position' => $i + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('about_reels')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('about_reels');
    }
};
