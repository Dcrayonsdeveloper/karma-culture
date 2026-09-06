<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cut the catalogue's product titles down to "Style Colour Garment".
 *
 * The names were imported as one long attribute dump - "Karmaa Kulture Women's
 * Bhacan Blue Polyester V-Neck Striped Cuffed Sleeve Relaxed Fit Shirt", 91
 * characters, of which a shopper reads the first four words. The shape is
 * consistent enough to parse:
 *
 *     [brand] [gender] [style] [colour] [fabric] [attributes...] [garment]
 *
 * so the three parts worth keeping can be lifted out and the rest dropped.
 * That example becomes "Bhacan Blue Shirt".
 *
 * The vocabularies below are not invented - they were read off the live
 * catalogue, which is why they carry the misspellings it actually contains
 * ("flourscent", "metalic", "turq").
 *
 * The slug is deliberately NOT touched. Product::getSlugOptions() regenerates
 * it from the name, so renaming through Eloquent would move 1,231 live URLs and
 * break every shared link and search result pointing at them - the writes here
 * go through the query builder for that reason.
 *
 * Nothing is written without --apply, and --apply first dumps every old name to
 * a JSON file so the whole run can be put back.
 */
class ShortenProductTitles extends Command
{
    protected $signature = 'products:shorten-titles
        {--apply : Write the new titles. Without this the command only reports.}
        {--out= : Where to write the preview/backup files (default: storage/app/private)}
        {--restore= : Path to a backup JSON written by a previous --apply, to undo it.}';

    protected $description = 'Shorten product titles to "Style Colour Garment" and drop the brand prefix';

    /** Longest first, so "rare ones" is matched before "rare". */
    private const BRANDS = ['karmaa kulture', 'rare rabbit', 'rare ones', 'rarez'];

    private const GENDER = ["men's", "women's", 'mens', 'womens', 'men', 'women', 'kids', 'boys', 'girls', 'unisex'];

    /** A colour is one of these, optionally with one or two of the modifiers. */
    private const COLOUR_MODIFIERS = ['dark', 'light', 'dusky', 'dusty', 'pastel', 'primary', 'deep', 'bright',
        'off', 'mid', 'powder', 'french', 'clay', 'enamel', 'flame', 'cardamom', 'oyster', 'ice', 'neon',
        'flouroscent', 'fluorescent', 'flourscent', 'metallic', 'metalic', 'rose', 'navy'];

    private const COLOUR_BASE = ['black', 'white', 'offwhite', 'off-white', 'blue', 'beige', 'green', 'navy',
        'brown', 'grey', 'gray', 'pink', 'olive', 'maroon', 'red', 'purple', 'multi', 'rust', 'orange',
        'yellow', 'teal', 'peach', 'mustard', 'khaki', 'gold', 'silver', 'tan', 'coffee', 'melange', 'aqua',
        'sand', 'lime', 'lilac', 'fawn', 'turquoise', 'turq', 'petrol', 'bay', 'cream', 'ivory', 'burgundy',
        'coral', 'charcoal', 'rose', 'wine', 'plum', 'mint', 'sage', 'stone', 'slate', 'camel', 'indigo',
        'magenta', 'bronze', 'copper', 'ecru', 'taupe', 'mauve', 'salmon', 'emerald', 'assorted', 'lavender'];

    private const FABRICS = ['cotton', 'polyester', 'linen', 'rayon', 'silk', 'denim', 'wool', 'viscose',
        'nylon', 'leather', 'blend', 'knit', 'lycra', 'modal', 'velour', 'velvet', 'corduroy', 'georgette',
        'chiffon', 'satin', 'crepe', 'jacquard', 'fleece', 'suede', 'net', 'organza', 'tencel', 'acrylic',
        'cashmere', 'terry', 'twill', 'poplin', 'chambray', 'khadi', 'jersey', 'canvas', 'spandex',
        // 'micro' is left out: it turns up as a style code here, not a fabric.
        'elastane', 'lyocell', 'fabric', 'interlock', 'gsm'];

    /** Everything that describes the cut rather than naming the product. */
    private const ATTRIBUTES = ['plain', 'solid', 'printed', 'print', 'full', 'half', 'sleeve', 'sleeves',
        'sleeveless', 'regular', 'slim', 'relaxed', 'boxy', 'tailored', 'baggy', 'straight', 'wide-leg',
        'mid-rise', 'high', 'low', 'hooded', 'collared', 'collarless', 'crew', 'round', 'v-neck', 'neck',
        'lapel', 'mandarin', 'stand', 'drop', 'spread', 'striped', 'checked', 'check', 'embroidered',
        'typography', 'graphic', 'floral', 'button', 'zip', 'zip-up', 'a-line', 'flared', 'mini', 'midi',
        'maxi', 'knee', 'ankle', 'cuffed', 'stretch', 'self', 'design', 'oxford', 'style', 'colorblocked',
        'colourblocked', 'smart', 'casual', 'lace-up', 'slip-on', 'closure', 'pack', 'weight', 'ribbed',
        'textured', 'cable', 'pleated', 'wrap', 'broderie', 'anglaise', 'rugby', 'formal', 'premium',
        // 'basic', 'corp' and 'statement' are NOT here: in this catalogue they
        // are style-code words ("Basic Corp Jack", "Basic Rr-Corp-Gold").
        'classic', 'fit', 'length', 'short', 'long', 'overlayer', 'rise',
        'and', 'with', 'the', 'closure', 'up', 'down', 'front', 'back', 'side'];

    /** Garments whose name is two words, checked before the single-word rule. */
    private const GARMENTS_TWO_WORD = ['track pant', 'outer wear', 'nehru jacket', 'bangle necklace',
        'ankle socks', 'wrap dress', 'wrap top', 'polo shirt', 'formal shirt', 'midi skirt', 't shirt'];

    public function handle(): int
    {
        if ($restore = $this->option('restore')) {
            return $this->restore($restore);
        }

        $dir = $this->option('out') ?: storage_path('app/private');

        if (! is_dir($dir) && ! @mkdir($dir, 0775, true)) {
            $this->error('Cannot create '.$dir);

            return self::FAILURE;
        }

        $rows = [];
        $unchanged = 0;
        $long = [];

        foreach (Product::query()->orderBy('id')->get(['id', 'name', 'slug']) as $product) {
            $old = (string) $product->name;
            $new = $this->shorten($old);

            if ($new === '' || $new === $old) {
                $unchanged++;

                continue;
            }

            $rows[] = ['id' => $product->id, 'old' => $old, 'new' => $new, 'slug' => $product->slug];

            // A title that is still long, or has collapsed to a single word, is
            // where the parse most likely went wrong - worth a human's eye.
            if (mb_strlen($new) > 45 || substr_count($new, ' ') < 1) {
                $long[] = $new.'   <-   '.$old;
            }
        }

        $stamp = now()->format('Ymd-His');
        $preview = $dir.'/product-titles-'.$stamp.'.csv';
        $handle = fopen($preview, 'w');
        fputcsv($handle, ['id', 'old_name', 'new_name', 'slug_unchanged']);
        foreach ($rows as $r) {
            fputcsv($handle, [$r['id'], $r['old'], $r['new'], $r['slug']]);
        }
        fclose($handle);

        $this->info('Products              : '.Product::count());
        $this->info('Would change          : '.count($rows));
        $this->info('Left alone            : '.$unchanged);
        $this->info('Needs an eye (long/odd): '.count($long));
        $this->info('Preview CSV           : '.$preview);

        // Two products collapsing to one title is legal - the slug still makes
        // them distinct - but it is worth knowing about.
        $dupes = collect($rows)->groupBy('new')->filter(fn ($g) => $g->count() > 1);
        $this->info('Titles shared by 2+   : '.$dupes->count());

        $this->newLine();
        $this->line('--- 25 samples ---');
        foreach (array_slice($rows, 0, 25) as $r) {
            $this->line('  '.str_pad($r['new'], 34).'  <-  '.mb_substr($r['old'], 0, 70));
        }

        if ($long !== []) {
            $this->newLine();
            $this->line('--- '.min(20, count($long)).' of '.count($long).' that need an eye ---');
            foreach (array_slice($long, 0, 20) as $l) {
                $this->line('  '.$l);
            }
        }

        if (! $this->option('apply')) {
            $this->newLine();
            $this->comment('Dry run. Nothing was written. Re-run with --apply to commit.');

            return self::SUCCESS;
        }

        // Everything needed to put the catalogue back, written before the first
        // row changes.
        $backup = $dir.'/product-titles-backup-'.$stamp.'.json';
        file_put_contents($backup, json_encode(
            collect($rows)->mapWithKeys(fn ($r) => [$r['id'] => $r['old']])->all(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
        $this->info('Backup written        : '.$backup);

        $this->write($rows);

        $this->newLine();
        $this->info('Done. Undo with: php artisan products:shorten-titles --restore="'.$backup.'"');

        return self::SUCCESS;
    }

    /**
     * Write the names straight through the query builder.
     *
     * Not through Eloquent: HasSlug regenerates the slug from the name on save,
     * and moving 1,231 live URLs is a far bigger change than the one being
     * asked for here. The filter cache is bumped once at the end instead of
     * once per row.
     *
     * @param  array<int, array{id:int, new:string}>  $rows
     */
    private function write(array $rows): void
    {
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        DB::transaction(function () use ($rows, $bar): void {
            foreach (array_chunk($rows, 100) as $chunk) {
                foreach ($chunk as $r) {
                    DB::table('products')->where('id', $r['id'])->update(['name' => $r['new']]);
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine();

        ProductVariant::bumpFilterCache();
    }

    /** Put back the names a previous --apply replaced. */
    private function restore(string $path): int
    {
        if (! is_file($path)) {
            $this->error('No such backup: '.$path);

            return self::FAILURE;
        }

        $map = json_decode((string) file_get_contents($path), true);

        if (! is_array($map) || $map === []) {
            $this->error('That file is not a title backup.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($map): void {
            foreach ($map as $id => $name) {
                DB::table('products')->where('id', (int) $id)->update(['name' => $name]);
            }
        });

        ProductVariant::bumpFilterCache();
        $this->info('Restored '.count($map).' product titles.');

        return self::SUCCESS;
    }

    /**
     * "Karmaa Kulture Women's Bhacan Blue Polyester ... Shirt" -> "Bhacan Blue Shirt".
     *
     * Returns '' when the name does not fit the pattern well enough to be worth
     * rewriting - a title nobody can parse is better left as the merchandiser
     * typed it than replaced with a guess.
     */
    public function shorten(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        if ($name === '') {
            return '';
        }

        // A trailing "(Pack Of 3)" is information, not decoration - set it aside
        // and put it back on the end, so it does not get read as the garment.
        $suffix = '';
        if (preg_match('/\s*(\((?:pack|set)[^)]*\))\s*$/i', $name, $m)) {
            $suffix = ' '.trim($m[1]);
            $name = trim(mb_substr($name, 0, -mb_strlen($m[0])));
        }

        $words = explode(' ', $name);
        $i = 0;
        $hadBrand = false;

        foreach (self::BRANDS as $brand) {
            $parts = explode(' ', $brand);
            if (mb_strtolower(implode(' ', array_slice($words, 0, count($parts)))) === $brand) {
                $i = count($parts);
                $hadBrand = true;
                break;
            }
        }

        // A name with no brand on the front and already short was written by a
        // person, not dumped by the importer - "Ivory Pleated Midi Skirt" is
        // the thing this command is trying to produce. Parsing it could only
        // throw information away.
        if (! $hadBrand && mb_strlen($name) <= 45) {
            return '';
        }

        if (isset($words[$i]) && in_array($this->key($words[$i]), self::GENDER, true)) {
            $i++;
        }

        // The garment is the tail of the name. Two-word garments first, so
        // "Track Pant" does not become "Pant".
        $garment = [];
        $end = count($words);

        if ($end - $i >= 2) {
            $pair = $this->key($words[$end - 2]).' '.$this->key($words[$end - 1]);
            if (in_array($pair, self::GARMENTS_TWO_WORD, true)) {
                $garment = [$words[$end - 2], $words[$end - 1]];
                $end -= 2;
            }
        }

        if ($garment === [] && $end - $i >= 1) {
            $garment = [$words[$end - 1]];
            $end--;
        }

        if ($garment === []) {
            return '';
        }

        // The style code is whatever sits between the gender and the first word
        // that describes the garment rather than naming it. Capped at three, so
        // a name with no colour in it cannot swallow the whole description.
        $style = [];
        $j = $i;
        while ($j < $end && count($style) < 3 && ! $this->isDescriptive($words[$j])) {
            $style[] = $words[$j];
            $j++;
        }

        // Then the colour: modifiers, then the colour itself.
        $colour = [];
        while ($j < $end && count($colour) < 3 && $this->isColour($words[$j])) {
            $isList = str_contains($words[$j], ',');
            $colour[] = trim($words[$j], ',');
            $j++;

            // "Offwhite, Grey Melange, Navy" is three colourways in one name.
            // The first stands for the set; listing them all is how the title
            // got long in the first place.
            if ($isList) {
                break;
            }
        }

        // "Emerald Light Beige" is a style code followed by a colour, not a
        // three-word colour: no colour reads base-then-modifier, so a run that
        // starts that way has the product's name on the front of it.
        if ($style === [] && count($colour) >= 2 && $this->isModifierOnly($colour[1])) {
            $style[] = array_shift($colour);
        }

        // "Emerald Light" - a run cannot end on a modifier.
        while ($colour !== [] && $this->isModifierOnly($colour[count($colour) - 1])) {
            array_pop($colour);
        }

        // "Light Weight High Stretch" opens with a colour modifier and names no
        // colour at all. Reading it as one produced three different t-shirts all
        // called "Light T-Shirt".
        if ($colour !== [] && ! $this->hasBaseColour($colour)) {
            $colour = [];
        }

        // A style code that is really a colour ("Navy & White Striped Rugby
        // Polo" has no style at all) - hand it back as the colour.
        if ($colour === [] && $style !== [] && $this->isColour(end($style))) {
            $colour = [array_pop($style)];
        }

        // "Mobile Pouch Black Pouches" - the style code already names the
        // garment, so the trailing noun is just an echo of it.
        $styleKeys = array_map(fn ($w) => $this->singular($w), $style);
        $garmentKey = $this->singular($garment[count($garment) - 1]);

        if ($style !== [] && in_array($garmentKey, $styleKeys, true)) {
            $garment = [];
        }

        $parts = array_merge($style, $colour, $garment);
        $short = trim(implode(' ', $parts)).$suffix;

        // Nothing but the garment noun is not a title, it is a category. A
        // handful of names carry no style code and no colour - "Karmaa Kulture
        // Men's Cotton Plain Baggy Fit Jeans" - and there is nothing to lift
        // out of them. Rather than guess, take the brand and gender off and
        // leave the merchandiser's own words: the brand has to go from every
        // title, but a title nobody can parse is better kept than invented.
        if ($short === '' || count($parts) < 2) {
            if (! $hadBrand) {
                return '';
            }

            $rest = trim(implode(' ', array_slice($words, $i))).$suffix;

            return $rest === '' ? '' : $this->titleCase($rest);
        }

        return $this->titleCase($short);
    }

    /**
     * Enough singularisation to spot a garment named twice.
     *
     * rtrim($w, 's') alone turned "pouches" into "pouche", which never matched
     * the "pouch" in the style code - so "Mobile Pouch Black Pouches" kept its
     * echo.
     */
    private function singular(string $word): string
    {
        $k = $this->key($word);

        foreach (['ches', 'shes', 'xes', 'sses'] as $ending) {
            if (str_ends_with($k, $ending)) {
                return mb_substr($k, 0, -2);
            }
        }

        return rtrim($k, 's');
    }

    /** Lower-cased, stripped of the punctuation that clings to a word. */
    private function key(string $word): string
    {
        return mb_strtolower(trim($word, " \t\n\r\0\x0B,.:;\"'"));
    }

    private function isColour(string $word): bool
    {
        $k = $this->key($word);

        return in_array($k, self::COLOUR_BASE, true) || in_array($k, self::COLOUR_MODIFIERS, true);
    }

    /** A word that describes the garment rather than naming it. */
    private function isDescriptive(string $word): bool
    {
        $k = $this->key($word);

        return $this->isColour($word)
            || in_array($k, self::FABRICS, true)
            || in_array($k, self::ATTRIBUTES, true)
            // A long number is a measurement ("180 Gsm"); a short one is part of
            // the style code ("Boot 1", "Trio-3").
            || preg_match('/^\d{3,}/', $k) === 1
            || str_contains($k, '%');
    }

    /** A word that only ever qualifies a colour, and names none by itself. */
    private function isModifierOnly(string $word): bool
    {
        $k = $this->key($word);

        return in_array($k, self::COLOUR_MODIFIERS, true) && ! in_array($k, self::COLOUR_BASE, true);
    }

    /** @param array<int, string> $run */
    private function hasBaseColour(array $run): bool
    {
        foreach ($run as $word) {
            if (in_array($this->key($word), self::COLOUR_BASE, true)) {
                return true;
            }
        }

        return false;
    }

    /** Leaves an already-capitalised token alone, so "L&R" and "Ss" survive. */
    private function titleCase(string $value): string
    {
        return implode(' ', array_map(
            fn ($w) => preg_match('/[A-Z]/', mb_substr($w, 1)) ? $w : mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'),
            explode(' ', $value)
        ));
    }
}
