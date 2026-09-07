<?php

namespace App\Console\Commands;

use App\Support\ImageWebp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Re-encode the files that are named .webp but are not WebP.
 *
 * Most of this shop's image library is a lie. Of the files carrying a .webp
 * extension, 6,088 hold JPEG data and 8 hold PNG - about 1.74 GB of it - and
 * only 27 are actually WebP. Something upstream renamed the files without ever
 * encoding them. Browsers sniff the content and render them anyway, and nginx
 * labels them image/webp on the way out, so nothing ever looked broken; the
 * shop has simply been shipping full-size JPEGs under a name that promises
 * otherwise. Measured over a sample of them, encoding for real gives back
 * about 40% of those bytes.
 *
 * This is the cheapest large win available here, because the FILENAME DOES NOT
 * CHANGE. Every database row, every cached fragment and every <img> already
 * points at `x.webp`; the only thing that changes is what is inside it. No
 * repointing, no view edits, nothing to coordinate.
 *
 * It is also the only genuinely destructive step in the WebP work, which is why
 * it lives in its own command instead of being folded into images:webp. These
 * files are excluded from git and exist nowhere else, so every original is
 * copied into a backup tree before it is replaced, and --restore puts them all
 * back. Deleting that backup is a separate, deliberate act.
 *
 * As everywhere else in this work, the image is never resized: same width,
 * same height, same aspect ratio. That is verified on the re-encoded file
 * before the original is allowed to go.
 */
class RecodeMislabelledWebp extends Command
{
    protected $signature = 'images:webp-recode
        {--dry-run : Report what would be re-encoded without writing anything}
        {--quality=80 : WebP quality}
        {--min-gain=3 : Percent smaller the real WebP must be before it replaces the file}
        {--limit=0 : Stop after this many files, for a staged rollout}
        {--path= : Limit the walk to one subdirectory of the uploads disk}
        {--backup=webp-recode-backup : Directory on the local disk to copy originals into}
        {--restore : Put every backed-up original back and stop}';

    protected $description = 'Re-encode files named .webp that actually contain JPEG or PNG data';

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('This PHP build has no WebP support in GD. Nothing to do.');

            return Command::FAILURE;
        }

        $backupRoot = rtrim(Storage::disk('local')->path((string) $this->option('backup')), '/\\');

        if ($this->option('restore')) {
            return $this->restore($backupRoot);
        }

        $dry = (bool) $this->option('dry-run');
        $quality = (int) $this->option('quality');
        $minGain = (float) $this->option('min-gain');
        $limit = (int) $this->option('limit');

        $root = rtrim(Storage::disk('public')->path(''), '/\\').'/';
        $base = $root.trim((string) $this->option('path'), '/');

        if (! is_dir($base)) {
            $this->error("Not a directory: {$base}");

            return Command::FAILURE;
        }

        $this->info('Scanning for .webp files that are not actually WebP...');
        $targets = $this->mislabelled($base);

        if ($targets === []) {
            $this->info('Every .webp file on the disk really is WebP. Nothing to do.');

            return Command::SUCCESS;
        }

        if ($limit > 0) {
            $targets = array_slice($targets, 0, $limit);
            $this->warn("Limited to {$limit} file(s) - this is a partial run.");
        }

        $this->info(sprintf(
            '%s %d mislabelled file%s at quality %d (min gain %.0f%%).',
            $dry ? 'Would re-encode' : 'Re-encoding',
            count($targets),
            count($targets) === 1 ? '' : 's',
            $quality,
            $minGain
        ));

        if (! $dry) {
            $this->line('Originals are copied to '.$backupRoot.' first.');
        }

        $bar = $this->output->createProgressBar(count($targets));
        $bar->start();

        $recoded = 0;
        $skipped = 0;
        $failed = 0;
        $before = 0;
        $after = 0;
        $problems = [];

        foreach ($targets as $path) {
            $bar->advance();
            $source = filesize($path);

            if ($dry) {
                $before += $source;
                $recoded++;

                continue;
            }

            // Written beside the target so the replacement is a rename on the
            // same filesystem, and therefore atomic - a reader either sees the
            // whole old file or the whole new one, never a half-written image.
            $temporary = $path.'.recode-tmp';
            $size = ImageWebp::encode($path, $temporary, $quality, $quality, $minGain);

            if ($size === false) {
                // Either GD could not read it or the real WebP was no smaller.
                // Both mean the file stays exactly as it is.
                if (is_file($temporary)) {
                    @unlink($temporary);
                }

                $skipped++;

                continue;
            }

            if (! $this->backup($root, $path, $backupRoot)) {
                @unlink($temporary);
                $failed++;

                if (count($problems) < 10) {
                    $problems[] = 'could not back up '.$this->rel($root, $path);
                }

                continue;
            }

            if (! @rename($temporary, $path)) {
                @unlink($temporary);
                $failed++;

                if (count($problems) < 10) {
                    $problems[] = 'could not replace '.$this->rel($root, $path);
                }

                continue;
            }

            $before += $source;
            $after += $size;
            $recoded++;
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['outcome', 'files'], [
            [$dry ? 'would re-encode' : 're-encoded in place', $recoded],
            ['left alone (real WebP was no smaller, or unreadable)', $skipped],
            ['failed', $failed],
        ]);

        if ($before > 0) {
            $this->info(sprintf(
                $dry ? 'Those files currently occupy %s.' : 'Bytes: %s -> %s, saving %s (%.1f%%).',
                $this->human($before),
                $this->human($after),
                $this->human($before - $after),
                $before > 0 && $after > 0 ? (1 - $after / $before) * 100 : 0
            ));
        }

        if ($problems !== []) {
            $this->newLine();
            $this->warn('Problems:');

            foreach ($problems as $p) {
                $this->line('  '.$p);
            }
        }

        if (! $dry && $recoded > 0) {
            $this->newLine();
            $this->line('To undo: php artisan images:webp-recode --restore --backup='.$this->option('backup'));
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Every .webp file whose bytes say JPEG or PNG.
     *
     * getimagesize() reads the header rather than trusting the name, which is
     * the whole point - the name is what is wrong.
     */
    private function mislabelled(string $base): array
    {
        $out = [];
        $walk = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($walk as $file) {
            if (! $file->isFile() || strtolower($file->getExtension()) !== 'webp') {
                continue;
            }

            $info = @getimagesize($file->getPathname());

            if ($info === false) {
                continue;
            }

            if ($info[2] === IMAGETYPE_JPEG || $info[2] === IMAGETYPE_PNG) {
                $out[] = $file->getPathname();
            }
        }

        sort($out);

        return $out;
    }

    /** Copy one original into the backup tree, keeping its path below the disk root. */
    private function backup(string $root, string $path, string $backupRoot): bool
    {
        $destination = $backupRoot.'/'.$this->rel($root, $path);
        $directory = dirname($destination);

        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            return false;
        }

        // An existing backup is from an earlier run and is the older, more
        // original file - never overwrite it with an already-processed one.
        if (is_file($destination)) {
            return true;
        }

        return @copy($path, $destination);
    }

    /** Put every backed-up original back where it came from. */
    private function restore(string $backupRoot): int
    {
        if (! is_dir($backupRoot)) {
            $this->error("No backup directory at {$backupRoot}");

            return Command::FAILURE;
        }

        $root = rtrim(Storage::disk('public')->path(''), '/\\').'/';
        $walk = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($backupRoot, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $restored = 0;
        $failed = 0;

        foreach ($walk as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $target = $root.substr($file->getPathname(), strlen($backupRoot) + 1);

            if (@copy($file->getPathname(), $target)) {
                $restored++;
            } else {
                $failed++;
            }
        }

        $this->info("Restored {$restored} file(s).".($failed > 0 ? " {$failed} failed." : ''));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
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
