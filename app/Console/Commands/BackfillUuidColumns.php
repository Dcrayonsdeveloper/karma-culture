<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Fills the UUID columns added by the stage 1 migration.
 *
 * Idempotent and resumable: it only ever touches rows whose target column is still
 * NULL, so it can be interrupted, re-run, and run again later to pick up rows
 * written in between. Nothing reads these columns yet, so it is safe to run against
 * a live database while the site is serving traffic.
 */
class BackfillUuidColumns extends Command
{
    protected $signature = 'uuid:backfill
        {--chunk=2000 : Rows per statement}
        {--table=* : Restrict to these tables}
        {--verify : Only check what is already filled, write nothing}';

    protected $description = 'Populate the shadow UUID key columns beside the integer primary keys';


    public function handle(): int
    {
        $migration = $this->migration();
        $database = DB::getDatabaseName();

        $tables = $migration->convertibleTables($database);
        if ($only = $this->option('table')) {
            $tables = array_values(array_intersect($tables, $only));
        }

        if (! $this->option('verify')) {
            $this->components->info('Filling primary key UUIDs');
            foreach ($tables as $table) {
                $this->backfillKeys($table);
            }

            $this->components->info('Resolving foreign keys');
            foreach ($migration->foreignKeyColumns($database) as [$child, $column, $parent]) {
                if ($only && ! in_array($child, $only, true)) {
                    continue;
                }
                $this->backfillForeignKey($child, $column, $parent);
            }
        }

        return $this->report($migration, $database, $tables);
    }

    /**
     * A UUIDv7 leads with a 48-bit millisecond timestamp, so each row's key is built
     * from its own `created_at`. Stamping every historical row with the moment of the
     * backfill instead would leave `orderBy('id')` - which most listings here sort by,
     * and which today means "oldest first" - returning an arbitrary order after the
     * swap.
     *
     * A timestamp alone is not enough. Rows written in the same millisecond would sort
     * against each other on random bits, and seeders and imports write hundreds of rows
     * per millisecond. So the 12 bits the spec leaves free directly after the version
     * nibble are used as a counter, and keys are generated in `id` order: the result is
     * strictly ascending, and `orderBy('id')` returns exactly the order it does today,
     * row for row, rather than merely approximately.
     *
     * Note this deliberately does NOT reuse the public `uuid` column that a handful of
     * tables already carry. Those are v4 - no timestamp, no order - and adopting them
     * as the key scrambled all 18 products in testing. That column stays exactly where
     * it is and keeps resolving, so nothing referring to it externally notices.
     */
    private function backfillKeys(string $table): void
    {
        $pending = DB::table($table)->whereNull('uuid_pk')->count();

        if ($pending === 0) {
            $this->line("  <fg=gray>{$table}: nothing to fill</>");

            return;
        }

        $hasCreatedAt = Schema::hasColumn($table, 'created_at');
        $bar = $this->output->createProgressBar($pending);
        $bar->setFormat("  {$table}: %current%/%max% [%bar%] %elapsed%");

        // Carried across chunks so the sequence stays ascending over the whole table.
        $lastMs = 0;
        $counter = 0;

        do {
            $columns = array_filter(['id', $hasCreatedAt ? 'created_at' : null]);

            $rows = DB::table($table)
                ->whereNull('uuid_pk')
                ->orderBy('id')
                ->limit((int) $this->option('chunk'))
                ->get($columns);

            if ($rows->isEmpty()) {
                break;
            }

            DB::transaction(function () use ($rows, $table, $hasCreatedAt, &$lastMs, &$counter) {
                foreach ($rows as $row) {
                    $ms = $hasCreatedAt && $row->created_at
                        ? (int) (new \DateTimeImmutable($row->created_at))->format('Uv')
                        : $lastMs;

                    if ($ms > $lastMs) {
                        $lastMs = $ms;
                        $counter = 0;
                    } else {
                        // Same millisecond, or a row whose created_at runs backwards
                        // against its id. Keep climbing regardless; the counter is
                        // what guarantees the order, not the clock.
                        $counter++;

                        if ($counter > 0xFFF) {
                            $lastMs++;
                            $counter = 0;
                        }
                    }

                    DB::table($table)->where('id', $row->id)->update([
                        'uuid_pk' => $this->orderedUuid7($lastMs, $counter),
                        'legacy_id' => $row->id,
                    ]);
                }
            });

            $bar->advance($rows->count());
        } while ($rows->count() === (int) $this->option('chunk'));

        $bar->finish();
        $this->newLine();

        // Rows that predate the `legacy_id` column being added still need it.
        DB::table($table)->whereNull('legacy_id')->update(['legacy_id' => DB::raw('id')]);
    }

    /**
     * A UUIDv7 whose leading 60 bits are the millisecond and the sequence counter, so
     * that string comparison between two of them is the same answer as comparing the
     * integer keys they replace. The remaining 62 bits stay random, which is what keeps
     * the value unguessable now that it is the public identifier for the row.
     */
    private function orderedUuid7(int $ms, int $counter): string
    {
        $hex = sprintf('%012x', $ms & 0xFFFFFFFFFFFF)   // 48-bit timestamp
             .'7'.sprintf('%03x', $counter & 0xFFF)      // version + 12-bit counter
             .dechex(random_int(8, 11))                  // RFC 4122 variant
             .bin2hex(random_bytes(2)).substr(bin2hex(random_bytes(6)), 0, 11);

        return sprintf('%s-%s-%s-%s-%s',
            substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4),
            substr($hex, 16, 4), substr($hex, 20, 12));
    }

    /**
     * Point the shadow foreign key at the parent's new uuid, by joining on the
     * integer key that is still authoritative.
     */
    private function backfillForeignKey(string $child, string $column, string $parent): void
    {
        $shadow = $column.'__uuid';

        if (! Schema::hasColumn($child, $shadow)) {
            return;
        }

        $updated = DB::update("
            UPDATE `{$child}` c
            JOIN `{$parent}` p ON p.`id` = c.`{$column}`
            SET c.`{$shadow}` = p.`uuid_pk`
            WHERE c.`{$column}` IS NOT NULL
              AND c.`{$shadow}` IS NULL
              AND p.`uuid_pk` IS NOT NULL
        ");

        if ($updated > 0) {
            $this->line("  <fg=green>{$child}.{$column}</> -> {$parent}: {$updated}");
        }
    }

    /**
     * Verification, and the only part of this command that can fail the deploy.
     *
     * An orphan is a child row whose foreign key points at a parent that is not
     * there. The integer schema tolerates them wherever a constraint was never
     * declared; the swap does not, because there is no uuid to point at. They are
     * reported here, while there is still time to decide what they should become,
     * rather than surfacing as a constraint violation during the cutover.
     */
    private function report($migration, string $database, array $tables): int
    {
        $problems = [];

        foreach ($tables as $table) {
            $missing = DB::table($table)->whereNull('uuid_pk')->count();
            if ($missing > 0) {
                $problems[] = ['unfilled key', "{$table}.uuid_pk", $missing];
            }
        }

        foreach ($migration->foreignKeyColumns($database) as [$child, $column, $parent]) {
            $shadow = $column.'__uuid';

            if (! Schema::hasColumn($child, $shadow)) {
                continue;
            }

            $orphans = DB::table($child)
                ->whereNotNull($column)
                ->whereNull($shadow)
                ->count();

            if ($orphans > 0) {
                $problems[] = ['orphaned rows', "{$child}.{$column} -> {$parent}", $orphans];
            }
        }

        $this->reportTypeKeyedColumns($migration);

        $this->newLine();

        if (empty($problems)) {
            $this->components->info('Every key filled and every foreign key resolved.');

            return self::SUCCESS;
        }

        $this->components->error('Rows that cannot be carried across as they stand:');
        $this->table(['problem', 'column', 'rows'], $problems);
        $this->line('  These must be resolved before stage 3. An orphan points at a parent');
        $this->line('  that no longer exists, so there is no uuid for it to become.');

        return self::FAILURE;
    }

    /**
     * The columns this stage deliberately does not carry across: an id whose parent
     * table is picked by a sibling `*_type` value.
     *
     * They are printed with the type values actually present, and the row count behind
     * each, because that is the evidence needed to settle the mapping before stage 2
     * moves them. An empty column needs no mapping at all, which is worth knowing -
     * most of these are empty on production today, and a mapping nobody has to guess
     * at is a mapping nobody can get wrong.
     */
    private function reportTypeKeyedColumns($migration): void
    {
        $rows = [];

        foreach ($migration::TYPE_KEYED_COLUMNS as [$table, $idColumn, $typeColumn]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $typeColumn)) {
                continue;
            }

            $present = DB::table($table)
                ->whereNotNull($idColumn)
                ->select($typeColumn, DB::raw('COUNT(*) AS n'))
                ->groupBy($typeColumn)
                ->pluck('n', $typeColumn);

            $rows[] = [
                "{$table}.{$idColumn}",
                $typeColumn,
                $present->isEmpty()
                    ? '(no rows - nothing to map)'
                    : $present->map(fn ($n, $t) => "{$t}={$n}")->implode(', '),
            ];
        }

        if ($rows === []) {
            return;
        }

        $this->newLine();
        $this->components->info('Type-keyed columns, deferred to stage 2:');
        $this->table(['column', 'keyed by', 'values present'], $rows);
    }

    /**
     * The stage 1 migration owns the table and foreign key inventory; this command
     * borrows it rather than keeping a second copy that could drift out of step.
     */
    private function migration(): object
    {
        return require database_path('migrations/2026_09_07_200000_add_uuid_shadow_columns.php');
    }
}
