<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Fill MEN and WOMEN out to a full shelf of subcategories, each with a tile.
 *
 * Both roots were left holding the placeholders they were created with -
 * SUB-MEN-1, SUB-MEN-2, SUB-WOMEN - so the mega menu had two entries under one
 * root and one under the other, and every category tile on /categories drew the
 * empty-state glyph because not one row had an image.
 *
 * Idempotent by slug and additive by design: it counts what is already there
 * and creates only the difference, so running it twice does not duplicate a
 * shelf and the placeholders that already exist are kept rather than replaced.
 * That is deliberate - they may be carrying products, and deleting a category
 * cascades to whatever is filed under it.
 *
 * The tiles are drawn here rather than uploaded because storage/app/public is
 * gitignored and the deploy ships neither it nor anything outside public/images
 * - so an image added to the repo would simply never reach the server. Drawing
 * them means the command carries its own artwork and produces the same result
 * in both environments, which is the only version of this that can be run on
 * production at all. They are honest placeholders, sized and framed to the 4:3
 * the tile expects, and any one of them is replaced by uploading a photo over
 * it in Admin > Categories.
 */
class SeedFashionSubcategories extends Command
{
    protected $signature = 'categories:fashion-subcategories
                            {--per=10 : How many subcategories each root should end up with}
                            {--refresh-images : Redraw tiles for categories that already have one}';

    protected $description = 'Top MEN and WOMEN up to a full set of subcategories and draw a tile for each';

    /**
     * Names only - the slug is the model's to decide, and deliberately so.
     *
     * Category slugs are generated from the name by the sluggable trait, and
     * regenerated on every update. A slug set by hand here would not survive:
     * the model's own created() hook re-saves the row to fill in `path` once an
     * id exists, and on that update the trait no longer counts the slug as
     * custom, so it rewrites it from the name. The same happens the next time
     * an admin edits the category. Fighting that would mean saving quietly at
     * every point forever and still losing the slug to the first ordinary edit.
     *
     * So the names carry the uniqueness instead: no name appears under both
     * roots. That is what keeps "Jeans" from becoming jeans and jeans-1, with
     * the bare one going to whichever root happened to be inserted first.
     *
     * @var array<string, array<int, string>>
     */
    private const SHELVES = [
        'men' => [
            'Shirts', 'T-Shirts', 'Kurtas', 'Trousers',
            'Jackets', 'Blazers', 'Activewear', 'Shorts',
            'Sherwanis', 'Track Pants',
        ],
        'women' => [
            'Dresses', 'Tops', 'Kurtis', 'Sarees', 'Lehengas',
            'Skirts', 'Co-ord Sets', 'Blouses', 'Leggings', 'Palazzos',
        ],
    ];

    /** The storefront's own accent, which the tiles are toned around. */
    private const BRAND = [0x6F, 0x9C, 0xA2];

    public function handle(): int
    {
        $per = max(1, (int) $this->option('per'));

        $this->newLine();

        $roots = [];
        foreach (array_keys(self::SHELVES) as $slug) {
            $root = Category::whereNull('parent_id')->where('slug', $slug)->first();

            if (! $root) {
                $this->error('  No top-level category with slug "'.$slug.'". Nothing was changed.');

                return self::FAILURE;
            }

            $roots[$slug] = $root;
        }

        $created = 0;

        foreach ($roots as $slug => $root) {
            $existing = $root->children()->count();
            $missing = max(0, $per - $existing);

            $this->line('  '.$root->name.' : '.$existing.' subcategor'.($existing === 1 ? 'y' : 'ies')
                .', adding '.$missing.' to reach '.$per);

            if ($missing === 0) {
                continue;
            }

            // Position continues past whatever is already on the shelf, so the
            // new rows sort after the existing ones rather than all landing on
            // position 0 and ordering arbitrarily.
            $position = (int) $root->children()->max('position');

            foreach (self::SHELVES[$slug] as $name) {
                if ($missing === 0) {
                    break;
                }

                // Skip a shelf this root already has, whoever made it - the
                // point is to reach $per, not to own every row.
                if ($root->children()->where('name', $name)->exists()) {
                    continue;
                }

                $child = Category::create([
                    'parent_id' => $root->id,
                    'name' => $name,
                    'description' => $name.' for '.ucfirst(strtolower($root->name)).'.',
                    'is_active' => true,
                    'position' => ++$position,
                ]);

                // The trait appends -1 rather than colliding, so a name already
                // used elsewhere in the tree still lands - just at a URL nobody
                // chose. Worth saying out loud rather than discovering in the
                // address bar.
                if ($child->slug !== \Illuminate\Support\Str::slug($name)) {
                    $this->warn('    "'.$name.'" took the slug '.$child->slug.' - that name is in use elsewhere');
                }

                $created++;
                $missing--;
            }

            if ($missing > 0) {
                $this->warn('    ran out of names - '.$missing.' short of '.$per);
            }
        }

        $this->newLine();
        $this->line('  Created '.$created.' subcategor'.($created === 1 ? 'y' : 'ies').'.');

        $drawn = $this->drawTiles($roots);

        $this->newLine();
        $this->line('  Tiles drawn : '.$drawn);
        $this->line('  Stored at   : storage/app/public/categories, served from /storage/categories');
        $this->newLine();
        $this->info('  Done. Replace any tile by uploading a photo in Admin > Categories.');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Draw a tile for both roots and everything under them.
     *
     * @param  array<string, Category>  $roots
     */
    private function drawTiles(array $roots): int
    {
        if (! extension_loaded('gd')) {
            $this->warn('  GD is not available - no tiles drawn. Categories were still created.');

            return 0;
        }

        Storage::disk('public')->makeDirectory('categories');

        $font = $this->font();
        $this->line('  Font        : '.($font ?? 'GD built-in (no TrueType font found)'));

        $targets = collect();
        foreach ($roots as $root) {
            $targets->push($root);
            $targets = $targets->concat($root->children()->orderBy('position')->get());
        }

        $refresh = (bool) $this->option('refresh-images');
        $drawn = 0;
        $index = 0;

        foreach ($targets as $category) {
            $index++;

            // An image someone uploaded is not overwritten by a drawing.
            if ($category->image_url && ! $refresh) {
                continue;
            }

            $relative = 'categories/'.$category->slug.'.jpg';
            $absolute = Storage::disk('public')->path($relative);

            if ($this->drawTile($category->name, $absolute, $index, $font)) {
                $category->forceFill(['image_url' => $relative])->save();
                $drawn++;
            }
        }

        return $drawn;
    }

    /**
     * One 4:3 tile: a toned gradient, the name, and the wordmark.
     *
     * 4:3 because that is the frame /categories reserves. The tile is drawn to
     * fit rather than relying on the frame to crop it - the frame shows an image
     * whole over a blurred copy of itself, so anything drawn to a different
     * shape would sit in a band of its own blur.
     */
    private function drawTile(string $name, string $path, int $index, ?string $font): bool
    {
        $w = 800;
        $h = 600;

        $im = imagecreatetruecolor($w, $h);

        // Each tile is nudged around the brand hue so a grid of them reads as a
        // set rather than as one colour repeated twelve times.
        [$r, $g, $b] = self::BRAND;
        $shift = (($index * 37) % 60) - 30;
        $top = $this->shade($r, $g, $b, $shift, 1.18);
        $bottom = $this->shade($r, $g, $b, $shift, 0.62);

        for ($y = 0; $y < $h; $y++) {
            $t = $y / max(1, $h - 1);
            $line = imagecolorallocate(
                $im,
                (int) round($top[0] + ($bottom[0] - $top[0]) * $t),
                (int) round($top[1] + ($bottom[1] - $top[1]) * $t),
                (int) round($top[2] + ($bottom[2] - $top[2]) * $t),
            );
            imageline($im, 0, $y, $w, $y, $line);
        }

        $white = imagecolorallocate($im, 255, 255, 255);
        $veil = imagecolorallocatealpha($im, 255, 255, 255, 118);

        // A thin inset rule, so the tile has an edge of its own inside the
        // card's border rather than bleeding to it.
        imagesetthickness($im, 2);
        imagerectangle($im, 28, 28, $w - 29, $h - 29, $veil);

        if ($font) {
            $size = 62;
            // Shrink until it fits the rule with a margin - "Co-ord Sets" and
            // "Ethnic Wear" overrun the frame at the opening size.
            do {
                $box = imagettfbbox($size, 0, $font, $name);
                $textWidth = $box[2] - $box[0];
                $size -= 2;
            } while ($textWidth > $w - 160 && $size > 18);

            $box = imagettfbbox($size, 0, $font, $name);
            $textWidth = $box[2] - $box[0];
            $textHeight = $box[1] - $box[7];

            imagettftext($im, $size, 0, (int) (($w - $textWidth) / 2), (int) (($h + $textHeight) / 2) - 18,
                $white, $font, $name);

            imagettftext($im, 15, 0, (int) (($w - imagettfbbox(15, 0, $font, 'KARMAA KULTURE')[2]) / 2), $h - 74,
                $veil, $font, 'KARMAA KULTURE');
        } else {
            // No TrueType anywhere: still produce a labelled tile rather than a
            // blank gradient, just in the built-in bitmap face.
            $label = strtoupper($name);
            imagestring($im, 5, (int) (($w - imagefontwidth(5) * strlen($label)) / 2), (int) ($h / 2) - 14, $label, $white);
        }

        imagefilledrectangle($im, (int) ($w / 2) - 46, $h - 130, (int) ($w / 2) + 46, $h - 128, $veil);

        // No imagedestroy: since PHP 8 the handle is a GdImage object freed by
        // the collector, and the call is deprecated.
        return (bool) imagejpeg($im, $path, 88);
    }

    /**
     * The brand colour, hue-shifted and scaled for lightness.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    private function shade(int $r, int $g, int $b, int $hueShift, float $lightness): array
    {
        $max = max($r, $g, $b) / 255;
        $min = min($r, $g, $b) / 255;
        $l = ($max + $min) / 2;
        $d = $max - $min;
        $s = $d == 0 ? 0 : $d / (1 - abs(2 * $l - 1));

        $rn = $r / 255;
        $gn = $g / 255;
        $bn = $b / 255;
        if ($d == 0) {
            $hue = 0;
        } elseif ($max == $rn) {
            $hue = 60 * fmod((($gn - $bn) / $d), 6);
        } elseif ($max == $gn) {
            $hue = 60 * ((($bn - $rn) / $d) + 2);
        } else {
            $hue = 60 * ((($rn - $gn) / $d) + 4);
        }

        $hue = fmod($hue + $hueShift + 360, 360);
        $l = max(0.06, min(0.94, $l * $lightness));

        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($hue / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r1, $g1, $b1] = match ((int) floor($hue / 60)) {
            0 => [$c, $x, 0],
            1 => [$x, $c, 0],
            2 => [0, $c, $x],
            3 => [0, $x, $c],
            4 => [$x, 0, $c],
            default => [$c, 0, $x],
        };

        return [
            (int) round(($r1 + $m) * 255),
            (int) round(($g1 + $m) * 255),
            (int) round(($b1 + $m) * 255),
        ];
    }

    /**
     * A TrueType face, wherever this is running.
     *
     * Windows keeps one set, this host keeps another, and neither path exists
     * on the other machine - so the list is tried in order and then the whole
     * font tree is swept before giving up. Returning null is not fatal: the
     * tile falls back to GD's built-in face rather than to no label.
     */
    private function font(): ?string
    {
        $candidates = [
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/arial.ttf',
            '/usr/share/fonts/google-droid-sans-fonts/DroidSansFallbackFull.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
        ];

        foreach ($candidates as $font) {
            if (is_file($font)) {
                return $font;
            }
        }

        foreach (['/usr/share/fonts', '/usr/local/share/fonts'] as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            foreach ((glob($dir.'/*/*.ttf') ?: []) as $found) {
                return $found;
            }
        }

        return null;
    }
}
