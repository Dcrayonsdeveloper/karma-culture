<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Spread the catalogue across the brands, so no brand is an empty shelf.
 *
 * The brands were created in the admin long before anything was filed under
 * them: /brands rendered seven tiles reading "0 products", six of them leading
 * to a page saying the brand had nothing, and the shop sidebar's Brand facet
 * offered exactly one choice - that facet is derived from the products, so a
 * brand no product carries is a brand the shopper never sees.
 *
 * The deal, in order:
 *
 *   1. Shuffle the PRODUCTS, not the brands. Assigning brand = id % 7 would be
 *      just as even and completely wrong: the catalogue was imported in
 *      supplier batches, so consecutive ids are the same kind of garment, and
 *      each brand would end up holding one contiguous slab of it.
 *   2. Deal the shuffled list round-robin. Shares then differ by at most one
 *      product however many brands there are, and no brand can be skipped -
 *      which is the whole point, and what a per-product random pick does NOT
 *      give you: seven independent draws over 1230 products will not leave a
 *      brand empty, but over a dozen products they easily can.
 *   3. Rotate the brands holding nothing to the front, so when there are fewer
 *      products than brands the empty ones are served first.
 *
 * Writes go through the query builder rather than save(), and every reason for
 * that is a side effect that has nothing to do with brands:
 *
 *   - Product uses spatie's HasSlug with generateSlugsOnUpdate left on, so
 *     saving a product REGENERATES its slug from its current name. The names
 *     were corrected by raw SQL after the import and the slugs never caught up
 *     - that is the whole reason products:refresh-slugs exists - so saving the
 *     catalogue to set one integer column would quietly rewrite the address of
 *     every product on the site, with no redirects filed.
 *   - Product::booted() hangs two listeners on `saved`: a filter-cache bump,
 *     and a categories()->syncWithoutDetaching() that writes to the category
 *     pivot.
 *   - save() stamps updated_at. Nothing sorts on it, but the sitemap publishes
 *     it as lastmod - "every product on the site changed at 10:41" is a lie to
 *     tell a crawler - and the inventory report reads it as the dead-stock
 *     signal, which touching all 1230 rows would silently empty for 90 days.
 *
 * A single UPDATE ... WHERE id IN (...) per brand has none of them.
 *
 * Reports only unless --apply is passed, like products:refresh-slugs - filing
 * the whole catalogue under brands is not something to do as a side effect of
 * running a command to see what it would do.
 */
class AssignRandomBrands extends Command
{
    protected $signature = 'products:assign-brands
                            {--apply : Write the assignments. Without this the command only reports.}
                            {--reassign : Re-deal products that already have a brand, instead of only filling the empty ones.}
                            {--with-trashed : Include soft-deleted products, so a restored one is not brandless.}
                            {--include-inactive-brands : Deal to hidden brands too. By default only brands the storefront shows.}
                            {--limit= : Only deal this many products, for a cautious first run.}
                            {--seed= : Shuffle deterministically, so a report and the run that follows deal the same hand.}';

    protected $description = 'Randomly assign the existing brands across the catalogue so every brand carries products';

    /** How many ids go into one UPDATE ... WHERE id IN (...). */
    private const CHUNK = 500;

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;

        $brands = Brand::query()
            ->unless($this->option('include-inactive-brands'), fn ($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($brands->isEmpty()) {
            $this->error('No brands to deal to. Create some at /admin/brands first.');

            return self::FAILURE;
        }

        if (! $apply) {
            $this->warn('Dry run. Nothing is written. Re-run with --apply to make these changes.');
        }

        // The seed is echoed back whether it was given or generated, so the
        // hand a report showed can be dealt for real by passing it straight
        // back in - and a deal that has to be explained afterwards can be
        // reproduced exactly.
        $seed = $this->option('seed') !== null
            ? (int) $this->option('seed')
            : random_int(1, PHP_INT_MAX);

        $randomizer = new Randomizer(new Mt19937($seed));

        $productIds = $this->targetProducts($limit);

        $this->newLine();
        $this->line('  Brands       : '.$brands->count());
        $this->line('  Products     : '.count($productIds)
            .($this->option('reassign') ? ' (re-dealing every product)' : ' (only the ones with no brand)'));
        $this->line('  Soft-deleted : '.($this->option('with-trashed') ? 'included' : 'left alone'));
        $this->line('  Seed         : '.$seed);

        if ($productIds === []) {
            $this->newLine();
            $this->warn('No products to deal. Pass --reassign to re-deal the ones that already have a brand.');
            $this->report($brands);

            return self::SUCCESS;
        }

        $order = $this->dealOrder($brands, $randomizer);
        $shuffled = $randomizer->shuffleArray($productIds);

        /** @var array<int, array<int, int>> $deal  brand id => the product ids dealt to it */
        $deal = [];

        foreach ($shuffled as $i => $productId) {
            $deal[$order[$i % count($order)]][] = $productId;
        }

        if (! $apply) {
            $this->newLine();
            $this->table(
                ['Brand', 'Would be dealt'],
                $brands->map(fn ($b) => [$b->name, count($deal[$b->id] ?? [])])->all(),
            );
            $this->line('  Deal it with:  php artisan products:assign-brands --apply --seed='.$seed);

            return self::SUCCESS;
        }

        $written = 0;

        // One transaction for the whole deal: a half-filed catalogue is worse
        // than an unfiled one, because the next run's "only the ones with no
        // brand" would top up the tail without knowing where this one stopped.
        DB::transaction(function () use ($deal, &$written) {
            foreach ($deal as $brandId => $ids) {
                foreach (array_chunk($ids, self::CHUNK) as $chunk) {
                    $written += DB::table('products')
                        ->whereIn('id', $chunk)
                        ->update(['brand_id' => $brandId]);
                }
            }
        });

        $this->newLine();
        $this->info("{$written} products updated.");

        // Nothing here went through a model, so nothing bumped the counter the
        // derived shop rails and the chatbot's category list are cached
        // against. Once, for the whole run.
        ProductVariant::bumpFilterCache();

        $this->report($brands);

        $this->newLine();
        $this->line('  Similar-product rails rank on category+brand and are cached for 30 minutes.');
        $this->line('  Clear them now with:  php artisan cache:clear-products');

        return self::SUCCESS;
    }

    /**
     * The products this run is allowed to touch.
     *
     * Query builder, so soft-deleted rows are in scope only when they were
     * asked for - the Eloquent scope would hide them either way.
     *
     * @return array<int, int>
     */
    private function targetProducts(?int $limit): array
    {
        return DB::table('products')
            ->unless($this->option('with-trashed'), fn ($q) => $q->whereNull('deleted_at'))
            ->unless($this->option('reassign'), fn ($q) => $q->whereNull('brand_id'))
            ->orderBy('id')
            ->when($limit !== null, fn ($q) => $q->limit($limit))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * The rotation the round-robin deals into.
     *
     * Shuffled, so the brands that pick up the remainder when the split is not
     * exact are not always the same ones; then the brands carrying nothing are
     * pulled to the front, which only matters when there are fewer products
     * than brands - and that is exactly the case where being skipped is
     * permanent.
     *
     * @param  Collection<int, Brand>  $brands
     * @return array<int, int>
     */
    private function dealOrder(Collection $brands, Randomizer $randomizer): array
    {
        $held = $this->liveCountsByBrand();

        $ids = $randomizer->shuffleArray($brands->pluck('id')->map(fn ($id) => (int) $id)->all());

        // A partition rather than a sort: all this needs is "empty first,
        // shuffled order within each half", and sorting would throw away the
        // shuffle it was just handed.
        $empty = array_values(array_filter($ids, fn ($id) => ($held[$id] ?? 0) === 0));
        $stocked = array_values(array_filter($ids, fn ($id) => ($held[$id] ?? 0) > 0));

        return array_merge($empty, $stocked);
    }

    /**
     * What the storefront will show, read back from the database rather than
     * from the deal - the point of the command is the counts on /brands, and
     * those come from a query.
     *
     * @param  Collection<int, Brand>  $brands
     */
    private function report(Collection $brands): void
    {
        $counts = $this->liveCountsByBrand();

        $this->newLine();
        $this->table(
            ['Brand', 'Live products'],
            $brands->map(fn ($b) => [$b->name, $counts[$b->id] ?? 0])->all(),
        );

        $empty = $brands->filter(fn ($b) => ($counts[$b->id] ?? 0) === 0);

        if ($empty->isNotEmpty()) {
            $this->warn('Still empty: '.$empty->pluck('name')->implode(', '));
        }

        $orphans = DB::table('products')->whereNull('deleted_at')->whereNull('brand_id')->count();

        if ($orphans > 0) {
            $this->warn("{$orphans} live products still have no brand.");
        }
    }

    /**
     * Products per brand, counting only what the storefront can reach.
     *
     * @return array<int, int>
     */
    private function liveCountsByBrand(): array
    {
        return DB::table('products')
            ->selectRaw('brand_id, count(*) as aggregate')
            ->whereNull('deleted_at')
            ->whereNotNull('brand_id')
            ->groupBy('brand_id')
            ->pluck('aggregate', 'brand_id')
            ->mapWithKeys(fn ($count, $brandId) => [(int) $brandId => (int) $count])
            ->all();
    }
}
