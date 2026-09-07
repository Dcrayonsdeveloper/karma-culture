<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Point every image column at the WebP twin that images:webp produced.
 *
 * The storefront renders whatever string the column holds - ProductImage's
 * resolveUrl() normalises the three shapes in use, asset_v() fingerprints it,
 * and <x-media> puts it in an <img>. So there is no view to edit: moving the
 * site onto WebP is this one UPDATE, and moving it back off is the --restore
 * below.
 *
 * Two details matter more than they look.
 *
 * The rewrite is done on the STORED STRING, not on a normalised key, so a row
 * keeps the shape it had. `/storage/products/videos/x.jpg` becomes
 * `/storage/products/videos/x.webp` and a bare `products/x.jpg` becomes
 * `products/x.webp`; rewriting both into one canonical form would work on the
 * page and quietly destroy the distinction the table has always carried.
 *
 * A row is only rewritten when the twin is actually on disk. images:webp
 * deliberately declines to write a twin that came out no smaller than its
 * source, and eight banner rows on production already point at files that are
 * missing entirely, so "converted everything" and "every row can be repointed"
 * are not the same set. Checking each one is what keeps this from turning a
 * working JPEG into a 404.
 */
class RepointImagesToWebp extends Command
{
    protected $signature = 'images:webp-repoint
        {--dry-run : Report what would change without touching a row}
        {--restore= : Path to a backup written by an earlier run, to undo it}';

    protected $description = 'Repoint image columns at their WebP twins, reversibly';

    /**
     * The columns that hold a storefront still, and nothing else.
     *
     * Everything omitted is omitted on purpose - see NOT_TOUCHED below. The
     * list is explicit rather than discovered so that a new column has to be
     * considered by a person before this command starts rewriting it.
     */
    private const COLUMNS = [
        ['product_images', 'url'],
        ['product_images', 'thumbnail_url'],
        ['categories', 'image_url'],
        ['banners', 'image_url'],
        ['banners', 'mobile_image_url'],
        ['qualities', 'image_url'],
        ['product_aplus_images', 'image_path'],
        ['review_images', 'url'],
        ['texture_presets', 'image_path'],
        ['brands', 'logo_url'],
        ['testimonials', 'avatar_url'],
    ];

    /**
     * Deliberately left alone, and why. Kept here so the next person does not
     * have to re-derive the reasoning from an empty diff.
     *
     * - banners.video_url / mobile_video_url, categories.video_url,
     *   about_reels.video_path  : MP4. Not an image.
     * - products.model_glb_path / model_usdz_path : 3D models.
     * - delivery_partners.license_document / profile_photo,
     *   seller_documents.file_url, sellers.documents, wholesalers.documents,
     *   returns.images, messages.attachments : identity documents and things
     *   customers uploaded. Re-encoding somebody's KYC scan or a returns photo
     *   is not ours to do, and a claim may need the untouched original.
     * - seo_metadata.og_image : social scrapers handle WebP badly; an og:image
     *   that Facebook will not render costs more than the bytes it saves.
     * - categories.icon, users.avatar_url : no rows carry a value.
     */
    private const SOURCES = ['jpg', 'jpeg', 'png', 'gif'];

    public function handle(): int
    {
        if ($restore = $this->option('restore')) {
            return $this->restore($restore);
        }

        $dry = (bool) $this->option('dry-run');
        $disk = Storage::disk('public');

        $planned = [];
        $rows = [];
        $skippedMissing = 0;
        $skippedExternal = 0;
        $alreadyWebp = 0;

        foreach (self::COLUMNS as [$table, $column]) {
            if (! $this->tableHasColumn($table, $column)) {
                $this->warn("Skipping {$table}.{$column} - not present on this database.");

                continue;
            }

            $changed = 0;
            $records = DB::table($table)
                ->select('id', $column)
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->orderBy('id')
                ->get();

            foreach ($records as $record) {
                $value = (string) $record->{$column};

                if (str_starts_with($value, 'http') || str_starts_with($value, 'data:')) {
                    $skippedExternal++;

                    continue;
                }

                $extension = strtolower(pathinfo(parse_url($value, PHP_URL_PATH) ?: $value, PATHINFO_EXTENSION));

                if ($extension === 'webp') {
                    $alreadyWebp++;

                    continue;
                }

                if (! in_array($extension, self::SOURCES, true)) {
                    continue;
                }

                // Rewrite the stored string itself, so `/storage/` prefixes and
                // bare keys each survive as what they were.
                $next = preg_replace('/\.[^.\/]+$/', '.webp', $value);
                $key = ltrim(preg_replace('#^/?storage/#', '', $next), '/');

                if (! $disk->exists($key)) {
                    $skippedMissing++;

                    continue;
                }

                $changed++;
                $rows[] = [
                    'table' => $table,
                    'column' => $column,
                    'id' => $record->id,
                    'from' => $value,
                    'to' => $next,
                ];
            }

            if ($changed > 0) {
                $planned[] = [$table.'.'.$column, $records->count(), $changed];
            }
        }

        if ($rows === []) {
            $this->info('Nothing to repoint - every image column already points at a file that is WebP or has no twin.');

            return Command::SUCCESS;
        }

        $this->table(['column', 'rows with a value', $dry ? 'would repoint' : 'to repoint'], $planned);
        $this->line(sprintf(
            'Left alone: %d already WebP, %d external URLs, %d with no twin on disk.',
            $alreadyWebp,
            $skippedExternal,
            $skippedMissing
        ));

        if ($dry) {
            $this->newLine();
            $this->line('Sample of what would change:');

            foreach (array_slice($rows, 0, 8) as $r) {
                $this->line("  {$r['table']}#{$r['id']}  {$r['from']}  ->  {$r['to']}");
            }

            $this->newLine();
            $this->info('Dry run - nothing was written.');

            return Command::SUCCESS;
        }

        $backup = 'webp-repoint-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($backup, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('Backup written to '.Storage::disk('local')->path($backup));

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        DB::transaction(function () use ($rows, $bar) {
            foreach ($rows as $r) {
                DB::table($r['table'])->where('id', $r['id'])->update([$r['column'] => $r['to']]);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info(sprintf('Repointed %d rows onto WebP.', count($rows)));
        $this->line('To undo: php artisan images:webp-repoint --restore='.Storage::disk('local')->path($backup));

        return Command::SUCCESS;
    }

    /** Put every row in a backup file back the way it was. */
    private function restore(string $path): int
    {
        if (! is_file($path)) {
            $this->error("No such backup: {$path}");

            return Command::FAILURE;
        }

        $rows = json_decode((string) file_get_contents($path), true);

        if (! is_array($rows) || $rows === []) {
            $this->error('That backup is empty or unreadable.');

            return Command::FAILURE;
        }

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        DB::transaction(function () use ($rows, $bar) {
            foreach ($rows as $r) {
                DB::table($r['table'])->where('id', $r['id'])->update([$r['column'] => $r['from']]);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info(sprintf('Restored %d rows to their original paths.', count($rows)));

        return Command::SUCCESS;
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }
}
