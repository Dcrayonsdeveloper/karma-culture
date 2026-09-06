<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Where a banner's four files live, and how they are put there and taken away.
 *
 * Both banner screens - Marketing > Banners and Homepage > Hero Banners - write
 * the same four columns, and until this class existed they did it differently:
 * one filed mobile images into `banners/` instead of `banners/mobile/`, and it
 * replaced and deleted rows without deleting the files, so every edit left an
 * orphan on the public disk. Uploading is now one code path, so the two screens
 * cannot drift again.
 *
 * Nothing here knows about the Banner model on purpose: it takes a column name
 * and a file and answers with a path, which keeps it usable from a controller,
 * a command or a test without a row to hang off.
 */
class BannerMedia
{
    /**
     * The disk directory for each media column.
     *
     * Four directories rather than one, so a phone-sized clip is never confused
     * with the desktop one while looking at the disk.
     */
    public const DIRECTORIES = [
        'image_url' => 'banners',
        'video_url' => 'banners/video',
        'mobile_image_url' => 'banners/mobile',
        'mobile_video_url' => 'banners/mobile/video',
    ];

    /** The form field that fills each column. */
    public const FIELDS = [
        'image' => 'image_url',
        'video' => 'video_url',
        'mobile_image' => 'mobile_image_url',
        'mobile_video' => 'mobile_video_url',
    ];

    /** The two columns that hold a still, and can therefore carry a WebP twin. */
    public const IMAGE_COLUMNS = ['image_url', 'mobile_image_url'];

    /**
     * How big a banner still may be.
     *
     * 5 MB was the figure, and it was the wrong one for this form in a way that
     * only showed up on the mobile field. A desktop banner is a 1426x370 strip
     * and compresses to well under a megabyte; the phone artwork beside it is
     * 1080x720 or taller and comes out of a design tool several times heavier,
     * so the same cap refused the mobile file while accepting the desktop one -
     * which reads, from the admin's side, as "the mobile field does not work".
     *
     * 12 MB clears real banner artwork with room to spare and is still far
     * below the 256 MB the server accepts in a request. Stated here once: the
     * rules, the help text, the browser-side check and the error message all
     * read it, so the number cannot drift between what is enforced and what the
     * screen promises.
     */
    public const MAX_IMAGE_KB = 12288;

    /**
     * A hero clip.
     *
     * This was 64 MB, and a 64 MB ceiling is how an 18.6 MB file came to be the
     * home page's hero: nothing refused it, so nothing questioned it. That one
     * file is 78% of the page's weight, and it autoplays, so a phone on mobile
     * data pays for all of it before the shop is usable.
     *
     * 8 MB is still generous - a ten-second 1080p loop encoded for the web lands
     * nearer 2 MB. It is a ceiling meant to be noticed rather than met: an
     * upload that trips it wants re-encoding, not a bigger limit.
     */
    public const MAX_VIDEO_KB = 8192;

    /**
     * The pixel ceiling, a decompression-bomb guard rather than a design rule.
     *
     * Raised for banners specifically: a phone-shot or a full-bleed export
     * routinely runs past 5,000px on its long edge, and refusing that as though
     * it were an attack is the same class of mistake as the size cap above.
     */
    public const MAX_IMAGE_EDGE = 10000;

    /**
     * Store an upload for one column and hand back its disk key.
     *
     * A still also gets a WebP sibling where the server can make one, which is
     * what lets the storefront offer a smaller file to the browsers that take
     * it. The original is always kept and always what the column points at:
     * the derivative is an extra, never a replacement, so nothing breaks if it
     * cannot be produced.
     */
    public static function store(UploadedFile $file, string $column): string
    {
        $path = $file->store(self::DIRECTORIES[$column] ?? 'banners', 'public');

        if (in_array($column, self::IMAGE_COLUMNS, true)) {
            self::writeWebp($path);
        }

        return $path;
    }

    /**
     * Put a new file in one column's place, taking the old one with it.
     *
     * Returns the new key. The previous file is removed first so a long series
     * of edits does not leave the disk holding every banner the shop has ever
     * shown.
     */
    public static function replace(UploadedFile $file, string $column, ?string $previous): string
    {
        self::delete($previous);

        return self::store($file, $column);
    }

    /**
     * Remove a stored file, and its WebP twin if it has one.
     *
     * Absolute URLs and web-root paths are left alone. The hero clip the shop
     * ships with was imported as `/images/...`, which is a real file in the
     * webroot and not this disk's to delete - handing it to the public disk
     * would either miss it or, worse, hit something else of the same name.
     */
    public static function delete(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return;
        }

        $disk = Storage::disk('public');
        $disk->delete($path);
        $disk->delete(self::webpKeyFor($path));
    }

    /** Every file a banner owns, removed. Used when the row itself goes. */
    public static function deleteAll(array $paths): void
    {
        foreach ($paths as $path) {
            self::delete($path);
        }
    }

    /**
     * The WebP twin of a stored still, or null when there is not one.
     *
     * Checked rather than assumed: the derivative is best-effort, so the
     * storefront has to ask before offering it. A miss simply means the
     * browser gets the original, which is the behaviour every banner had
     * before this existed.
     */
    public static function webpFor(?string $path): ?string
    {
        if (! $path || str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return null;
        }

        $key = self::webpKeyFor($path);

        return Storage::disk('public')->exists($key) ? $key : null;
    }

    /** `banners/x.jpg` -> `banners/x.webp`. A sibling, so it moves with the original. */
    private static function webpKeyFor(string $path): string
    {
        return preg_replace('/\.[^.\/]+$/', '', $path).'.webp';
    }

    /**
     * Write a WebP copy of a stored still beside it.
     *
     * Deliberately no new dependency: production carries both GD and Imagick
     * with WebP built in, and a banner is one image saved once, not a pipeline.
     * Anything that goes wrong is logged and swallowed - a server without WebP
     * support, a file GD cannot read, a disk that is full. None of those is a
     * reason to refuse the upload the admin actually made.
     */
    private static function writeWebp(string $path): void
    {
        try {
            $disk = Storage::disk('public');
            $source = $disk->path($path);

            if (! is_file($source) || ! function_exists('imagewebp')) {
                return;
            }

            $info = @getimagesize($source);

            if ($info === false) {
                return;
            }

            $image = match ($info[2]) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
                IMAGETYPE_PNG => @imagecreatefrompng($source),
                IMAGETYPE_WEBP => null, // already one
                IMAGETYPE_GIF => null,  // animation would be flattened to one frame
                default => null,
            };

            if (! $image) {
                return;
            }

            // Transparency survives the round trip; without these a PNG logo
            // comes back on a black rectangle.
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            $target = $disk->path(self::webpKeyFor($path));
            @imagewebp($image, $target, 82);
            imagedestroy($image);

            // A WebP bigger than what it replaces is not worth serving, and
            // for an already-optimised JPEG that does happen.
            if (is_file($target) && filesize($target) >= filesize($source)) {
                @unlink($target);
            }
        } catch (\Throwable $e) {
            Log::warning('Banner WebP derivative failed', ['path' => $path, 'error' => $e->getMessage()]);
        }
    }
}
