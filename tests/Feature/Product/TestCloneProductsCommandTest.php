<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The clone command's one genuinely subtle decision: what to do about the three
 * system collections.
 *
 * "New In", "Bestsellers" and "Introductory Offer" compute their own pages
 * until somebody ticks a product into them, at which point they show the picks
 * INSTEAD. So ticking a clone into an empty one replaces the real catalogue on
 * that page, and NOT ticking a clone into a full one leaves the clones off that
 * page entirely - opposite mistakes, one per configuration, and the storefront
 * looks plausible either way.
 *
 * Local development had all three empty and production had all three picked,
 * so a run that was verified locally was wrong on the site it was aimed at.
 * These tests pin both halves.
 */
class TestCloneProductsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeSourceProduct(): Product
    {
        $category = Category::create(['name' => 'Polos', 'slug' => 'polos', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Premium Polo',
            'slug' => 'premium-polo',
            'sku' => 'REAL-1',
            'description' => 'A real product an admin created.',
            'mrp' => 1499,
            'price' => 999,
            'stock_quantity' => 20,
            'stock_status' => 'in_stock',
            'is_active' => true,
            'status' => 'approved',
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'url' => 'https://example.test/polo.jpg',
            'position' => 0,
            'is_primary' => true,
        ]);

        return $product;
    }

    /** The three built-ins, as the migration creates them: live, and empty. */
    private function systemCollections(): void
    {
        foreach ([
            'new_in' => ['New In', 'new-in'],
            'bestsellers' => ['Bestsellers', 'bestsellers-picks'],
            'deals' => ['Introductory Offer', 'introductory-offer'],
        ] as $handle => [$name, $slug]) {
            // Through the system scope, not updateOrCreate. Category hides
            // system rows from ordinary queries, so updateOrCreate never finds
            // the row the migration already created and tries to insert a
            // second one - which the unique handle then rejects.
            $row = Category::system()->where('handle', $handle)->first();

            $row
                ? $row->update(['name' => $name, 'slug' => $slug, 'is_active' => true])
                : Category::create([
                    'handle' => $handle,
                    'name' => $name,
                    'slug' => $slug,
                    'is_system' => true,
                    'is_active' => true,
                ]);
        }
    }

    public function test_it_creates_clones_across_every_active_category(): void
    {
        $this->makeSourceProduct();
        Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'is_active' => true]);
        Category::create(['name' => 'Retired', 'slug' => 'retired', 'is_active' => false]);

        $this->artisan('products:test-clones', ['--count' => 3])->assertSuccessful();

        $clones = Product::where('sku', 'like', 'TESTCLONE-%')->get();
        $this->assertCount(3, $clones);

        // Both active categories, and never the inactive one - a clone filed
        // under a category customers cannot reach is invisible either way.
        foreach ($clones as $clone) {
            $this->assertEqualsCanonicalizing(
                ['polos', 'shirts'],
                $clone->categories()->pluck('slug')->all(),
            );
        }
    }

    public function test_every_clone_qualifies_for_the_computed_listings(): void
    {
        $this->makeSourceProduct();

        $this->artisan('products:test-clones', ['--count' => 6])->assertSuccessful();

        foreach (Product::where('sku', 'like', 'TESTCLONE-%')->get() as $clone) {
            $this->assertTrue($clone->is_active, 'every storefront listing filters on is_active');
            $this->assertTrue($clone->is_featured, 'the home page Featured row');
            $this->assertLessThan((float) $clone->mrp, (float) $clone->price, '/deals finds reductions');
            $this->assertGreaterThan(0, $clone->sales_count, '/bestsellers sorts on this');
            $this->assertSame('in_stock', $clone->stock_status);
            $this->assertGreaterThan(0, $clone->stock_quantity, 'both halves, or the card badges Out of Stock');
            $this->assertCount(3, $clone->variants, 'the size filter reads the variants');
            $this->assertNotEmpty(data_get($clone->attributes, 'Colours'), 'the shade filter reads this');
        }
    }

    public function test_it_copies_the_source_images_without_touching_the_originals(): void
    {
        $source = $this->makeSourceProduct();

        $this->artisan('products:test-clones', ['--count' => 2])->assertSuccessful();

        foreach (Product::where('sku', 'like', 'TESTCLONE-%')->with('images')->get() as $clone) {
            $this->assertCount(1, $clone->images);
            $this->assertSame('https://example.test/polo.jpg', $clone->images->first()->url);
            $this->assertTrue($clone->images->first()->is_primary);
        }

        $this->assertSame(1, $source->images()->count(), 'the source keeps its own image rows');
    }

    /**
     * An empty system collection is computing its page, so ticking a clone in
     * is what would stop it - and the clones do not need it.
     */
    public function test_it_leaves_an_empty_system_collection_empty(): void
    {
        $this->makeSourceProduct();
        $this->systemCollections();

        $this->artisan('products:test-clones', ['--count' => 4])->assertSuccessful();

        foreach (['new_in', 'bestsellers', 'deals'] as $handle) {
            $this->assertSame(
                [],
                Category::pickedProductIds($handle),
                "ticking a clone into the empty '{$handle}' would replace the catalogue on that page",
            );
        }
    }

    /**
     * A system collection that already holds picks has already stopped
     * computing, so a clone can only reach that page by being ticked in - and
     * doing so displaces nothing, because the existing picks stay.
     */
    public function test_it_joins_a_system_collection_that_already_has_picks(): void
    {
        $source = $this->makeSourceProduct();
        $this->systemCollections();

        $curated = Category::system()->where('handle', 'deals')->first();
        $curated->shownProducts()->attach($source->id);

        $this->artisan('products:test-clones', ['--count' => 4])->assertSuccessful();

        $picked = Category::pickedProductIds('deals');

        $this->assertContains($source->id, $picked, 'the admin\'s own pick must survive');
        $this->assertCount(5, $picked, 'the four clones joined it rather than replacing it');

        // The other two were empty, so they are still computing.
        $this->assertSame([], Category::pickedProductIds('new_in'));
        $this->assertSame([], Category::pickedProductIds('bestsellers'));
    }

    public function test_it_gives_a_video_to_some_of_the_clones_but_not_all(): void
    {
        $this->makeSourceProduct();

        $this->artisan('products:test-clones', ['--count' => 9, '--video-every' => 3, '--video' => '/images/reel.mp4'])
            ->assertSuccessful();

        $clones = Product::where('sku', 'like', 'TESTCLONE-%')->with('images')->get();

        $withVideo = $clones->filter(fn ($p) => $p->images->contains('media_type', 'video'));

        $this->assertCount(3, $withVideo, 'every third clone, not all nine');
        $this->assertCount(9, $clones, 'the ones without a video are still made');
    }

    /**
     * The product card on every listing paints the primary image. It cannot
     * play a video, so a video marked primary drops the clone back to the
     * no-image placeholder on the home page, the shop and every category.
     */
    public function test_a_video_is_never_the_primary_image(): void
    {
        $this->makeSourceProduct();

        $this->artisan('products:test-clones', ['--count' => 3, '--video-every' => 1, '--video' => '/images/reel.mp4'])
            ->assertSuccessful();

        foreach (Product::where('sku', 'like', 'TESTCLONE-%')->with('images')->get() as $clone) {
            $video = $clone->images->firstWhere('media_type', 'video');

            $this->assertNotNull($video);
            $this->assertFalse((bool) $video->is_primary);
            $this->assertSame('image', $clone->images->firstWhere('is_primary', true)->media_type);

            // The poster, so the gallery thumb is the garment rather than a
            // black frame - and what the PDP hands <video poster="...">.
            $this->assertSame('https://example.test/polo.jpg', $video->thumbnail_url);

            // Last in the gallery, so the photos come first.
            $this->assertSame($clone->images->max('position'), $video->position);
        }
    }

    /**
     * Production's source product leads its gallery with an admin-uploaded
     * video. Copying "position 0 is the primary" straight across would make a
     * video the primary image on all forty clones, and every listing card on
     * the site would fall through to the no-image placeholder.
     */
    public function test_a_source_that_leads_with_a_video_still_yields_a_photo_primary(): void
    {
        $source = $this->makeSourceProduct();

        // Push the photo behind a video, so position 0 is the video.
        $source->images()->update(['position' => 1]);
        ProductImage::create([
            'product_id' => $source->id,
            'media_type' => 'video',
            'url' => '/storage/products/videos/source-own.mp4',
            'position' => 0,
            'is_primary' => false,
        ]);

        $this->artisan('products:test-clones', ['--count' => 3, '--video-every' => 0])->assertSuccessful();

        foreach (Product::where('sku', 'like', 'TESTCLONE-%')->with('images')->get() as $clone) {
            $primaries = $clone->images->where('is_primary', true);

            $this->assertCount(1, $primaries, 'exactly one primary, or the card picks arbitrarily');
            $this->assertSame('image', $primaries->first()->media_type);
            $this->assertSame('https://example.test/polo.jpg', $primaries->first()->url);

            // The source's own video still comes along - it is part of the product.
            $this->assertTrue($clone->images->contains('media_type', 'video'));
        }
    }

    public function test_video_can_be_switched_off(): void
    {
        $this->makeSourceProduct();

        $this->artisan('products:test-clones', ['--count' => 4, '--video-every' => 0])->assertSuccessful();

        $this->assertSame(
            0,
            ProductImage::whereIn('product_id', Product::where('sku', 'like', 'TESTCLONE-%')->pluck('id'))
                ->where('media_type', 'video')
                ->count(),
        );
    }

    public function test_delete_removes_every_clone_and_nothing_else(): void
    {
        $source = $this->makeSourceProduct();

        $this->artisan('products:test-clones', ['--count' => 5])->assertSuccessful();
        $this->assertSame(5, Product::where('sku', 'like', 'TESTCLONE-%')->count());

        $this->artisan('products:test-clones', ['--delete' => true])->assertSuccessful();

        // withTrashed, because a soft delete would leave the rows behind and
        // still read as gone from the storefront.
        $this->assertSame(0, Product::withTrashed()->where('sku', 'like', 'TESTCLONE-%')->count());
        $this->assertDatabaseHas('products', ['id' => $source->id, 'deleted_at' => null]);
        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_two_runs_do_not_collide_on_the_unique_columns(): void
    {
        $this->makeSourceProduct();

        $this->artisan('products:test-clones', ['--count' => 3])->assertSuccessful();
        $this->artisan('products:test-clones', ['--count' => 3])->assertSuccessful();

        $clones = Product::where('sku', 'like', 'TESTCLONE-%')->get();

        $this->assertCount(6, $clones);
        $this->assertCount(6, $clones->pluck('sku')->unique(), 'sku is unique-constrained');
        $this->assertCount(6, $clones->pluck('slug')->unique(), 'slug is unique-constrained');
        $this->assertCount(6, $clones->pluck('uuid')->unique(), 'uuid is unique-constrained');
    }

    public function test_it_never_clones_a_clone(): void
    {
        $this->makeSourceProduct();

        $this->artisan('products:test-clones', ['--count' => 2])->assertSuccessful();
        $this->artisan('products:test-clones', ['--count' => 2])->assertSuccessful();

        // The default source is chosen by image count, and clones carry a copy
        // of the source's image - so without the guard the second run would
        // pick a clone and compound the naming.
        foreach (Product::where('sku', 'like', 'TESTCLONE-%')->get() as $clone) {
            $this->assertStringNotContainsString(
                'Test Copy 001 - Test Copy',
                $clone->name,
                'the source must always be a real product',
            );
        }
    }
}
