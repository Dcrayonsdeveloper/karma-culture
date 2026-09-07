<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 1 of the move to UUID primary keys: put a UUID beside every integer key,
 * and beside every foreign key that points at one. Nothing reads these columns yet.
 *
 * The application keeps running entirely on the integer keys after this migration.
 * That is the point - the risky half of a primary-key swap is the data, not the
 * DDL, so the data moves first, under a name nothing depends on, where it can be
 * checked at leisure. Stage 3 later promotes `uuid_pk` to `id` and `<fk>__uuid` to
 * `<fk>`, at which point no column has changed name and every relationship, every
 * `exists:` rule and every query in the app still refers to exactly what it did.
 *
 * The schema is read from information_schema at run time rather than from a list
 * baked in here, because production has had migrations applied to it directly and
 * cannot be assumed to match what this repository would build from scratch.
 *
 * Columns are added NULL and left empty; `php artisan uuid:backfill` fills them.
 * Splitting it that way keeps the deploy's DDL fast and makes the data pass
 * re-runnable, which matters on a shared box where it may need interrupting.
 */
return new class extends Migration
{
    /**
     * Laravel's own tables. The cache, session and queue drivers write these with
     * key types of their own choosing, so they are not ours to convert.
     */
    private const FRAMEWORK_TABLES = [
        'migrations', 'cache', 'cache_locks', 'sessions',
        'password_reset_tokens', 'jobs', 'job_batches', 'failed_jobs',
    ];

    /**
     * Foreign keys the database does not know about: `*_id` columns carrying no
     * constraint. A migration driven off information_schema's foreign keys alone
     * would skip every one of these and leave the rows unreachable after the swap,
     * so they are named explicitly.
     *
     * Only the columns whose target is provable from the code are listed. The
     * genuinely polymorphic ones - resolved through a sibling `*_type` column - are
     * handled in stage 2 once each type value has been mapped to a table; guessing
     * at those would silently mis-point live rows.
     */
    public const UNCONSTRAINED_FKS = [
        ['barcodes', 'variant_id', 'product_variants'],
        ['cart_items', 'variant_id', 'product_variants'],
        ['inventory_movements', 'variant_id', 'product_variants'],
        ['inventory_stocks', 'variant_id', 'product_variants'],
        ['order_items', 'variant_id', 'product_variants'],
        ['pos_return_items', 'variant_id', 'product_variants'],
        ['pos_sale_items', 'variant_id', 'product_variants'],
        ['product_images', 'variant_id', 'product_variants'],
        ['wishlists', 'variant_id', 'product_variants'],
        ['pos_audit_log', 'store_id', 'stores'],
        // `sessions` keeps its own varchar key, but it stores a user's id, and that
        // one is converting - so the column travels with users or logins break.
        ['sessions', 'user_id', 'users'],
    ];

    public function up(): void
    {
        $database = DB::getDatabaseName();

        foreach ($this->convertibleTables($database) as $table) {
            if (! Schema::hasColumn($table, 'uuid_pk')) {
                Schema::table($table, function ($t) {
                    $t->char('uuid_pk', 36)->nullable()->after('id');
                });
                // Unique, not the primary key: stage 3 promotes it. A unique index
                // ignores NULLs, so the backfill can run in chunks without tripping
                // over a half-populated table.
                Schema::table($table, function ($t) use ($table) {
                    $t->unique('uuid_pk', $this->indexName($table, 'uuid_pk', 'unique'));
                });
            }

            if (! Schema::hasColumn($table, 'legacy_id')) {
                Schema::table($table, function ($t) {
                    $t->unsignedBigInteger('legacy_id')->nullable()->after('uuid_pk');
                });
                Schema::table($table, function ($t) use ($table) {
                    $t->index('legacy_id', $this->indexName($table, 'legacy_id', 'index'));
                });
            }
        }

        foreach ($this->foreignKeyColumns($database) as [$childTable, $column]) {
            $shadow = $column.'__uuid';

            if (Schema::hasColumn($childTable, $shadow)) {
                continue;
            }

            Schema::table($childTable, function ($t) use ($column, $shadow) {
                $t->char($shadow, 36)->nullable()->after($column);
            });
            Schema::table($childTable, function ($t) use ($childTable, $shadow) {
                $t->index($shadow, $this->indexName($childTable, $shadow, 'index'));
            });
        }
    }

    public function down(): void
    {
        $database = DB::getDatabaseName();

        foreach ($this->foreignKeyColumns($database) as [$childTable, $column]) {
            $shadow = $column.'__uuid';
            if (Schema::hasColumn($childTable, $shadow)) {
                Schema::table($childTable, fn ($t) => $t->dropColumn($shadow));
            }
        }

        foreach ($this->convertibleTables($database) as $table) {
            foreach (['uuid_pk', 'legacy_id'] as $column) {
                if (Schema::hasColumn($table, $column)) {
                    Schema::table($table, fn ($t) => $t->dropColumn($column));
                }
            }
        }
    }

    /**
     * Every table whose primary key is a single auto-increment `id`, minus Laravel's.
     */
    public function convertibleTables(string $database): array
    {
        return collect(DB::select("
            SELECT t.TABLE_NAME AS tbl
            FROM information_schema.TABLES t
            JOIN information_schema.COLUMNS c
              ON c.TABLE_SCHEMA = t.TABLE_SCHEMA
             AND c.TABLE_NAME  = t.TABLE_NAME
             AND c.COLUMN_KEY  = 'PRI'
             AND c.COLUMN_NAME = 'id'
             AND c.EXTRA       = 'auto_increment'
            WHERE t.TABLE_SCHEMA = ? AND t.TABLE_TYPE = 'BASE TABLE'
            ORDER BY t.TABLE_NAME
        ", [$database]))
            ->pluck('tbl')
            ->reject(fn ($t) => in_array($t, self::FRAMEWORK_TABLES, true))
            ->values()
            ->all();
    }

    /**
     * Declared foreign keys pointing at a converting table, plus the undeclared ones
     * above. Returns [child table, child column, parent table] triples.
     */
    public function foreignKeyColumns(string $database): array
    {
        $convertible = $this->convertibleTables($database);

        $declared = collect(DB::select("
            SELECT k.TABLE_NAME AS child, k.COLUMN_NAME AS col, k.REFERENCED_TABLE_NAME AS parent
            FROM information_schema.KEY_COLUMN_USAGE k
            WHERE k.TABLE_SCHEMA = ?
              AND k.REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY k.TABLE_NAME, k.COLUMN_NAME
        ", [$database]))
            ->map(fn ($r) => [$r->child, $r->col, $r->parent]);

        return $declared
            ->concat(self::UNCONSTRAINED_FKS)
            ->filter(fn ($e) => in_array($e[2], $convertible, true))
            ->filter(fn ($e) => Schema::hasTable($e[0]) && Schema::hasColumn($e[0], $e[1]))
            ->unique(fn ($e) => $e[0].'.'.$e[1])
            ->values()
            ->all();
    }

    /**
     * MySQL caps identifiers at 64 characters and several table names here are long
     * enough that the conventional `<table>_<column>_index` overflows it.
     */
    private function indexName(string $table, string $column, string $kind): string
    {
        $name = "{$table}_{$column}_{$kind}";

        return strlen($name) <= 64 ? $name : substr($table, 0, 20).'_'.substr(md5($name), 0, 16).'_'.$kind;
    }
};
