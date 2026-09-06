<?php

namespace App\Console\Commands;

use App\Models\ColourPreset;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SizePreset;
use App\Models\TexturePreset;
use App\Support\ShopFilterCatalogue;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Make the Sizes / Colours / Textures libraries the only options any product
 * carries.
 *
 * The libraries under Admin -> Products are already the only way to put a
 * size, colour or texture ONTO a product - the free-text "+ Add" buttons are
 * gone. But products imported before the libraries existed still carry
 * whatever their spreadsheet said, so the catalogue holds values no library
 * row backs: the shop derives its filter rails from the products, so those
 * strays are what put unrecognised chips on the rails.
 *
 * This walks the catalogue once and brings it back in line:
 *
 *   colours  - replaced outright with the active Colour library. A colour is
 *              a name and a swatch and nothing else, so there is nothing on a
 *              product's own copy worth preserving.
 *   textures - replaced outright with the active Texture library, same reason.
 *   sizes    - PRUNED, not replaced. A size row carries that product's price,
 *              MRP, stock and SKU, so a row whose size the library knows is
 *              kept as it stands (only its spelling is corrected to the
 *              library's) and a row whose size the library does not know is
 *              deleted. --sizes=replace additionally opens a row for every
 *              library size the product lacks, which is only right for a
 *              catalogue where every product genuinely ships in every size.
 *
 * Reports by default and writes nothing; --apply commits. Take a database
 * backup before --apply: deleting a size row takes its stock and SKU with it.
 */
class NormaliseProductOptionsToLibrary extends Command
{
    protected $signature = 'products:normalise-options
                            {--apply : Write the changes. Without this the command only reports}
                            {--only= : Limit to some dimensions - comma separated, any of colours,textures,sizes}
                            {--sizes=prune : prune (keep library sizes a product already has) or replace (also add every library size)}
                            {--chunk=200 : Products loaded per batch}';

    protected $description = 'Bring every product\'s sizes, colours and textures back in line with the admin libraries';

    /** Products whose every size row is unknown to the library. */
    private array $emptied = [];

    /** Size rows a stock transfer is holding, so they could not be retired. */
    private array $locked = [];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $sizeMode = (string) $this->option('sizes');

        if (! in_array($sizeMode, ['prune', 'replace'], true)) {
            $this->error('--sizes must be "prune" or "replace".');

            return self::FAILURE;
        }

        $only = $this->dimensions();

        if ($only === []) {
            $this->error('--only must name at least one of: colours, textures, sizes.');

            return self::FAILURE;
        }

        $colours = ColourPreset::active()->ordered()->get(['name', 'hex']);
        $textures = TexturePreset::active()->ordered()->get(['name']);
        $sizes = SizePreset::active()->ordered()->get(['name', 'measurements']);

        // An empty library would mean "carry nothing", which is never what an
        // admin means by it - it means they have not filled it in yet. Refuse
        // rather than strip the catalogue bare.
        foreach (['colours' => $colours, 'textures' => $textures, 'sizes' => $sizes] as $name => $library) {
            if (in_array($name, $only, true) && $library->isEmpty()) {
                $this->error("The {$name} library is empty. Add rows under Admin -> Products -> ".ucfirst($name).', or drop it from --only.');

                return self::FAILURE;
            }
        }

        $this->line('');
        $this->line('  Libraries in force');

        if (in_array('colours', $only, true)) {
            $this->line('    Colours  ('.$colours->count().'): '.$colours->pluck('name')->implode(', '));
        }

        if (in_array('textures', $only, true)) {
            $this->line('    Textures ('.$textures->count().'): '.$textures->pluck('name')->implode(', '));
        }

        if (in_array('sizes', $only, true)) {
            $this->line('    Sizes    ('.$sizes->count().'): '.$sizes->pluck('name')->implode(', ').'   [mode: '.$sizeMode.']');
        }

        $this->line('');

        // Canonical spelling per normalised key, so "BLACK" on a product is
        // rewritten to the library's "Black" rather than counted as a stray.
        $sizeByKey = $sizes->keyBy(fn ($s) => ShopFilterCatalogue::normaliseKey($s->name));

        $colourList = $colours->map(fn ($c) => ['name' => $c->name, 'hex' => $c->hex])->values()->all();
        $textureList = $textures->pluck('name')->values()->all();

        $stats = [
            'products' => 0,
            'colours_rewritten' => 0,
            'textures_rewritten' => 0,
            'variants_kept' => 0,
            'variants_renamed' => 0,
            'variants_unknown' => 0,
            'variants_duplicate' => 0,
            'variants_added' => 0,
        ];

        $run = function () use ($only, $apply, $sizeMode, $sizeByKey, $colourList, $textureList, &$stats): void {
            Product::query()
                ->with(['variants' => fn ($q) => $q->orderBy('id')])
                ->chunkById((int) $this->option('chunk'), function (Collection $products) use ($only, $apply, $sizeMode, $sizeByKey, $colourList, $textureList, &$stats): void {
                    foreach ($products as $product) {
                        $stats['products']++;

                        $attributes = (array) ($product->attributes ?? []);
                        $dirty = false;

                        if (in_array('colours', $only, true)
                            && ($attributes[ShopFilterCatalogue::COLOURS_KEY] ?? null) != $colourList) {
                            $attributes[ShopFilterCatalogue::COLOURS_KEY] = $colourList;
                            $stats['colours_rewritten']++;
                            $dirty = true;
                        }

                        if (in_array('textures', $only, true)
                            && ($attributes[ShopFilterCatalogue::TEXTURES_KEY] ?? null) != $textureList) {
                            $attributes[ShopFilterCatalogue::TEXTURES_KEY] = $textureList;
                            $stats['textures_rewritten']++;
                            $dirty = true;
                        }

                        if ($dirty && $apply) {
                            // Written straight to the column rather than through
                            // the model: a save would bump the filter cache once
                            // per product across the whole catalogue, and the run
                            // bumps it once at the end instead.
                            DB::table('products')
                                ->where('id', $product->id)
                                ->update(['attributes' => json_encode($attributes)]);
                        }

                        if (in_array('sizes', $only, true)) {
                            $this->reconcileSizes($product, $sizeByKey, $sizeMode, $apply, $stats);
                        }
                    }
                });
        };

        if ($apply) {
            DB::transaction($run);
            ProductVariant::bumpFilterCache();
        } else {
            $run();
        }

        $this->summarise($stats, $apply, $only);

        return self::SUCCESS;
    }

    /**
     * Bring one product's size rows in line with the library.
     *
     * @param  Collection<string, SizePreset>  $sizeByKey
     * @param  array<string, int>  $stats
     */
    private function reconcileSizes(Product $product, Collection $sizeByKey, string $mode, bool $apply, array &$stats): void
    {
        $held = [];

        foreach ($product->variants as $variant) {
            $key = ShopFilterCatalogue::normaliseKey(ProductVariant::sizeLabel($variant->name));
            $preset = $sizeByKey->get($key);

            // A size the library does not list is not a size the shop offers.
            if ($preset === null) {
                if ($this->delete($variant, $apply, $stats)) {
                    $stats['variants_unknown']++;
                }

                continue;
            }

            // Variant names historically fuse size and colour - "M / BLACK",
            // "M / BEIGE" are two rows of one size. Colours are a product-level
            // list now, so those collapse to a single row per size: the first
            // one wins and keeps its price, stock and SKU, and the rest go.
            // This is where most of the deleting happens, and it is also where
            // per-colour stock counts are lost.
            if (isset($held[$key])) {
                if ($this->delete($variant, $apply, $stats)) {
                    $stats['variants_duplicate']++;
                }

                continue;
            }

            $held[$key] = true;

            if ($variant->name !== $preset->name) {
                $stats['variants_renamed']++;

                if ($apply) {
                    ProductVariant::withoutEvents(fn () => $variant->forceFill(['name' => $preset->name])->save());
                }
            } else {
                $stats['variants_kept']++;
            }
        }

        if ($mode === 'replace') {
            foreach ($sizeByKey as $key => $preset) {
                if (isset($held[$key])) {
                    continue;
                }

                $stats['variants_added']++;

                if ($apply) {
                    ProductVariant::withoutEvents(fn () => ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => $preset->name,
                        // The product's own price, so a new row is sellable at
                        // the price the product already advertises rather than
                        // at zero. Stock starts at 0: nobody has counted these.
                        'price' => $product->price,
                        'mrp' => $product->mrp ?? $product->price,
                        'stock_quantity' => 0,
                        'attributes' => $preset->measurements ? ['measurements' => $preset->measurements] : null,
                        'is_active' => true,
                    ]));
                }
            }

            return; // every library size is now on the product
        }

        if ($held === []) {
            $this->emptied[] = $product->id.'  '.$product->name;
        }
    }

    /**
     * Retire one size row, unless a stock transfer is holding it.
     *
     * store_transfer_items.variant_id is the one foreign key pointing at a
     * variant, and it RESTRICTs - so deleting a row a transfer refers to would
     * throw and take the whole run's transaction down with it. Those rows are
     * left standing and named in the summary instead.
     *
     * Deleted through the model rather than around it: ProductVariant's
     * deleting hook is what clears the row's warehouse stock lines, and
     * skipping it would leave inventory_stock keyed to a variant that no
     * longer exists. That costs a filter-cache bump per delete, which is why
     * the run bumps once at the end and this is the only churn left.
     *
     * @param  array<string, int>  $stats
     * @return bool  whether the row is gone (or would be)
     */
    private function delete(ProductVariant $variant, bool $apply, array &$stats): bool
    {
        if (DB::table('store_transfer_items')->where('variant_id', $variant->id)->exists()) {
            $this->locked[] = $variant->id.'  '.$variant->name;

            return false;
        }

        if ($apply) {
            $variant->delete();
        }

        return true;
    }

    /** @return array<int, string> */
    private function dimensions(): array
    {
        $all = ['colours', 'textures', 'sizes'];
        $only = trim((string) $this->option('only'));

        if ($only === '') {
            return $all;
        }

        return collect(explode(',', $only))
            ->map(fn ($d) => strtolower(trim($d)))
            ->filter(fn ($d) => in_array($d, $all, true))
            ->unique()
            ->values()
            ->all();
    }

    /** @param  array<string, int>  $stats */
    private function summarise(array $stats, bool $apply, array $only): void
    {
        $rows = [['Products scanned', $stats['products']]];

        if (in_array('colours', $only, true)) {
            $rows[] = ['Products whose colours were rewritten', $stats['colours_rewritten']];
        }

        if (in_array('textures', $only, true)) {
            $rows[] = ['Products whose textures were rewritten', $stats['textures_rewritten']];
        }

        if (in_array('sizes', $only, true)) {
            $rows[] = ['Size rows left as they are', $stats['variants_kept']];
            $rows[] = ['Size rows respelled to the library', $stats['variants_renamed']];
            $rows[] = ['Size rows DELETED - size not in the library', $stats['variants_unknown']];
            $rows[] = ['Size rows DELETED - same size, different colour', $stats['variants_duplicate']];
            $rows[] = ['Size rows added', $stats['variants_added']];
        }

        $this->table([$apply ? 'Applied' : 'Would apply', 'Count'], $rows);

        if ($this->locked !== []) {
            $this->line('');
            $this->warn('  '.count($this->locked).' size row(s) were kept because a stock transfer refers to them.');
            $this->warn('  Close or delete the transfer, then run again to retire these:');

            foreach (array_slice($this->locked, 0, 25) as $line) {
                $this->line('    '.$line);
            }

            if (count($this->locked) > 25) {
                $this->line('    ... and '.(count($this->locked) - 25).' more');
            }
        }

        if ($this->emptied !== []) {
            $this->line('');
            $this->warn('  '.count($this->emptied).' product(s) end up with NO size rows - nothing they carry is in the library.');
            $this->warn('  They stay listed but cannot be added to a cart until a size is given back to them:');

            foreach (array_slice($this->emptied, 0, 25) as $line) {
                $this->line('    '.$line);
            }

            if (count($this->emptied) > 25) {
                $this->line('    ... and '.(count($this->emptied) - 25).' more');
            }
        }

        $this->line('');

        if ($apply) {
            $this->info('  Done. The filter rails have been retired and will rebuild on the next request.');
        } else {
            $this->comment('  Nothing was written. Re-run with --apply to commit - take a database backup first.');
        }

        $this->line('');
    }
}
