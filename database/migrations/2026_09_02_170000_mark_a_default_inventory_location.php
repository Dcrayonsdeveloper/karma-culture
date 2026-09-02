<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Say out loud which warehouse is the default one.
 *
 * inventory_locations.is_default exists but no screen ever set it, so a shop
 * that has been running for months has every location marked 0. Stock still
 * lands somewhere - InventoryStockService::defaultLocation() falls back to the
 * oldest location, and the Adjust Stock dialog pre-selects whichever option the
 * browser lands on - but "somewhere" is decided by id order and alphabetical
 * order agreeing by luck, and they stop agreeing the moment a location is
 * renamed or added.
 *
 * Marking the oldest location keeps today's behaviour exactly as it is; it just
 * stops it being an accident. Locations created from now on set the flag
 * themselves (the first one a shop creates is its default).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('inventory_locations')->where('is_default', true)->exists()) {
            return;
        }

        // The same row defaultLocation() already falls back to, so nothing
        // moves - a different choice would silently redirect adjustments.
        $oldest = DB::table('inventory_locations')->orderBy('id')->value('id');

        if (! $oldest) {
            return;
        }

        DB::table('inventory_locations')
            ->where('id', $oldest)
            ->update(['is_default' => true, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Unmarking it would only restore the ambiguity this removed.
    }
};
