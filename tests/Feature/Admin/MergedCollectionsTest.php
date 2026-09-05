<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Categories and collections as one system.
 *
 * They used to be two tables, two admin screens and two sidebar entries - both
 * entries labelled "Collections", pointing at unrelated things, because the
 * categories screen had been relabelled without the code following. This pins
 * the merged shape, and above all the thing that would be worst to get wrong:
 * a built-in listing is a destination, not a classification, so it must never
 * leak into the tree, the menu, the facets, the sitemap or a product's
 * breadcrumb.
 *
 * Replaces the old CollectionsTest, which exercised an admin screen that no
 * longer exists.
 */
class MergedCollectionsTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create(['user_id' => $this->adminUser->id, 'role' => 'super_admin', 'is_active' => true]);
    }

    private function actingAsAdmin(): self
    {
        $this->actingAs($this->adminUser, 'admin');

        return $this;
    }

    // ------------------------------------------------------------ the merge --

    public function test_the_built_in_listings_are_rows_in_the_categories_table(): void
    {
        foreach (['new_in', 'bestsellers', 'deals', 'shop_all'] as $handle) {
            $this->assertNotNull(
                Category::system()->where('handle', $handle)->first(),
                "The built-in listing '{$handle}' did not survive the merge into categories."
            );
        }
    }

    public function test_the_old_collections_table_is_gone(): void
    {
        $this->assertFalse(\Schema::hasTable('collections'), 'Two tables still hold the same idea.');
        $this->assertFalse(\Schema::hasTable('collection_product'));
    }

    public function test_shop_all_exists_as_a_listing(): void
    {
        // It was the one built-in page with no row to tick into.
        $shopAll = Category::system()->where('handle', 'shop_all')->first();

        $this->assertNotNull($shopAll);
        $this->assertSame('Shop All', $shopAll->name);
    }

    // ------------------------------- system rows must not act like categories --

    public function test_a_system_row_is_invisible_to_an_ordinary_category_query(): void
    {
        // The global scope is the whole safety net: every browse surface - the
        // mega menu, the tree, breadcrumbs, the shop facet, the sitemap, search,
        // the API, the admin parent pickers - queries Category:: directly. If
        // one of them saw a system row, "Bestsellers" would appear as a
        // category somebody could file a product under.
        Category::create(['name' => 'Kurtas', 'slug' => 'kurtas', 'is_active' => true]);

        $names = Category::all()->pluck('name')->all();

        $this->assertContains('Kurtas', $names);
        $this->assertNotContains('Bestsellers', $names);
        $this->assertNotContains('Shop All', $names);
    }

    public function test_a_system_row_is_not_a_root_category(): void
    {
        // scopeRoots() alone could not have kept them out: a system row has a
        // null parent_id too, so it looks exactly like a root.
        $roots = Category::roots()->pluck('name')->all();

        $this->assertNotContains('New In', $roots);
    }

    public function test_a_system_row_is_kept_out_of_the_sitemap(): void
    {
        $this->get('/sitemap-categories.xml')
            ->assertOk()
            ->assertDontSee('bestsellers-picks')
            ->assertDontSee('shop-all');
    }

    public function test_the_tree_used_by_the_filters_holds_no_system_rows(): void
    {
        $names = (new \App\Support\CategoryTree)->rows()->pluck('name')->all();

        $this->assertNotContains('New In', $names);
        $this->assertNotContains('Shop All', $names);
    }

    // ------------------------------------------------------------- storefront --

    public function test_a_real_category_is_not_served_from_the_collection_url(): void
    {
        // Two URLs for one page is duplicate content, and RouteIntegrityTest
        // fails the build when two paths answer 200 from the same action. A
        // category lives at /category/{slug} and nowhere else.
        Category::create(['name' => 'Kurtas', 'slug' => 'kurtas', 'is_active' => true]);

        $this->get('/collection/kurtas')->assertNotFound();
        $this->get('/category/kurtas')->assertOk();
    }

    public function test_a_system_row_still_answers_on_its_collection_url(): void
    {
        $row = Category::system()->where('handle', 'new_in')->firstOrFail();

        $this->get('/collection/'.$row->slug)->assertOk();
    }

    /** The ids /products actually rendered, rather than a substring search. */
    private function shopIds(): array
    {
        return $this->get('/products')->assertOk()
            ->viewData('products')->pluck('id')->sort()->values()->all();
    }

    public function test_shop_all_computes_the_whole_catalogue_until_something_is_ticked(): void
    {
        $category = Category::create(['name' => 'Kurtas', 'slug' => 'kurtas', 'is_active' => true]);

        $one = $this->product('Amber Kurta', 'amber-kurta', $category);
        $two = $this->product('Indigo Kurta', 'indigo-kurta', $category);

        $this->assertSame([$one->id, $two->id], $this->shopIds());

        // Tick one in and the page shows the picks instead.
        Category::system()->where('handle', 'shop_all')->firstOrFail()->shownProducts()->sync([$one->id]);

        $this->assertSame(
            [$one->id],
            $this->shopIds(),
            'Ticking a product into Shop All did not narrow /products to the picks.'
        );
    }

    public function test_unticking_shop_all_puts_the_whole_catalogue_back(): void
    {
        $category = Category::create(['name' => 'Kurtas', 'slug' => 'kurtas', 'is_active' => true]);
        $one = $this->product('Amber Kurta', 'amber-kurta', $category);
        $two = $this->product('Indigo Kurta', 'indigo-kurta', $category);

        $shopAll = Category::system()->where('handle', 'shop_all')->firstOrFail();
        $shopAll->shownProducts()->sync([$one->id]);
        $shopAll->shownProducts()->sync([]);

        $this->assertSame(
            [$one->id, $two->id],
            $this->shopIds(),
            'Unticking everything should hand /products back to the whole catalogue.'
        );
    }

    public function test_the_filter_drawer_is_bound_the_same_way_as_the_listing(): void
    {
        // The drawer is fetched on every page of the site. Overriding only the
        // listing would leave the sidebar counting the whole catalogue while
        // the grid showed the picks - the sidebar saying 40, the page showing 1.
        $category = Category::create(['name' => 'Kurtas', 'slug' => 'kurtas', 'is_active' => true]);
        $one = $this->product('Amber Kurta', 'amber-kurta', $category);
        $this->product('Indigo Kurta', 'indigo-kurta', $category);

        Category::system()->where('handle', 'shop_all')->firstOrFail()->shownProducts()->sync([$one->id]);

        $listing = $this->get('/products')->assertOk();
        $drawer = $this->get('/products/filters')->assertOk();

        $this->assertSame(
            1,
            $listing->viewData('products')->total(),
            'The listing did not honour the Shop All picks.'
        );
        $this->assertSame(
            $listing->viewData('filterPanel')['counts']['category'] ?? null,
            $drawer->viewData('filterPanel')['counts']['category'] ?? null,
            'The drawer and the listing disagree about what is in the shop.'
        );
    }

    // ----------------------------------------------------------------- admin --

    public function test_the_admin_screen_lists_categories_and_the_built_in_listings_together(): void
    {
        Category::create(['name' => 'Kurtas', 'slug' => 'kurtas', 'is_active' => true]);

        $this->actingAsAdmin()
            ->get('/admin/categories')
            ->assertOk()
            ->assertSee('Kurtas')
            ->assertSee('Built-in listings')
            ->assertSee('Shop All')
            ->assertSee('Bestsellers');
    }

    public function test_a_subcategory_can_be_created_from_the_one_screen(): void
    {
        $parent = Category::create(['name' => 'Ethnic Wear', 'slug' => 'ethnic-wear', 'is_active' => true]);

        $this->actingAsAdmin()->post('/admin/categories', [
            'name' => 'Kurtas',
            'parent_id' => $parent->id,
            'is_active' => 1,
        ])->assertRedirect();

        $child = Category::where('name', 'Kurtas')->first();

        $this->assertNotNull($child);
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame(1, $child->level, 'A subcategory should sit one level below its parent.');
    }

    public function test_the_sidebar_no_longer_carries_two_collections_entries(): void
    {
        // Two entries, both called "Collections", pointing at unrelated screens
        // is what started all of this. Counted by href, not label: the label is
        // what was ambiguous, the link is what actually differed.
        $html = $this->actingAsAdmin()->get('/admin')->assertOk()->getContent();

        $this->assertStringNotContainsString(
            '/admin/collections',
            $html,
            'The sidebar still links the screen that was removed.'
        );

        $this->assertSame(
            1,
            substr_count($html, 'href="'.route('admin.categories.index').'"'),
            'The collections screen is linked from the sidebar more than once.'
        );
    }

    public function test_the_old_admin_collections_screen_no_longer_exists(): void
    {
        $this->actingAsAdmin()->get('/admin/collections')->assertNotFound();
    }

    public function test_a_system_row_is_not_served_as_a_category_page(): void
    {
        // Route binding can resolve a system row now - the admin has to be able
        // to edit one - so the storefront category page has to refuse it itself,
        // or /category/bestsellers-picks would be a second URL for a listing
        // that already lives at /collection/bestsellers-picks.
        $row = Category::system()->where('handle', 'bestsellers')->firstOrFail();

        $this->get('/category/'.$row->slug)->assertNotFound();
        $this->get('/collection/'.$row->slug)->assertOk();
    }

    public function test_an_admin_can_still_edit_a_built_in_listing(): void
    {
        // The global scope hides system rows from ordinary queries, which also
        // hid them from route binding - the admin screen silently 404d on every
        // built-in row until resolveRouteBinding opted out of the scope.
        $row = Category::system()->where('handle', 'new_in')->firstOrFail();

        $this->actingAsAdmin()->put(route('admin.categories.update', $row), [
            'name' => 'Renamed By Hand',
            'slug' => 'renamed-by-hand',
            'is_active' => 0,
            'position' => 5,
        ])->assertSessionHasNoErrors();

        $row->refresh();

        $this->assertSame('New In', $row->name, 'A built-in was renamed out from under the page it drives.');
        $this->assertSame(5, $row->position, 'The rest of the row stopped being editable.');
        $this->assertFalse($row->is_active);
    }

    public function test_position_saves_the_same_way_for_a_plain_category(): void
    {
        // A control for the built-in row's own editability check: if position
        // does not stick here either, that is how this screen has always
        // behaved (it has a separate reorder endpoint) and not something the
        // merge changed.
        $category = Category::create(['name' => 'Kurtas', 'slug' => 'kurtas', 'is_active' => true, 'position' => 0]);

        $this->actingAsAdmin()->put(route('admin.categories.update', $category), [
            'name' => 'Kurtas',
            'slug' => 'kurtas',
            'is_active' => 1,
            'position' => 5,
        ])->assertSessionHasNoErrors();

        $this->assertSame(5, $category->refresh()->position);
    }

    private function product(string $name, string $slug, Category $category): Product
    {
        $product = Product::create([
            'name' => $name,
            'slug' => $slug,
            'sku' => strtoupper($slug),
            'price' => 500,
            'mrp' => 700,
            'stock_quantity' => 5,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $product->categories()->syncWithoutDetaching([$category->id]);

        return $product;
    }
}
