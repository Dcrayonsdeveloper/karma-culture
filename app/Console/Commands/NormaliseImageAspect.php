<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ImageWebp;
use GdImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Give one department's product stills a single size and a single shape.
 *
 * The Kids shelves were photographed to no fixed brief: measured on the live
 * disk, 291 of the 293 stills are 1920x2379, one is 1920x2390 and one is
 * 1217x1424 - three sizes and three aspect ratios (0.807, 0.803, 0.855), none
 * of them the 3:4 the storefront is built around. The PDP frame and the
 * listing card both declare `aspect-ratio: 3/4` with `object-fit: cover`, so
 * every one of those files was already being cropped at render time, by a
 * different amount each, and the thumbnail rail beside them inherited the same
 * drift. Normalising the FILES is what makes the shelf line up, because it
 * moves the decision out of the browser and into one place.
 *
 * Cover-crop, then resample. Not letterbox: padding a picture out to 3:4 would
 * hand the cover frame bars to crop straight back off, which is a worse
 * version of the problem this fixes. Cropping here shows exactly what the
 * shopper was already being shown, at a size the whole catalogue agrees on.
 *
 * The FILENAME DOES NOT CHANGE, which is the same reason images:webp-recode
 * works in place: every database row, cached fragment and <img> on the site
 * already points at `x.webp`, and asset_v() fingerprints by mtime, so
 * rewriting the bytes busts its own cache and there is nothing to coordinate.
 *
 * It is destructive, so it behaves like the recode command it borrows from:
 * every original is copied into a backup tree before it is replaced, --restore
 * puts them all back, and deleting that backup is a separate deliberate act.
 */
class NormaliseImageAspect extends Command
{
    protected $signature = 'images:normalise-aspect
        {--category=kids : Slug of the category whose products are processed, descendants included}
        {--all : Every product still on the site, whatever shelf it sits on}
        {--width=510 : Target width in pixels}
        {--height=680 : Target height in pixels}
        {--quality=80 : WebP quality for files stored as .webp}
        {--dry-run : Report what would change without writing anything}
        {--limit=0 : Stop after this many files, for a staged rollout}
        {--backup=aspect-backup : Directory on the local disk to copy originals into}
        {--restore : Put every backed-up original back and stop}';

    protected $description = 'Crop and resample a category of product stills to one exact size and aspect ratio';

    /**
     * Where a vertical crop takes its excess from.
     *
     * A quarter off the top and three quarters off the bottom, not half and
     * half. These are photographs of people: the face sits near the top of the
     * frame and the floor near the bottom, so a centred crop spends half its
     * budget on the half nobody is buying. Horizontal excess IS taken evenly -
     * a garment is centred in frame left to right.
     */
    private const TOP_BIAS = 0.25;

    /** Decompression-bomb guard, the same ceiling ImageWebp works to. */
    private const MAX_MEGAPIXELS = 60;

    public function handle(): int
    {
        $backupRoot = rtrim(Storage::disk('local')->path((string) $this->option('backup')), '/\\');

        if ($this->option('restore')) {
            return $this->restore($backupRoot);
        }

        if (! function_exists('imagewebp')) {
            $this->error('This PHP build has no WebP support in GD. Nothing to do.');

            return Command::FAILURE;
        }

        $targetWidth = (int) $this->option('width');
        $targetHeight = (int) $this->option('height');

        if ($targetWidth < 1 || $targetHeight < 1) {
            $this->error('Width and height must both be positive.');

            return Command::FAILURE;
        }

        $everything = (bool) $this->option('all');
        $productIds = [];

        // Videos keep their own shape - a poster frame is cropped by the
        // player, not by us - so only stills are ever collected here.
        $query = ProductImage::where('media_type', 'image');

        if ($everything) {
            $this->info('Every product still on the site, whatever shelf it sits on.');
        } else {
            $slug = (string) $this->option('category');
            $categoryIds = $this->categoryTree($slug);

            if ($categoryIds === []) {
                $this->error('No category with slug "'.$slug.'".');

                return Command::FAILURE;
            }

            $this->info(sprintf(
                'Category "%s" and its descendants: %d shelves (%s).',
                $slug,
                count($categoryIds),
                implode(', ', $categoryIds)
            ));

            $productIds = $this->productsOn($categoryIds);

            if ($productIds === []) {
                $this->warn('No products on those shelves. Nothing to do.');

                return Command::SUCCESS;
            }

            $query->whereIn('product_id', $productIds);
        }

        $rows = $query->get(['id', 'product_id', 'url']);

        $root = rtrim(Storage::disk('public')->path(''), '/\\').'/';
        $paths = [];
        $offDisk = 0;

        foreach ($rows as $row) {
            $path = $this->diskPath($root, $row->url);

            if ($path === null) {
                $offDisk++;

                continue;
            }

            // Two products can share one file. Keyed by path so it is read,
            // cropped and rewritten once.
            $paths[$path] = true;
        }

        $paths = array_keys($paths);
        sort($paths);

        $this->info(sprintf(
            '%s, %d stills, %d files on disk.%s',
            $everything ? $rows->pluck('product_id')->unique()->count().' products' : count($productIds).' products',
            $rows->count(),
            count($paths),
            $offDisk > 0 ? ' '.$offDisk.' row(s) point off this disk and are left alone.' : ''
        ));

        if ($paths === []) {
            return Command::SUCCESS;
        }

        if (! $everything) {
            $shared = $this->sharedWithOtherProducts($paths, $productIds, $root);

            if ($shared > 0) {
                $this->warn($shared.' of those files are also used by products outside this category - they change there too.');
            }
        }

        $dry = (bool) $this->option('dry-run');
        $quality = (int) $this->option('quality');

        // Measured up front, in one pass, so that a file already the right
        // shape drops out HERE rather than inside the write loop. That is what
        // makes --limit mean "the next N that still need work": a staged
        // rollout over 7,000 files makes real progress on every run instead of
        // spending each one re-deciding about the batch before it.
        $this->line('Measuring '.count($paths).' file(s)...');

        $targets = [];
        $already = 0;
        $skipped = 0;
        $shapes = [];
        $problems = [];

        foreach ($paths as $path) {
            $info = @getimagesize($path);

            if ($info === false) {
                $skipped++;
                $this->note($problems, 'unreadable: '.$this->rel($root, $path));

                continue;
            }

            $shape = $info[0].'x'.$info[1];
            $shapes[$shape] = ($shapes[$shape] ?? 0) + 1;

            // Idempotent: a second run over a normalised shelf is a no-op, and
            // re-encoding an already-correct file would only lose a generation.
            if ($info[0] === $targetWidth && $info[1] === $targetHeight) {
                $already++;

                continue;
            }

            if (($info[0] * $info[1]) > self::MAX_MEGAPIXELS * 1000000) {
                $skipped++;
                $this->note($problems, 'too large to decode safely: '.$this->rel($root, $path));

                continue;
            }

            // Flattening an animation to its first frame is a visible
            // regression, not a normalisation.
            if ($info[2] === IMAGETYPE_GIF && ImageWebp::isAnimatedGif($path)) {
                $skipped++;
                $this->note($problems, 'animated gif left alone: '.$this->rel($root, $path));

                continue;
            }

            $targets[$path] = $info;
        }

        arsort($shapes);
        $this->line(sprintf(
            '%d distinct shape(s) on disk; %d file(s) already %dx%d.',
            count($shapes),
            $already,
            $targetWidth,
            $targetHeight
        ));

        foreach (array_slice($shapes, 0, 12, true) as $shape => $count) {
            $this->line(sprintf('  %-14s %d', $shape, $count));
        }

        if ($targets === []) {
            $this->newLine();
            $this->info('Nothing left to normalise.');

            return Command::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $outstanding = count($targets);

        if ($limit > 0 && $outstanding > $limit) {
            $targets = array_slice($targets, 0, $limit, true);
            $this->warn(sprintf(
                'Limited to %d of %d outstanding file(s) - this is a partial run, %d will remain.',
                $limit,
                $outstanding,
                $outstanding - $limit
            ));
        }

        $this->newLine();

        $this->info(sprintf(
            '%s to %dx%d (%s).',
            $dry ? 'Would normalise' : 'Normalising',
            $targetWidth,
            $targetHeight,
            $this->ratioLabel($targetWidth, $targetHeight)
        ));

        if (! $dry) {
            $this->line('Originals are copied to '.$backupRoot.' first.');
        }

        $bar = $this->output->createProgressBar(count($targets));
        $bar->start();

        $done = 0;
        $failed = 0;
        $before = 0;
        $after = 0;

        foreach ($targets as $path => $info) {
            $bar->advance();

            $source = filesize($path);

            if ($dry) {
                $before += $source;
                $done++;

                continue;
            }

            // Written beside the target so the replacement is a rename on the
            // same filesystem, and therefore atomic - a reader gets the whole
            // old file or the whole new one, never a half-written image.
            $temporary = $path.'.aspect-tmp';
            $size = $this->rewrite($path, $temporary, $info, $targetWidth, $targetHeight, $quality);

            if ($size === false) {
                if (is_file($temporary)) {
                    @unlink($temporary);
                }

                $failed++;
                $this->note($problems, 'could not re-encode: '.$this->rel($root, $path));

                continue;
            }

            if (! $this->backup($root, $path, $backupRoot)) {
                @unlink($temporary);
                $failed++;
                $this->note($problems, 'could not back up: '.$this->rel($root, $path));

                continue;
            }

            if (! @rename($temporary, $path)) {
                @unlink($temporary);
                $failed++;
                $this->note($problems, 'could not replace: '.$this->rel($root, $path));

                continue;
            }

            $before += $source;
            $after += $size;
            $done++;
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['outcome', 'files'], [
            [$dry ? 'would normalise' : 'normalised in place', $done],
            ['already '.$targetWidth.'x'.$targetHeight, $already],
            ['left alone', $skipped],
            ['failed', $failed],
        ]);

        if ($before > 0 && ! $dry) {
            $this->info(sprintf(
                'Bytes: %s -> %s (%s%s).',
                $this->human($before),
                $this->human($after),
                $after <= $before ? '-' : '+',
                $this->human((int) abs($before - $after))
            ));
        }

        if ($problems !== []) {
            $this->newLine();
            $this->warn('Problems:');

            foreach ($problems as $problem) {
                $this->line('  '.$problem);
            }
        }

        if (! $dry && $done > 0) {
            $this->newLine();
            $this->line('To undo: php artisan images:normalise-aspect --restore --backup='.$this->option('backup'));
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Crop to the target ratio, resample into the target box, write the result.
     *
     * Returns the byte size of what was written, or false if any step failed -
     * in which case the caller leaves the original exactly as it is.
     */
    private function rewrite(
        string $source,
        string $target,
        array $info,
        int $targetWidth,
        int $targetHeight,
        int $quality
    ): int|false {
        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_GIF => @imagecreatefromgif($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            default => null,
        };

        if (! $image) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $targetRatio = $targetWidth / $targetHeight;

        if ($width / $height > $targetRatio) {
            // Too wide. Take the excess off the sides, evenly.
            $cropHeight = $height;
            $cropWidth = min($width, (int) round($height * $targetRatio));
            $cropX = intdiv($width - $cropWidth, 2);
            $cropY = 0;
        } else {
            // Too tall. Take it mostly off the bottom - see TOP_BIAS.
            $cropWidth = $width;
            $cropHeight = min($height, (int) round($width / $targetRatio));
            $cropX = 0;
            $cropY = (int) round(($height - $cropHeight) * self::TOP_BIAS);
        }

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        // Carried across explicitly, or a PNG with a cut-out comes back sitting
        // on a black rectangle.
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        $resampled = imagecopyresampled(
            $canvas,
            $image,
            0,
            0,
            $cropX,
            $cropY,
            $targetWidth,
            $targetHeight,
            $cropWidth,
            $cropHeight
        );

        imagedestroy($image);

        if (! $resampled) {
            imagedestroy($canvas);

            return false;
        }

        // The file keeps the format its NAME promises, whatever its bytes said
        // going in. Much of this library is .webp holding JPEG data (see
        // images:webp-recode); normalising is a chance to make the two agree,
        // and nginx already labels these image/webp on the way out.
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $flattened = null;

        if ($extension === 'jpg' || $extension === 'jpeg') {
            $flattened = $this->onWhite($canvas, $targetWidth, $targetHeight);
        }

        $written = match ($extension) {
            'webp' => @imagewebp($canvas, $target, $quality),
            'png' => @imagepng($canvas, $target),
            'gif' => @imagegif($canvas, $target),
            default => @imagejpeg($flattened ?? $canvas, $target, 90),
        };

        imagedestroy($canvas);

        if ($flattened instanceof GdImage) {
            imagedestroy($flattened);
        }

        if (! $written) {
            return false;
        }

        clearstatcache(true, $target);

        // Belt and braces: read it back and confirm the geometry is the one
        // that was asked for. A wrong size here would be invisible in a file
        // listing and obvious on the shelf.
        $check = @getimagesize($target);

        if ($check === false || $check[0] !== $targetWidth || $check[1] !== $targetHeight) {
            @unlink($target);

            return false;
        }

        return filesize($target);
    }

    /** JPEG has no alpha, so transparency flattens onto white rather than black. */
    private function onWhite(GdImage $canvas, int $width, int $height): GdImage
    {
        $flat = imagecreatetruecolor($width, $height);
        imagefilledrectangle($flat, 0, 0, $width, $height, imagecolorallocate($flat, 255, 255, 255));
        imagealphablending($flat, true);
        imagecopy($flat, $canvas, 0, 0, 0, 0, $width, $height);

        return $flat;
    }

    /** A category's id and every id beneath it, however deep the tree goes. */
    private function categoryTree(string $slug): array
    {
        $root = Category::where('slug', $slug)->first();

        if (! $root) {
            return [];
        }

        $ids = [$root->id];
        $frontier = [$root->id];

        while ($frontier !== []) {
            $children = Category::whereIn('parent_id', $frontier)
                ->whereNotIn('id', $ids)
                ->pluck('id')
                ->all();

            if ($children === []) {
                break;
            }

            $ids = array_merge($ids, $children);
            $frontier = $children;
        }

        return $ids;
    }

    /**
     * Products on those shelves, by both routes.
     *
     * category_id is what a product IS, and category_product is every shelf it
     * is listed on. The model syncs the first into the second on save, but a
     * row written before that hook existed - or by an importer going straight
     * to the table - can sit in only one of them, and a still that gets missed
     * is exactly the odd one out this command exists to remove.
     *
     * @return array<int, int>
     */
    private function productsOn(array $categoryIds): array
    {
        $viaPivot = Product::inAnyCategory($categoryIds)->pluck('id')->all();
        $viaColumn = Product::whereIn('category_id', $categoryIds)->pluck('id')->all();

        return array_values(array_unique(array_merge($viaPivot, $viaColumn)));
    }

    /** How many of these files are also carried by a product on another shelf. */
    private function sharedWithOtherProducts(array $paths, array $productIds, string $root): int
    {
        if ($paths === []) {
            return 0;
        }

        $wanted = array_flip($paths);
        $shared = 0;

        ProductImage::whereNotIn('product_id', $productIds)
            ->where('media_type', 'image')
            ->select(['id', 'url'])
            ->chunkById(500, function ($rows) use (&$shared, &$wanted, $root) {
                foreach ($rows as $row) {
                    $path = $this->diskPath($root, $row->url);

                    if ($path !== null && isset($wanted[$path])) {
                        $shared++;
                        unset($wanted[$path]);
                    }
                }
            });

        return $shared;
    }

    /**
     * The absolute path a stored url points at, or null if it is not ours.
     *
     * Rows hold either "/storage/products/x.webp" or a bare "products/x.webp" -
     * ProductImage::resolveUrl() normalises the same two shapes on the way out.
     * An absolute URL belongs to somebody else's server.
     */
    private function diskPath(string $root, ?string $url): ?string
    {
        if (! $url || str_starts_with($url, 'http')) {
            return null;
        }

        $relative = ltrim((string) preg_replace('#^/?storage/#', '', $url), '/');

        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $path = $root.$relative;

        return is_file($path) ? $path : null;
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
            $this->error('No backup directory at '.$backupRoot);

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

        $this->info('Restored '.$restored.' file(s).'.($failed > 0 ? ' '.$failed.' failed.' : ''));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function note(array &$problems, string $message): void
    {
        if (count($problems) < 10) {
            $problems[] = $message;
        }
    }

    private function ratioLabel(int $width, int $height): string
    {
        $divisor = $this->gcd($width, $height);

        return ((int) ($width / $divisor)).':'.((int) ($height / $divisor));
    }

    private function gcd(int $a, int $b): int
    {
        return $b === 0 ? max($a, 1) : $this->gcd($b, $a % $b);
    }

    private function rel(string $root, string $path): string
    {
        return str_replace($root, '', $path);
    }

    private function human(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return round($size, 1).' '.$units[$index];
    }
}
