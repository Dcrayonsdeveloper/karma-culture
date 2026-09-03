<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ShopFilterItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fill every listing on the storefront with copies of one real product.
 *
 * Made for testing layout and pagination, not for selling. The catalogue is
 * small enough that most pages show two or three cards or none at all, so a
 * grid that breaks at the third row, a paginator that never appears, a filter
 * that silently matches nothing - none of it is visible on the real data. This
 * puts enough stock through every shelf to make those faults show.
 *
 * Every clone is marked by its SKU prefix and nothing else, which is what makes
 * `--delete` safe: it can name exactly the rows this command created without
 * touching a product a human made. Nothing here is idempotent by accident -
 * running it twice makes a second batch, and --delete removes both.
 *
 * Three deliberate choices, because each one is a trap:
 *
 * - Clones are written through Eloquent, never a bulk insert. The uuid, the
 *   slug, the primary category pivot and the warehouse shelves are all filled
 *   in by model events; a raw insert produces rows that look complete in the
 *   products table and are invisible or unbuyable everywhere else.
 *
 * - The three system collections - "New In", "Bestsellers", "Introductory
 *   Offer" - are joined only when they ALREADY have something ticked into
 *   them, and the rule turns on that because ticking means opposite things in
 *   the two cases. An empty one is computing its page: newest by date, by
 *   sales count, whatever is discounted. Ticking the first product into it is
 *   precisely what stops it computing, so a clone would REPLACE the real
 *   catalogue on that page rather than join it - and left alone the clones
 *   qualify on their own merits anyway. One that already holds picks has
 *   already stopped computing, so it will never find a clone on merit no
 *   matter how new or how discounted it is; there, ticking is the only way on
 *   to the page, and it is purely additive because the existing picks stay.
 *   Production had all three picked and local had none, which is the whole
 *   reason this is decided per collection at runtime rather than once here.
 *
 * - Sizes, shades and price bands are spread across the batch rather than
 *   copied identically. The "Shop It Your Way" hangers and the shop sidebar
 *   filter on those three, and forty products that are all size M leave every
 *   hanger but one leading to an empty page - which is the exact fault this
 *   data is meant to expose.
 */
class SeedTestCloneProducts extends Command
{
    protected $signature = 'products:test-clones
                            {--count=40 : How many copies to create}
                            {--source= : Slug or id of the product to copy (default: the active product with the most images)}
                            {--delete : Remove every clone this command has made, and nothing else}';

    protected $description = 'Create copies of one product across every category and listing, for testing empty and single-item pages';

    /** What marks a row as ours. Deliberately not a name or a flag a human might reuse. */
    private const SKU_PREFIX = 'TESTCLONE-';

    /** Used when the storefront has no shade hangers configured to read them from. */
    private const FALLBACK_SHADES = [
        'Ecru' => '#f0d9b8', 'Sand' => '#e0c9a6', 'Tan' => '#d2a679', 'Camel' => '#c19a6b',
        'Chestnut' => '#954535', 'Espresso' => '#4b3621', 'Ink' => '#26303b', 'Onyx' => '#353839',
        'Clay' => '#b66a50', 'Ochre' => '#cc7722', 'Umber' => '#635147', 'Char' => '#36454f',
    ];

    private const FALLBACK_SIZES = ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'];

    /**
     * One clone per band, so the sidebar's price slider and every price hanger
     * has something on either side of it. mrp is always above price - that is
     * what puts the whole batch on /deals and draws the discount badge.
     */
    private const PRICE_BANDS = [
        [499, 999], [1199, 1899], [2199, 2999], [3499, 4999], [5499, 7999], [8999, 12999],
    ];

    public function handle(): int
    {
        if ($this->option('delete')) {
            return $this->deleteClones();
        }

        $count = max(1, (int) $this->option('count'));

        $source = $this->resolveSource();
        if (! $source) {
            $this->error('  No product to copy. The catalogue is empty, or --source matched nothing.');

            return self::FAILURE;
        }

        $categories = Category::query()->where('is_active', true)->orderBy('id')->get(['id', 'name']);
        if ($categories->isEmpty()) {
            $this->error('  No active categories. A clone with no category is invisible on every listing.');

            return self::FAILURE;
        }

        $collections = ProductCollection::query()
            ->where('is_active', true)
            ->where('is_system', false)
            ->get(['id', 'name']);

        // The three system collections split by what is already ticked into
        // them, because the same action means opposite things in the two cases
        // - see the class docblock.
        $systemPicked = ProductCollection::query()
            ->where('is_active', true)
            ->where('is_system', true)
            ->has('products')
            ->get(['id', 'name', 'handle']);

        $systemEmpty = ProductCollection::query()
            ->where('is_active', true)
            ->where('is_system', true)
            ->doesntHave('products')
            ->get(['id', 'name', 'handle']);

        $collections = $collections->concat($systemPicked);

        $shades = $this->shades();
        $sizes = $this->sizes();
        $sourceImages = $source->images()->orderBy('position')->get();

        $this->newLine();
        $this->line('  Copying     : '.$source->name.'  (id '.$source->id.', sku '.$source->sku.')');
        $this->line('  Copies      : '.$count);
        $this->line('  Categories  : '.$categories->count().' - every clone joins all of them');
        $this->line('  Collections : '.($collections->isEmpty() ? 'none' : $collections->pluck('name')->implode(', ')));
        $this->line('  Left alone  : '.($systemEmpty->isEmpty()
            ? 'nothing'
            : $systemEmpty->pluck('name')->implode(', ').' - empty, so those pages still compute themselves'));
        $this->line('  Sizes       : '.implode(', ', $sizes));
        $this->line('  Shades      : '.implode(', ', array_keys($shades)));
        $this->line('  Images      : '.$sourceImages->count().' per clone, reusing the source URLs');
        $this->newLine();

        $existing = $this->cloneQuery()->count();
        if ($existing > 0) {
            $this->warn('  '.$existing.' clone(s) already exist. This adds a fresh batch beside them.');
            $this->newLine();
        }

        $categoryIds = $categories->pluck('id')->all();
        $collectionIds = $collections->pluck('id')->all();
        $shadeNames = array_keys($shades);
        $batch = Str::lower(Str::random(4));

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $made = [];

        // One transaction: a half-written batch would leave clones that are in
        // some categories and no collections, which reads as a storefront bug
        // rather than an interrupted command.
        DB::transaction(function () use (
            $count, $source, $categoryIds, $collectionIds, $shades, $shadeNames,
            $sizes, $sourceImages, $batch, $bar, &$made
        ) {
            for ($i = 1; $i <= $count; $i++) {
                $n = str_pad((string) $i, 3, '0', STR_PAD_LEFT);

                [$price, $mrp] = self::PRICE_BANDS[($i - 1) % count(self::PRICE_BANDS)];
                $shade = $shadeNames[($i - 1) % count($shadeNames)];

                // The primary category rotates, so every category owns a few
                // clones outright - the breadcrumb, the canonical URL and the
                // category counts all read from this one, not from the pivot.
                $primaryCategoryId = $categoryIds[($i - 1) % count($categoryIds)];

                $clone = new Product([
                    'brand_id' => $source->brand_id,
                    'seller_id' => $source->seller_id,
                    'category_id' => $primaryCategoryId,
                    'name' => $source->name.' - Test Copy '.$n,
                    'slug' => 'test-copy-'.$batch.'-'.$n.'-'.Str::slug($source->name),
                    'short_description' => $source->short_description,
                    'description' => $source->description,
                    'sku' => self::SKU_PREFIX.Str::upper($batch).'-'.$n,
                    'mrp' => $mrp,
                    'price' => $price,
                    'cost_price' => round($price * 0.4, 2),
                    'stock_quantity' => 40 + ($i * 3),
                    'low_stock_threshold' => 10,
                    'stock_status' => 'in_stock',
                    'weight_unit' => $source->weight_unit,
                    'dimension_unit' => $source->dimension_unit,
                    'is_active' => true,
                    // Puts the batch in the home page's Featured row, which is
                    // otherwise the emptiest row on the site.
                    'is_featured' => true,
                    'is_taxable' => $source->is_taxable,
                    'tax_rate' => $source->tax_rate,
                    // Never zero: Bestsellers sorts on this, and a batch of
                    // zeroes sorts arbitrarily and tests nothing.
                    'sales_count' => 1000 - ($i * 7),
                    'rating' => round(3.5 + (($i % 4) * 0.35), 2),
                    'review_count' => 5 + ($i % 40),
                    // Where the shade filter reads from. The variant carries the
                    // size; the colour lives on the product.
                    'attributes' => ['Colours' => [['name' => $shade, 'hex' => $shades[$shade]]]],
                    'specifications' => $source->specifications,
                    'feature_highlights' => $source->feature_highlights,
                    'status' => 'approved',
                    // Staggered rather than identical, so "newest first" has a
                    // real order to produce instead of a tie across 40 rows.
                    'published_at' => now()->subMinutes($i),
                ]);

                $clone->save();

                // created_at drives New Arrivals. Set after the insert so the
                // model's own timestamps do not overwrite it.
                $clone->forceFill(['created_at' => now()->subMinutes($i)])->saveQuietly();

                // Every shelf, so each of the category pages has a full grid.
                // The primary is already in here via the model's saved() hook;
                // sync covers it again harmlessly.
                $clone->categories()->sync($categoryIds);

                if ($collectionIds !== []) {
                    $clone->collections()->sync($collectionIds);
                }

                foreach ($sourceImages as $position => $image) {
                    ProductImage::create([
                        'product_id' => $clone->id,
                        'media_type' => $image->media_type,
                        'url' => $image->url,
                        'thumbnail_url' => $image->thumbnail_url,
                        'alt_text' => $clone->name,
                        'position' => $position,
                        'is_primary' => $position === 0,
                    ]);
                }

                // Three consecutive sizes per clone, walking the list, so the
                // size hangers all resolve and no single clone carries every
                // size (which would make the size filter useless as a test).
                for ($s = 0; $s < 3; $s++) {
                    $size = $sizes[($i - 1 + $s) % count($sizes)];

                    ProductVariant::create([
                        'product_id' => $clone->id,
                        'name' => $size,
                        'sku' => self::SKU_PREFIX.Str::upper($batch).'-'.$n.'-'.Str::upper($size),
                        'mrp' => $mrp,
                        'price' => $price,
                        'stock_quantity' => 15,
                        'attributes' => ['size' => $size, 'color' => $shade],
                        'is_active' => true,
                    ]);
                }

                $made[] = $clone->id;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info('  Created '.count($made).' clones, sku '.self::SKU_PREFIX.Str::upper($batch).'-001 .. -'.str_pad((string) $count, 3, '0', STR_PAD_LEFT));
        $this->newLine();
        $this->line('  They now appear on:');
        $this->line('    /                     featured, new arrivals, bestsellers, trending and deals rows');
        $this->line('    /shop                 all of them, paginated');
        // Said per page, because how a clone got there differs by how the
        // collection behind it was set up, and "it should be there" is not
        // worth much next to "here is why it is there".
        $picked = $systemPicked->pluck('handle')->all();
        $why = fn (string $handle, string $computed) => in_array($handle, $picked, true)
            ? 'ticked in beside the existing picks'
            : $computed;

        $this->line('    /new-arrivals         '.$why('new_in', 'newest first'));
        $this->line('    /bestsellers          '.$why('bestsellers', 'by sales count'));
        $this->line('    /deals                '.$why('deals', 'every clone is priced under its MRP'));
        $this->line('    /category/{slug}      all '.$categories->count().' categories');
        $this->line('    /search?q=Test+Copy   by name');
        if ($collectionIds !== []) {
            $this->line('    /collection/{slug}    '.count($collectionIds).' collection(s): '.$collections->pluck('name')->implode(', '));
        }
        $this->newLine();
        $this->line('  Remove them with:  php artisan products:test-clones --delete');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Take the batch back out.
     *
     * Variants are deleted through Eloquent first and the product force-deleted
     * after. Both matter: the database's own cascade would take the variant
     * rows without firing the model event that clears their warehouse shelves,
     * and a soft delete deliberately leaves shelves alone because it can be
     * undone - so a plain delete() here would leave the warehouse counting
     * stock for products that no longer exist.
     */
    private function deleteClones(): int
    {
        $clones = $this->cloneQuery()->get(['id', 'name', 'sku']);

        if ($clones->isEmpty()) {
            $this->newLine();
            $this->line('  No test clones found. Nothing to remove.');
            $this->newLine();

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('  Removing '.$clones->count().' test clone(s).');

        DB::transaction(function () use ($clones) {
            foreach ($clones as $clone) {
                $product = Product::withTrashed()->with('variants')->find($clone->id);

                if (! $product) {
                    continue;
                }

                foreach ($product->variants as $variant) {
                    $variant->delete();
                }

                $product->forceDelete();
            }
        });

        $this->info('  Removed. The storefront is back to the real catalogue.');
        $this->newLine();

        return self::SUCCESS;
    }

    /** Every product this command has ever made, across batches, and nothing else. */
    private function cloneQuery()
    {
        return Product::withTrashed()->where('sku', 'like', self::SKU_PREFIX.'%');
    }

    /**
     * The product to copy.
     *
     * Defaults to the best-furnished one rather than the first, because a clone
     * of a product with no image tests the grid with forty placeholder tiles.
     * Never picks an existing clone, which would compound rounding and naming
     * across runs.
     */
    private function resolveSource(): ?Product
    {
        $source = $this->option('source');

        if ($source) {
            return Product::query()
                ->where('slug', $source)
                ->orWhere('id', is_numeric($source) ? (int) $source : 0)
                ->first();
        }

        return Product::query()
            ->where('is_active', true)
            ->where('sku', 'not like', self::SKU_PREFIX.'%')
            ->withCount('images')
            ->orderByDesc('images_count')
            ->orderBy('id')
            ->first();
    }

    /** @return array<string, string> shade name => hex */
    private function shades(): array
    {
        $configured = ShopFilterItem::query()
            ->where('type', 'shade')
            ->where('is_active', true)
            ->orderBy('position')
            ->get(['label', 'shade_hex'])
            ->filter(fn ($s) => trim((string) $s->label) !== '')
            ->mapWithKeys(fn ($s) => [trim($s->label) => $s->shade_hex ?: '#c19a6b'])
            ->all();

        return $configured !== [] ? $configured : self::FALLBACK_SHADES;
    }

    /** @return array<int, string> */
    private function sizes(): array
    {
        $configured = ShopFilterItem::query()
            ->where('type', 'size')
            ->where('is_active', true)
            ->orderBy('position')
            ->pluck('label')
            ->map(fn ($l) => trim((string) $l))
            ->filter()
            ->values()
            ->all();

        return $configured !== [] ? $configured : self::FALLBACK_SIZES;
    }
}
