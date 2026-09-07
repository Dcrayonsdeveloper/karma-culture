<?php

namespace App\Console\Commands;

use App\Support\ImageWebp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Write a WebP twin beside every raster on the public disk.
 *
 * This is the bulk counterpart to App\Support\ImageWebp, which handles one
 * upload at a time. The shop's images were accumulated over a long import and
 * arrived in whatever format the supplier sent, so the disk holds a few
 * thousand JPEG and PNG files next to the WebP ones the newer code writes. A
 * page that ships those originals is heavier than it needs to be, and the fix
 * is the same for all of them: encode a WebP once, offline, and let the
 * database point at it.
 *
 * Three rules shape everything below.
 *
 * The image is NEVER resized. Same width, same height, same aspect ratio -
 * only the encoding changes. That is asserted after the encode rather than
 * assumed, because a silent geometry change would reflow the storefront's
 * product grid and would be very hard to trace back to here.
 *
 * A WebP is only kept when it is genuinely smaller. WebP is not universally
 * better than JPEG: an already-optimised photo re-encoded at quality 80 can
 * come out LARGER, which is measurably true on this disk. Shipping that would
 * make the site slower while looking like an optimisation, so a twin that does
 * not beat its source by --min-gain is deleted and the original stands.
 *
 * The original is never deleted. It is the rollback, and it is the fallback for
 * the surfaces that cannot read WebP at all.
 */
class ConvertImagesToWebp extends Command
{
    protected $signature = 'images:webp
        {--dry-run : Report what would happen without writing anything}
        {--quality=80 : First-choice WebP quality}
        {--fallback-quality=72 : Retried quality when the first pass does not beat the original}
        {--min-gain=3 : Percent smaller a WebP must be before it is worth keeping}
        {--path= : Limit the walk to one subdirectory}
        {--webroot : Walk public/<path> in the web root instead of the uploads disk}
        {--force : Re-encode even where a .webp twin already exists}';

    protected $description = 'Write a same-size WebP twin beside every JPEG/PNG/GIF on the public disk';

    /**
     * Both borrowed from the encoder rather than restated, so the walk here and
     * the conversion there can never disagree about what is convertible.
     */
    private const SOURCES = ImageWebp::SOURCES;

    private const MAX_MEGAPIXELS = ImageWebp::MAX_MEGAPIXELS;

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('This PHP build has no WebP support in GD. Nothing to do.');

            return Command::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $quality = (int) $this->option('quality');
        $fallback = (int) $this->option('fallback-quality');
        $minGain = (float) $this->option('min-gain');
        $force = (bool) $this->option('force');

        // Two places hold images. Uploads live on the public disk; the logos and
        // placeholders that ship with the app live in the web root, are excluded
        // from git as binaries, and would otherwise never be reached.
        $root = $this->option('webroot')
            ? rtrim(public_path(), '/\\').'/'
            : rtrim(Storage::disk('public')->path(''), '/\\').'/';

        $base = $root.trim((string) $this->option('path'), '/');

        if (! is_dir($base)) {
            $this->error("Not a directory: {$base}");

            return Command::FAILURE;
        }

        $files = $this->rasters($base);
        $total = count($files);

        if ($total === 0) {
            $this->info('No JPEG/PNG/GIF files found.');

            return Command::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d raster file%s under %s (quality %d, retry %d, min gain %.0f%%)',
            $dry ? 'Would convert' : 'Converting',
            $total,
            $total === 1 ? '' : 's',
            rtrim(str_replace($root, '', $base), '/') ?: ($this->option('webroot') ? 'the web root' : 'the uploads disk'),
            $quality,
            $fallback,
            $minGain
        ));

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Two sources can only ever want the same twin when they differ solely
        // by extension - foo.jpg and foo.png both map to foo.webp. Whichever
        // arrived second would silently overwrite the first, so it is refused.
        $claimed = [];

        $stat = [
            'written' => 0, 'skipped_existing' => 0, 'skipped_animated' => 0,
            'skipped_huge' => 0, 'unreadable' => 0, 'not_smaller' => 0, 'collision' => 0,
        ];
        $srcBytes = 0;
        $webpBytes = 0;
        $notSmaller = [];
        $collisions = [];

        foreach ($files as $path) {
            $bar->advance();
            $target = preg_replace('/\.[^.\/]+$/', '', $path).'.webp';

            if (isset($claimed[$target])) {
                $stat['collision']++;
                $collisions[] = $this->rel($root, $path).' -> already claimed by '.$this->rel($root, $claimed[$target]);

                continue;
            }

            if (! $force && is_file($target)) {
                $stat['skipped_existing']++;
                $claimed[$target] = $path;

                continue;
            }

            $info = @getimagesize($path);

            if ($info === false) {
                $stat['unreadable']++;

                continue;
            }

            $width = $info[0];
            $height = $info[1];

            if (($width * $height) > self::MAX_MEGAPIXELS * 1000000) {
                $stat['skipped_huge']++;

                continue;
            }

            // An animated GIF would be flattened to its first frame, which is a
            // visible regression, not an optimisation. Leave it alone.
            if ($info[2] === IMAGETYPE_GIF && ImageWebp::isAnimatedGif($path)) {
                $stat['skipped_animated']++;

                continue;
            }

            $source = filesize($path);

            if ($dry) {
                $claimed[$target] = $path;
                $stat['written']++;
                $srcBytes += $source;

                continue;
            }

            $result = ImageWebp::encode($path, $target, $quality, $fallback, $minGain);

            if ($result === false) {
                $stat['not_smaller']++;

                if (count($notSmaller) < 15) {
                    $notSmaller[] = $this->rel($root, $path);
                }

                continue;
            }

            $claimed[$target] = $path;
            $stat['written']++;
            $srcBytes += $source;
            $webpBytes += $result;
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['outcome', 'files'], [
            [$dry ? 'would write' : 'twins written', $stat['written']],
            ['already had a twin', $stat['skipped_existing']],
            ['kept original (WebP was not smaller)', $stat['not_smaller']],
            ['skipped (animated GIF)', $stat['skipped_animated']],
            ['skipped (over '.self::MAX_MEGAPIXELS.' MP)', $stat['skipped_huge']],
            ['skipped (name collision)', $stat['collision']],
            ['unreadable', $stat['unreadable']],
        ]);

        if (! $dry && $srcBytes > 0) {
            $this->info(sprintf(
                'Bytes: %s of originals -> %s of WebP, saving %s (%.1f%%).',
                $this->human($srcBytes),
                $this->human($webpBytes),
                $this->human($srcBytes - $webpBytes),
                (1 - $webpBytes / $srcBytes) * 100
            ));
        }

        if ($notSmaller !== []) {
            $this->newLine();
            $this->line('Kept as-is because WebP came out no smaller (showing '.count($notSmaller).'):');

            foreach ($notSmaller as $p) {
                $this->line('  '.$p);
            }
        }

        if ($collisions !== []) {
            $this->newLine();
            $this->warn('Name collisions - these were NOT converted:');

            foreach (array_slice($collisions, 0, 15) as $c) {
                $this->line('  '.$c);
            }
        }

        return Command::SUCCESS;
    }
    /** Every convertible raster under a directory, sorted so runs are repeatable. */
    private function rasters(string $base): array
    {
        $out = [];
        $walk = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($walk as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if (in_array(strtolower($file->getExtension()), self::SOURCES, true)) {
                $out[] = $file->getPathname();
            }
        }

        sort($out);

        return $out;
    }

    private function rel(string $root, string $path): string
    {
        return str_replace($root, '', $path);
    }

    private function human(int $bytes): string
    {
        $value = (float) $bytes;

        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($value < 1024 || $unit === 'GB') {
                return sprintf('%.1f %s', $value, $unit);
            }

            $value /= 1024;
        }

        return $bytes.' B';
    }
}
