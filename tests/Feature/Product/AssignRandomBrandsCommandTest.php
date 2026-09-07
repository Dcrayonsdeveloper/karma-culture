<?php

namespace Tests\Feature\Product;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The command exists for one guarantee: after it runs, every brand the
 * storefront shows carries products. Everything else it does is in service of
 * not breaking something else on the way there.
 *
 * The failure modes worth pinning are all quiet ones. A per-product random
 * pick looks correct on 1230 products and leaves a brand empty on 12. An
 * id-ordered deal produces perfectly even counts while filing every romper
 * under one brand, because the catalogue was imported in supplier batches.
 * And going through save() would look identical in the database while firing
 * the two `saved` listeners Product::booted() hangs there - which write to the
 * category pivot and would bump updated_at on the whole catalogue.
 */
class AssignRandomBrandsCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @return \Illuminate\Support\Collection<int, Brand> */
    private function makeBrands(int $count = 7, bool $active = true)
    {
        return collect(range(1, $count))->map(fn ($i) => Brand::create([
            'name' => 'Brand '.$i.($active ? '' : ' (hidden)'),
            'is_active' => $active,
        ]));
    }

    /** @return \Illuminate\Support\Collection<int, Product> */
    private function makeProducts(int $count)
    {
        $category = Category::firstOrCreate(
            ['slug' => 'tees'],
            ['name' => 'Tees', 'is_active' => true],
        );

        return collect(range(1, $count))->map(fn ($i) => Product::create([
            'category_id' => $category->id,
            'name' => 'Product '.$i,
            'slug' => 'product-'.$i,
            'sku' => 'SKU-'.$i,
            'mrp' => 1499,
            'price' => 999,
            'stock_quantity' => 5,
            'stock_status' => 'in_stock',
            'is_active' => true,
            'status' => 'approved',
        ]));
    }

    /** @return array<int, int> brand id => live products */
    private function counts(): array
    {
        return Brand::withCount('products')->pluck('products_count', 'id')
            ->map(fn ($c) => (int) $c)->all();
    }

    /** The headline: nothing is left empty. */
    public function test_every_brand_ends_up_carrying_products(): void
    {
        $this->makeBrands(7);
        $this->makeProducts(60);

        $this->artisan('products:assign-brands', ['--apply' => true])->assertSuccessful();

        foreach ($this->counts() as $brandId => $count) {
            $this->assertGreaterThan(0, $count, "brand {$brandId} was left with an empty shelf");
        }

        $this->assertSame(0, Product::whereNull('brand_id')->count());
    }

    /**
     * Round-robin over a shuffled list, so the shares differ by at most one
     * however the division falls. 60/7 is 8 remainder 4 - the case a naive
     * "chunk into equal slices" gets wrong.
     */
    public function test_the_shares_are_even_to_within_one_product(): void
    {
        $this->makeBrands(7);
        $this->makeProducts(60);

        $this->artisan('products:assign-brands', ['--apply' => true])->assertSuccessful();

        $counts = array_values($this->counts());

        $this->assertSame(60, array_sum($counts));
        $this->assertLessThanOrEqual(1, max($counts) - min($counts), 'round-robin, not a lopsided deal');
    }

    /**
     * The reason the PRODUCTS are shuffled rather than the brands: ids are
     * grouped by supplier batch, so an id-ordered deal would hand each brand
     * one contiguous slab of the catalogue. With a fixed seed this is a fact
     * about the algorithm, not a flake.
     */
    public function test_the_deal_is_not_a_contiguous_slab_of_ids(): void
    {
        $this->makeBrands(4);
        $this->makeProducts(80);

        $this->artisan('products:assign-brands', ['--apply' => true, '--seed' => 20260907])->assertSuccessful();

        // Every brand should draw from across the whole id range rather than
        // one quarter of it. Ten times the mean gap would be a slab.
        foreach (Brand::pluck('id') as $brandId) {
            $ids = Product::where('brand_id', $brandId)->orderBy('id')->pluck('id')->all();

            $this->assertGreaterThan(1, count($ids));
            $this->assertGreaterThan(
                (max($ids) - min($ids)) / 2,
                count($ids) * 2,
                "brand {$brandId} holds one contiguous run of ids",
            );
        }

        // And no brand may own a whole quartile of the ids on its own.
        $firstQuarter = Product::orderBy('id')->limit(20)->pluck('brand_id')->unique();
        $this->assertGreaterThan(1, $firstQuarter->count(), 'the first 20 products are all one brand');
    }

    /** A product an admin already filed under a brand keeps it. */
    public function test_it_leaves_products_that_already_have_a_brand_alone(): void
    {
        $brands = $this->makeBrands(5);
        $products = $this->makeProducts(40);

        $chosen = $products->first();
        $chosen->update(['brand_id' => $brands->last()->id]);

        $this->artisan('products:assign-brands', ['--apply' => true])->assertSuccessful();

        $this->assertSame(
            $brands->last()->id,
            $chosen->fresh()->brand_id,
            "the admin's own assignment was overwritten",
        );
    }

    public function test_reassign_re_deals_the_products_that_already_have_a_brand(): void
    {
        $brands = $this->makeBrands(3);
        $this->makeProducts(30);

        // Everything under one brand, the way a bad first pass would leave it.
        Product::query()->update(['brand_id' => $brands->first()->id]);

        $this->artisan('products:assign-brands', ['--apply' => true, '--reassign' => true])->assertSuccessful();

        $counts = array_values($this->counts());

        $this->assertSame(30, array_sum($counts));
        $this->assertLessThanOrEqual(1, max($counts) - min($counts));
        $this->assertGreaterThan(0, min($counts), 'the other two brands are still empty');
    }

    /** Without --apply the command is a report and nothing else. */
    public function test_it_writes_nothing_without_apply(): void
    {
        $this->makeBrands(4);
        $this->makeProducts(20);

        $this->artisan('products:assign-brands')->assertSuccessful();

        $this->assertSame(20, Product::whereNull('brand_id')->count(), 'a dry run wrote to the database');
    }

    /** The seed the report prints deals the same hand when it is passed back. */
    public function test_the_same_seed_deals_the_same_hand(): void
    {
        $this->makeBrands(5);
        $this->makeProducts(45);

        $this->artisan('products:assign-brands', ['--apply' => true, '--seed' => 12345])->assertSuccessful();
        $first = Product::orderBy('id')->pluck('brand_id', 'id')->all();

        Product::query()->update(['brand_id' => null]);

        $this->artisan('products:assign-brands', ['--apply' => true, '--seed' => 12345])->assertSuccessful();
        $second = Product::orderBy('id')->pluck('brand_id', 'id')->all();

        $this->assertSame($first, $second);
    }

    public function test_a_different_seed_deals_a_different_hand(): void
    {
        $this->makeBrands(5);
        $this->makeProducts(45);

        $this->artisan('products:assign-brands', ['--apply' => true, '--seed' => 1])->assertSuccessful();
        $first = Product::orderBy('id')->pluck('brand_id', 'id')->all();

        Product::query()->update(['brand_id' => null]);

        $this->artisan('products:assign-brands', ['--apply' => true, '--seed' => 2])->assertSuccessful();
        $second = Product::orderBy('id')->pluck('brand_id', 'id')->all();

        $this->assertNotSame($first, $second);
    }

    /**
     * A hidden brand is one the storefront does not link to, so filing
     * products under it would take them off /brands without taking them off
     * the shop - a product that exists in two places and belongs to neither.
     */
    public function test_inactive_brands_are_left_out_by_default(): void
    {
        $active = $this->makeBrands(3);
        $hidden = Brand::create(['name' => 'Retired Label', 'is_active' => false]);

        $this->makeProducts(30);

        $this->artisan('products:assign-brands', ['--apply' => true])->assertSuccessful();

        $this->assertSame(0, Product::where('brand_id', $hidden->id)->count());
        $this->assertSame(30, Product::whereIn('brand_id', $active->pluck('id'))->count());
    }

    public function test_include_inactive_brands_deals_to_them_too(): void
    {
        $this->makeBrands(3);
        $hidden = Brand::create(['name' => 'Retired Label', 'is_active' => false]);

        $this->makeProducts(40);

        $this->artisan('products:assign-brands', ['--apply' => true, '--include-inactive-brands' => true])
            ->assertSuccessful();

        $this->assertGreaterThan(0, Product::where('brand_id', $hidden->id)->count());
    }

    /** A deleted product is not part of the catalogue, so it is not dealt. */
    public function test_soft_deleted_products_are_left_alone_by_default(): void
    {
        $this->makeBrands(3);
        $products = $this->makeProducts(30);

        $trashed = $products->last();
        $trashed->delete();

        $this->artisan('products:assign-brands', ['--apply' => true])->assertSuccessful();

        $this->assertNull(
            DB::table('products')->where('id', $trashed->id)->value('brand_id'),
            'a deleted product was dealt a brand',
        );
        $this->assertSame(0, Product::whereNull('brand_id')->count());
    }

    public function test_with_trashed_deals_to_soft_deleted_products(): void
    {
        $this->makeBrands(3);
        $products = $this->makeProducts(30);

        $trashed = $products->last();
        $trashed->delete();

        $this->artisan('products:assign-brands', ['--apply' => true, '--with-trashed' => true])->assertSuccessful();

        $this->assertNotNull(
            DB::table('products')->where('id', $trashed->id)->value('brand_id'),
            'a restored product would come back brandless',
        );
    }

    /**
     * The case a per-product random pick gets wrong. Seven brands, seven
     * products: every brand must get exactly one, and no draw may collide.
     */
    public function test_it_covers_every_brand_when_products_are_scarce(): void
    {
        $this->makeBrands(7);
        $this->makeProducts(7);

        $this->artisan('products:assign-brands', ['--apply' => true])->assertSuccessful();

        $this->assertSame([1, 1, 1, 1, 1, 1, 1], array_values($this->counts()));
    }

    /**
     * With fewer products than brands somebody has to go without - but it must
     * be a brand that already has stock, never one sitting empty.
     */
    public function test_empty_brands_are_served_before_stocked_ones(): void
    {
        $brands = $this->makeBrands(5);
        $products = $this->makeProducts(8);

        // Two brands already hold something; five products left to deal.
        $products->take(3)->each(fn ($p) => $p->update(['brand_id' => $brands->first()->id]));
        $products->slice(3, 2)->each(fn ($p) => $p->update(['brand_id' => $brands->get(1)->id]));

        $this->artisan('products:assign-brands', ['--apply' => true])->assertSuccessful();

        foreach ($this->counts() as $brandId => $count) {
            $this->assertGreaterThan(0, $count, "brand {$brandId} was skipped while a stocked brand was topped up");
        }
    }

    public function test_limit_deals_only_that_many_products(): void
    {
        $this->makeBrands(3);
        $this->makeProducts(30);

        $this->artisan('products:assign-brands', ['--apply' => true, '--limit' => 9])->assertSuccessful();

        $this->assertSame(9, Product::whereNotNull('brand_id')->count());
        $this->assertSame(21, Product::whereNull('brand_id')->count());
    }

    /**
     * The whole reason for the query builder. Product::booted() syncs the
     * primary category into the pivot on every `saved`, so dealing through
     * models would rewrite category_product for the entire catalogue - and
     * bump updated_at, which the sitemap publishes as lastmod.
     */
    public function test_it_touches_nothing_but_brand_id(): void
    {
        $this->makeBrands(4);
        $this->makeProducts(20);

        $before = DB::table('products')->orderBy('id')->pluck('updated_at', 'id')->all();
        $pivotBefore = DB::table('category_product')->count();

        $this->artisan('products:assign-brands', ['--apply' => true])->assertSuccessful();

        $this->assertSame($before, DB::table('products')->orderBy('id')->pluck('updated_at', 'id')->all());
        $this->assertSame($pivotBefore, DB::table('category_product')->count());
    }

    public function test_it_fails_when_there_are_no_brands_to_deal_to(): void
    {
        $this->makeProducts(5);

        $this->artisan('products:assign-brands', ['--apply' => true])->assertFailed();
    }

    /** Running it twice must not undo the first run or double-count anything. */
    public function test_a_second_run_is_a_no_op(): void
    {
        $this->makeBrands(4);
        $this->makeProducts(40);

        $this->artisan('products:assign-brands', ['--apply' => true])->assertSuccessful();
        $first = Product::orderBy('id')->pluck('brand_id', 'id')->all();

        $this->artisan('products:assign-brands', ['--apply' => true])->assertSuccessful();

        $this->assertSame($first, Product::orderBy('id')->pluck('brand_id', 'id')->all());
    }
}
