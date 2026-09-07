<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * One place that knows how to turn a raster into WebP, and when not to.
 *
 * The shop serves a few thousand product stills and a page that ships them as
 * JPEG is heavier than it needs to be. App\Support\BannerMedia had been doing
 * this for banner artwork alone since the hero banners were built; this is the
 * same idea with the banner-shaped assumptions taken out, so every upload
 * screen can use it and the console command that backfilled the existing files
 * (images:webp) can share the encoder rather than growing a second copy of it.
 *
 * The contract, which is what the callers actually depend on:
 *
 * - The image is never resized. Same width, same height, same aspect ratio.
 *   Only the encoding changes. Asserted, not assumed - see encode().
 * - A WebP is only kept when it genuinely beats its source by MIN_GAIN. An
 *   already-optimised JPEG re-encoded at quality 80 can come out LARGER, which
 *   is true of real files on this disk, and serving that would make the site
 *   slower while looking like an optimisation.
 * - The original is never deleted. It is the rollback, and the fallback for
 *   anything downstream that cannot read WebP.
 * - Nothing here ever throws at a caller. A server without WebP, a file GD
 *   cannot decode, a full disk - none of those is a reason to refuse the
 *   upload the admin actually made, so they are logged and the original key is
 *   returned instead.
 */
class ImageWebp
{
    /** What we will convert. SVG is vector; MP4, GLB and USDZ are not images. */
    public const SOURCES = ['jpg', 'jpeg', 'png', 'gif'];

    /**
     * Quality 80 is the working point for this catalogue.
     *
     * Measured over a sample of the shop's own files: quality 82 saved 28% of
     * their bytes, 80 sits near the knee, and 75 saved 46% but starts to show
     * on flat-lay fabric detail - which for a clothing retailer is the part of
     * the picture that sells the garment.
     */
    public const QUALITY = 80;

    /** Retried once here before an image is written off as not worth converting. */
    public const FALLBACK_QUALITY = 72;

    /** Percent smaller a WebP must be before it earns its place. */
    public const MIN_GAIN = 3;

    /** Decompression-bomb guard. GD holds 4 bytes a pixel, and this box is shared. */
    public const MAX_MEGAPIXELS = 60;

    /**
     * Store an upload and hand back the key the database should point at.
     *
     * The WebP twin when there is one, the original otherwise. Callers do not
     * branch on which they got - they store the string and the storefront
     * renders it, which is what keeps the two cases from drifting apart.
     */
    public static function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $path = $file->store($directory, $disk);

        if ($path === false || $path === '') {
            return '';
        }

        return self::twin($path, $disk) ?? $path;
    }

    /**
     * Replace what one column points at, taking the previous file with it.
     *
     * Deleting first means a long series of edits does not leave the disk
     * holding every image the shop has ever shown.
     */
    public static function replace(UploadedFile $file, string $directory, ?string $previous, string $disk = 'public'): string
    {
        self::delete($previous, $disk);

        return self::store($file, $directory, $disk);
    }

    /**
     * Write a WebP beside an already-stored raster and return its key.
     *
     * Null when there is no twin to be had: not a raster, already WebP, an
     * animated GIF, unreadable, or the WebP simply was not smaller.
     */
    public static function twin(string $path, string $disk = 'public'): ?string
    {
        try {
            $path = self::normalise($path);
            $storage = Storage::disk($disk);
            $source = $storage->path($path);

            if (! is_file($source) || ! function_exists('imagewebp')) {
                return null;
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if (! in_array($extension, self::SOURCES, true)) {
                return null;
            }

            $key = self::keyFor($path);
            $target = $storage->path($key);

            return self::encode($source, $target) !== false ? $key : null;
        } catch (\Throwable $e) {
            Log::warning('WebP derivative failed', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Encode one file on disk, retrying once at a lower quality.
     *
     * Absolute paths, so the console command can drive it over the filesystem
     * without going through a disk. Returns the twin's size in bytes, or false
     * when no twin was kept - in which case nothing is left behind.
     */
    public static function encode(
        string $source,
        string $target,
        int $quality = self::QUALITY,
        int $fallback = self::FALLBACK_QUALITY,
        float $minGain = self::MIN_GAIN
    ): int|false {
        $info = @getimagesize($source);

        if ($info === false) {
            return false;
        }

        $width = $info[0];
        $height = $info[1];

        if (($width * $height) > self::MAX_MEGAPIXELS * 1000000) {
            return false;
        }

        // Flattening an animation to its first frame is a visible regression,
        // not an optimisation.
        if ($info[2] === IMAGETYPE_GIF && self::isAnimatedGif($source)) {
            return false;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_GIF => @imagecreatefromgif($source),
            default => null,
        };

        if (! $image) {
            return false;
        }

        // Transparency has to be carried across explicitly; without these a PNG
        // logo comes back sitting on a black rectangle.
        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        // The promise that nothing is resized. imagewebp() does not resample,
        // so this should always hold - it is checked because a geometry change
        // would be invisible in a file listing and obvious on the page.
        if (imagesx($image) !== $width || imagesy($image) !== $height) {
            imagedestroy($image);

            return false;
        }

        $ceiling = filesize($source) * (1 - $minGain / 100);
        $kept = false;

        foreach ([$quality, $fallback] as $q) {
            if (! @imagewebp($image, $target, $q)) {
                continue;
            }

            clearstatcache(true, $target);

            if (is_file($target) && filesize($target) <= $ceiling) {
                $kept = true;

                break;
            }
        }

        imagedestroy($image);

        if (! $kept) {
            if (is_file($target)) {
                @unlink($target);
            }

            return false;
        }

        // Belt and braces: read the twin back and confirm the geometry survived.
        $check = @getimagesize($target);

        if ($check === false || $check[0] !== $width || $check[1] !== $height) {
            @unlink($target);

            return false;
        }

        return filesize($target);
    }

    /**
     * Remove a stored file, and the WebP twin made from it.
     *
     * Deliberately only those two. The obvious generalisation - clear every
     * sibling sharing the stem, so that deleting a repointed `x.webp` also
     * takes the `x.jpg` it came from - is not safe here: nothing guarantees
     * that `x.jpg` and `x.png` in one directory are the same picture, and
     * ImportLocalProducts names files from whatever the supplier called them.
     * Over-deleting destroys somebody's image; under-deleting leaves a stale
     * original taking up disk. Only one of those is recoverable, so this errs
     * toward leaving the orphan.
     *
     * Absolute URLs and web-root paths are left alone. The hero clip the shop
     * ships with was imported as `/images/...`, which is a real file in the
     * webroot and not this disk's to delete.
     */
    public static function delete(?string $path, string $disk = 'public'): void
    {
        if (! $path || str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return;
        }

        $path = self::normalise($path);
        $storage = Storage::disk($disk);
        $storage->delete($path);

        $twin = self::keyFor($path);

        if ($twin !== $path) {
            $storage->delete($twin);
        }
    }

    /**
     * Strip a `storage/` prefix some columns carry.
     *
     * Those values are written for the browser, which reaches the disk through
     * the public/storage symlink. Handed to the disk itself the prefix resolves
     * to storage/app/public/storage/..., which exists nowhere, so the call
     * silently does nothing - the worst kind of failure to debug.
     */
    private static function normalise(string $path): string
    {
        return ltrim(preg_replace('#^/?storage/#', '', $path), '/');
    }

    /** `products/x.jpg` -> `products/x.webp`. A sibling, so it moves with the original. */
    public static function keyFor(string $path): string
    {
        return preg_replace('/\.[^.\/]+$/', '', $path).'.webp';
    }

    /**
     * Does this GIF hold more than one frame?
     *
     * Counts Graphic Control Extension blocks.
     */
    public static function isAnimatedGif(string $path): bool
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return false;
        }

        return preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $contents) > 1;
    }
}
