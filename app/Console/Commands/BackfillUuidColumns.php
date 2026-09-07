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

    /** Tables that already carry a public `uuid`, whose value is reused as the key. */
    private array $existingUuidColumn = [];

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
     * A UUIDv7 carries a millisecond timestamp in its leading bits, so a row's uuid
     * is generated from its own `created_at` where the table has one. Without that,
     * every historical row would be stamped with the moment of the backfill and
     * `orderBy('id')` - which today means "oldest first" and is what most listings
     * in this app sort by - would come back in an arbitrary order after the swap.
     */
    private function backfillKeys(string $table): void
    {
        $pending = DB::table($table)->whereNull('uuid_pk')->count();

        if ($pending === 0) {
            $this->line("  <fg=gray>{$table}: nothing to fill</>");

            return;
        }

        $hasCreatedAt = Schema::hasColumn($table, 'created_at');
        $source = $this->reusableUuidColumn($table);
        $bar = $this->output->createProgressBar($pending);
        $bar->setFormat("  {$table}: %current%/%max% [%bar%] %elapsed%");

        do {
            $columns = array_filter(['id', 'uuid_pk', $source, $hasCreatedAt ? 'created_at' : null]);

            $rows = DB::table($table)
                ->whereNull('uuid_pk')
                ->orderBy('id')
                ->limit((int) $this->option('chunk'))
                ->get($columns);

            if ($rows->isEmpty()) {
                break;
            }

            DB::transaction(function () use ($rows, $table, $source, $hasCreatedAt) {
                foreach ($rows as $row) {
                    // Reuse the uuid this row is already known by rather than minting
                    // a second identity for it: those values are in URLs and in other
                    // people's systems, and the point of stage 3 is that they keep
                    // resolving to the same row afterwards.
                    $uuid = $source && ! empty($row->{$source}) && Str::isUuid($row->{$source})
                        ? $row->{$source}
                        : (string) Str::uuid7($hasCreatedAt && $row->created_at
                            ? new \DateTimeImmutable($row->created_at)
                            : null);

                    DB::table($table)->where('id', $row->id)->update([
                        'uuid_pk' => $uuid,
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
     * The public `uuid` column on the handful of tables that already have one.
     */
    private function reusableUuidColumn(string $table): ?string
    {
        return $this->existingUuidColumn[$table] ??=
            (Schema::hasColumn($table, 'uuid') ? 'uuid' : null);
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
