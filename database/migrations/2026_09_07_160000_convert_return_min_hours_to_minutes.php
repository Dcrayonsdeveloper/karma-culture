<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The "wait before returning" setting is measured in minutes now, not hours.
 *
 * An hour was the smallest delay the admin could express, which is far coarser
 * than the store actually needs - a few minutes' grace after delivery is a
 * realistic policy, "one hour or nothing" is not. Renaming the key rather than
 * reinterpreting `return_min_hours` in place means an old checkout of the code
 * cannot read the new number as hours and lock returns for a week.
 *
 * The stored value is carried across as hours x 60, so whatever the admin had
 * configured keeps meaning the same length of time.
 */
return new class extends Migration
{
    public function up(): void
    {
        $old = DB::table('settings')->where('key', 'return_min_hours')->first();
        $new = DB::table('settings')->where('key', 'return_min_minutes')->first();

        // A value already under the new key was set deliberately and wins.
        if (! $new) {
            DB::table('settings')->insert([
                'group'      => 'shipping',
                'key'        => 'return_min_minutes',
                'value'      => (string) (((int) ($old->value ?? 0)) * 60),
                'type'       => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('settings')->where('key', 'return_min_hours')->delete();

        // Settings are cached per row, per group and once for the whole table,
        // and the query builder skips the model hooks that would clear them.
        DB::table('cache')->where('key', 'like', '%setting%')->delete();

        echo "  return_min_hours converted to return_min_minutes.\n";
    }

    public function down(): void
    {
        $new = DB::table('settings')->where('key', 'return_min_minutes')->first();

        if ($new && ! DB::table('settings')->where('key', 'return_min_hours')->exists()) {
            DB::table('settings')->insert([
                'group'      => 'shipping',
                'key'        => 'return_min_hours',
                // Minutes that do not divide into whole hours round down, which
                // is the safe direction: it never widens the wait on rollback.
                'value'      => (string) intdiv((int) $new->value, 60),
                'type'       => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('settings')->where('key', 'return_min_minutes')->delete();

        DB::table('cache')->where('key', 'like', '%setting%')->delete();
    }
};
