<?php

use App\Support\NameCase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bring the rows that were already there in line with the rule new ones follow.
 *
 * From now on User's mutator folds first_name and last_name to lower case on
 * the way in and its accessor puts the capitals back on the way out. Without
 * this, that rule would hold for accounts created after the deploy and not for
 * the ones already in the table - so "Priyanshu" and "priyanshu" would go on
 * sitting side by side in a column that is supposed to have one spelling per
 * name, and every future "is this column canonical?" question would have to be
 * answered "only past this date".
 *
 * NOTHING VISIBLE CHANGES. The accessor title-cases whatever it reads, so a row
 * left as "Priyanshu" and a row folded to "priyanshu" both print "Priyanshu"
 * either way. The casing this discards is the casing the accessor was already
 * about to discard - "McDonald" reads back as "Mcdonald" with or without this
 * migration - which is what makes it safe to run rather than a second loss on
 * top of the first.
 *
 * DB::table(), so soft-deleted accounts are folded too: a closed account still
 * owns its email and phone as far as the unique indexes are concerned, and it
 * can be restored, so leaving it out would just re-introduce the mixture later.
 * Idempotent - a second run finds nothing left to change.
 */
return new class extends Migration
{
    private const COLUMNS = ['first_name', 'last_name'];

    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $columns = array_values(array_filter(
            self::COLUMNS,
            fn (string $column): bool => Schema::hasColumn('users', $column)
        ));

        if ($columns === []) {
            return;
        }

        // Chunked in PHP rather than one UPDATE ... SET first_name =
        // LOWER(first_name): mb_strtolower is the same function the mutator
        // uses, so the table ends up holding exactly what a re-save would write.
        // MySQL's LOWER() folds a narrower set of characters than PHP's on
        // several collations, and a backfill that disagrees with the mutator is
        // worse than no backfill.
        DB::table('users')
            ->select(array_merge(['id'], $columns))
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($columns) {
                foreach ($rows as $row) {
                    $changes = [];

                    foreach ($columns as $column) {
                        $folded = NameCase::store($row->{$column});

                        if ($folded !== $row->{$column}) {
                            $changes[$column] = $folded;
                        }
                    }

                    if ($changes !== []) {
                        DB::table('users')->where('id', $row->id)->update($changes);
                    }
                }
            });
    }

    /**
     * Deliberately empty. The capitals are gone from the column by design and
     * only the accessor knows where they belong; writing display casing back
     * into storage would undo the invariant rather than restore anything.
     */
    public function down(): void
    {
        //
    }
};
