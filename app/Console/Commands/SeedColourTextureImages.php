<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ShopFilterCatalogue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo photographs for the colour/texture gallery, drawn rather than downloaded.
 *
 * The gallery only does anything visible once a product has photos tagged with
 * the shades and fabrics it offers, and no catalogue has those on the day the
 * feature ships. This makes a set: one photo per shade, one per shade+fabric
 * pair where the product offers fabrics, and two deliberately untagged ones so
 * the "shared photo" half of the rule can be seen working too.
 *
 * Drawn with GD on purpose. Every other seeder that wants a picture fetches one
 * from picsum or unsplash, which needs the box to have egress, silently produces
 * a 0-byte file when it does not, and cannot be run from a test at all. These
 * are generated from the shade's own hex, so what is on screen is unmistakably
 * the colour that was picked - which is exactly what somebody checking this
 * feature needs to see.
 */
class SeedColourTextureImages extends Command
{
    protected $signature = 'products:colour-texture-images
        {product? : Product id or slug. Defaults to the first active product that has colours}
        {--clear : Remove images this command made before, instead of adding to them}
        {--shades=3 : How many shades to photograph, 0 for all of them}
        {--fabrics=2 : How many of its fabrics to photograph, 0 for all}';

    protected $description = 'Draw demo photos tagged by shade and fabric, so the product page gallery can be seen switching';

    /**
     * Marks the rows this command owns, so --clear can find them again without
     * risking a real photograph. Stored in alt_text because it is the one column
     * on product_images nothing else writes.
     */
    private const MARKER = '[demo-shade-image]';

    public function handle(): int
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->error('GD is not available, so there is nothing to draw with.');

            return self::FAILURE;
        }

        $product = $this->resolveProduct();

        if (! $product) {
            $this->error('No product found. Give an id or a slug, or add colours to a product first.');

            return self::FAILURE;
        }

        $this->info('Product: '.$product->name.' (#'.$product->id.')');

        $removed = ProductImage::where('product_id', $product->id)
            ->where('alt_text', 'like', '%'.self::MARKER.'%')
            ->get();

        foreach ($removed as $image) {
            // The file first, then the row: a row without its file is a broken
            // frame on the storefront, a file without its row is only bytes.
            Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', (string) $image->url), '/'));
            $image->delete();
        }

        if ($removed->isNotEmpty()) {
            $this->line('Removed '.$removed->count().' demo image(s) from a previous run.');
        }

        if ($this->option('clear')) {
            $this->info('Done.');

            return self::SUCCESS;
        }

        /* Capped, because a bulk normalisation has left this catalogue's
           products carrying the whole shade library - seven shades and five
           fabrics each, which is 7 x (1 + 5) + 2 = 44 photographs for one
           product. That is a slow page and a wall of thumbnails, and it
           demonstrates nothing the first three shades do not. Pass --shades=0
           --fabrics=0 to photograph the lot. */
        $colours = $this->cap($this->colours($product), (int) $this->option('shades'));
        $textures = $this->cap($this->textures($product), (int) $this->option('fabrics'));

        if ($colours === []) {
            $this->error('That product offers no colours, so there is nothing to tag a photo with.');

            return self::FAILURE;
        }

        $this->line('Shades: '.implode(', ', array_column($colours, 'name')));
        $this->line('Fabrics: '.($textures === [] ? '(none)' : implode(', ', $textures)));

        // Start after whatever is already there, so a product's real photographs
        // keep their order and the demo set lands behind them.
        $position = (int) ($product->images()->max('position') ?? 0);
        $made = 0;

        foreach ($colours as $colour) {
            // One plain shot of the shade itself, tagged with the shade alone, so
            // it shows whichever fabric is chosen.
            $this->write($product, $colour, null, ++$position);
            $made++;

            // Then one per fabric, tagged with both - a photograph of the indigo
            // linen is not a photograph of the indigo cotton, and the gallery
            // treats them that way.
            foreach ($textures as $texture) {
                $this->write($product, $colour, $texture, ++$position);
                $made++;
            }
        }

        // And two that name nothing at all. These are the shared shots every real
        // catalogue has - the size chart, the fabric close-up - and they must stay
        // on screen through every tap. Without a couple of these in the demo set
        // the shared half of the rule is invisible.
        foreach (['Size chart', 'Care label'] as $shared) {
            $this->write($product, ['name' => $shared, 'hex' => '#f3ede4'], null, ++$position, shared: true);
            $made++;
        }

        $this->newLine();
        $this->info($made.' demo image(s) written and tagged.');
        $this->line('Open: '.route('product.show', $product));
        $this->line('Admin: '.route('admin.products.edit', $product));

        return self::SUCCESS;
    }

    private function resolveProduct(): ?Product
    {
        $key = $this->argument('product');

        if ($key) {
            return Product::where('id', is_numeric($key) ? (int) $key : 0)
                ->orWhere('slug', $key)
                ->first();
        }

        // Whichever active product already offers colours, so the demo lands
        // somewhere the picker will actually be drawn.
        return Product::where('is_active', true)
            ->whereNotNull('attributes')
            ->get()
            ->first(fn (Product $p) => $this->colours($p) !== []);
    }

    /**
     * The first $limit of them, or all of them when $limit is 0 or less.
     *
     * @template T
     *
     * @param  array<int, T>  $values
     * @return array<int, T>
     */
    private function cap(array $values, int $limit): array
    {
        return $limit > 0 ? array_slice($values, 0, $limit) : $values;
    }

    /** @return array<int, array{name: string, hex: string}> */
    private function colours(Product $product): array
    {
        return collect(data_get($product->attributes, 'Colours', []))
            ->map(fn ($c) => is_array($c)
                ? ['name' => trim((string) ($c['name'] ?? '')), 'hex' => (string) ($c['hex'] ?? '#cccccc')]
                : ['name' => trim((string) $c), 'hex' => '#cccccc'])
            ->filter(fn (array $c) => $c['name'] !== '')
            ->unique('name')
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function textures(Product $product): array
    {
        return collect(data_get($product->attributes, 'Textures', []))
            ->map(fn ($t) => trim((string) (is_array($t) ? ($t['name'] ?? '') : $t)))
            ->filter()
            ->unique(fn (string $t) => mb_strtolower($t))
            ->values()
            ->all();
    }

    /**
     * @param  array{name: string, hex: string}  $colour
     */
    private function write(Product $product, array $colour, ?string $texture, int $position, bool $shared = false): void
    {
        $name = Str::slug($product->slug.'-'.$colour['name'].'-'.($texture ?: 'plain')).'-'.$position;
        $key = 'products/demo/'.$name.'.png';

        $png = $this->draw($colour, $texture, $shared);

        // sudo -u www-data when running this on a server: artisan as another
        // user writes some of storage/app/public and silently fails on the rest.
        Storage::disk('public')->put($key, $png);

        ProductImage::create([
            'product_id' => $product->id,
            'media_type' => 'image',
            'url' => '/storage/'.$key,
            // Only a real tag on a real shade. The two shared frames carry
            // neither, which is what makes them shared.
            'colour' => $shared ? null : $colour['name'],
            'texture' => $shared ? null : $texture,
            'alt_text' => trim($product->name.' - '.$colour['name'].($texture ? ' '.$texture : '')).' '.self::MARKER,
            'position' => $position,
            'is_primary' => false,
        ]);

        $this->line(sprintf(
            '  + %-28s shade=%-14s fabric=%s',
            Str::limit($name, 28),
            $shared ? '(shared)' : $colour['name'],
            $shared ? '(shared)' : ($texture ?: '(any)'),
        ));
    }

    /**
     * A 3:4 portrait in the shade, with the fabric suggested by a pattern.
     *
     * Product::IMAGE_SIZE, because that is the shape the storefront lays every
     * product photo out at and anything else would be cropped - a demo image
     * that loses its own label to the crop is not much of a demo.
     *
     * @param  array{name: string, hex: string}  $colour
     */
    private function draw(array $colour, ?string $texture, bool $shared): string
    {
        [$width, $height] = Product::IMAGE_SIZE;

        $canvas = imagecreatetruecolor($width, $height);
        [$r, $g, $b] = $this->rgb($colour['hex']);

        imagefilledrectangle($canvas, 0, 0, $width, $height, imagecolorallocate($canvas, $r, $g, $b));

        // A light shade needs dark ink on it and a dark one needs light, or the
        // label is unreadable on exactly the colours somebody wants to check.
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        $ink = $luminance > 0.6
            ? imagecolorallocate($canvas, 40, 24, 16)
            : imagecolorallocate($canvas, 255, 255, 255);

        $this->pattern($canvas, $width, $height, $texture, $ink);

        // A garment-shaped block, so the frame reads as a product photo at a
        // glance rather than as a colour swatch.
        $panel = imagecolorallocatealpha(
            $canvas,
            $luminance > 0.6 ? 255 : 0,
            $luminance > 0.6 ? 255 : 0,
            $luminance > 0.6 ? 255 : 0,
            100,
        );
        imagefilledrectangle($canvas, (int) ($width * 0.22), (int) ($height * 0.18), (int) ($width * 0.78), (int) ($height * 0.72), $panel);

        $lines = $shared
            ? [$colour['name'], '(shown for every shade)']
            : array_values(array_filter([$colour['name'], $texture]));

        $this->label($canvas, $width, $height, $lines, $ink);

        ob_start();
        imagepng($canvas);
        $png = (string) ob_get_clean();
        imagedestroy($canvas);

        return $png;
    }

    /**
     * A crude suggestion of the fabric - stripes for one, a weave for another.
     *
     * Not an attempt at realism. The point is that two fabrics in the same shade
     * are visibly different frames, so that switching fabric on the product page
     * is seen to do something.
     */
    private function pattern($canvas, int $width, int $height, ?string $texture, int $ink): void
    {
        $key = ShopFilterCatalogue::normaliseKey((string) $texture);

        if ($key === '') {
            return;
        }

        /* Which pattern a fabric gets is decided by its own name, so the same
           fabric always draws the same way on every product and every run.

           The density is taken from a different part of the same hash as the
           style, because with four styles alone two of a product's fabrics
           collide often - "Linen" and "Cotton" did, and two frames identical
           but for their caption make a poor demonstration of a feature whose
           whole point is that the picture changes. */
        $hash = crc32($key);
        $style = $hash % 4;
        $step = 28 + (intdiv($hash, 4) % 5) * 14;

        for ($i = -$height; $i < $width + $height; $i += $step) {
            match ($style) {
                0 => imageline($canvas, $i, 0, $i + $height, $height, $ink),          // diagonal twill
                1 => imageline($canvas, $i, 0, $i, $height, $ink),                    // vertical rib
                2 => imageline($canvas, 0, $i, $width, $i, $ink),                     // horizontal weave
                default => imagerectangle($canvas, $i, $i % $height, $i + 24, ($i % $height) + 24, $ink),
            };
        }
    }

    /** @param  array<int, string>  $lines */
    private function label($canvas, int $width, int $height, array $lines, int $ink): void
    {
        // Built-in font 5 is the largest GD ships without a TTF file on disk,
        // and a server with no fonts installed must still produce a legible
        // label. Scaled up by drawing into a small canvas and enlarging it.
        $font = 5;
        $charW = imagefontwidth($font);
        $charH = imagefontheight($font);
        $scale = 4;

        $y = (int) ($height * 0.78);

        foreach ($lines as $line) {
            $text = strtoupper($line);
            $stripW = $charW * strlen($text);

            if ($stripW < 1) {
                continue;
            }

            $strip = imagecreatetruecolor($stripW, $charH);
            imagesavealpha($strip, true);
            imagefill($strip, 0, 0, imagecolorallocatealpha($strip, 0, 0, 0, 127));
            imagestring($strip, $font, 0, 0, $text, imagecolorallocate($strip, 255, 255, 255));

            $targetW = $stripW * $scale;
            $targetH = $charH * $scale;
            $x = (int) (($width - $targetW) / 2);

            // A plate behind the text, so a stripe pattern underneath cannot eat it.
            imagefilledrectangle(
                $canvas,
                $x - 24,
                $y - 16,
                $x + $targetW + 24,
                $y + $targetH + 16,
                imagecolorallocatealpha($canvas, 0, 0, 0, 75),
            );

            imagecopyresized($canvas, $strip, $x, $y, 0, 0, $targetW, $targetH, $stripW, $charH);
            imagedestroy($strip);

            // The ink colour is used for the pattern rather than the text, which
            // is always white on a dark plate - white on black reads on every
            // shade, and the plate is what guarantees the contrast.
            unset($ink);

            $y += $targetH + 44;
        }
    }

    /** @return array{0: int, 1: int, 2: int} */
    private function rgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            $hex = 'cccccc';
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
