<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Collections: hand-picked groups of products with their own page and an
 * optional header link.
 *
 * The header's four built-in listings are computed from the catalogue - New In
 * by date, Bestsellers by sales, Introductory Offer by discount - so there was
 * no list a product could be added to, and the product form could not offer
 * them. Collections are the assignable kind alongside them.
 */
class CollectionsTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->category = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'is_active' => true]);
    }

    /** @param array<string, mixed> $overrides */
    private function collectionPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Summer Picks',
            'slug' => '',
            'description' => 'Light things for hot days',
            'is_active' => 1,
            'show_in_header' => 1,
            'position' => 0,
        ], $overrides);
    }

    private function makeProduct(string $sku = 'C-1', string $name = 'Linen Shirt'): Product
    {
        return Product::create([
            'name' => $name,
            'slug' => Str()->slug($name.' '.$sku),
            'description' => 'x',
            'sku' => $sku,
            'price' => 999,
            'mrp' => 999,
            'stock_quantity' => 5,
            'category_id' => $this->category->id,
            'is_active' => true,
            'status' => 'approved',
        ]);
    }

    /**
     * The three admin screens render at all.
     *
     * Every other test here posts to the controller, which never touches the
     * blades - so a broken view (an unresolvable helper, a missing variable)
     * would have shipped green.
     */
    public function test_the_collection_screens_render(): void
    {
        $collection = ProductCollection::create([
            'name' => 'Summer Picks', 'slug' => 'summer-picks', 'is_active' => true,
        ]);
        $collection->products()->sync([$this->makeProduct()->id]);

        $screens = [
            'index' => route('admin.collections.index'),
            'create' => route('admin.collections.create'),
            'edit' => route('admin.collections.edit', $collection),
        ];

        foreach ($screens as $which => $url) {
            $this->actingAs($this->adminUser, 'admin')->get($url)->assertOk();
        }
    }

    public function test_an_admin_can_create_a_collection(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.collections.store'), $this->collectionPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.collections.index'));

        $collection = ProductCollection::firstOrFail();

        $this->assertSame('Summer Picks', $collection->name);
        $this->assertSame('summer-picks', $collection->slug, 'The URL should be derived from the name.');
        $this->assertTrue($collection->show_in_header);
    }

    /**
     * The built-in listings own their URLs. A collection at /collection/deals
     * is legal, but one CALLED deals reads as the built-in page and is not it.
     */
    public function test_a_reserved_name_is_refused(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.collections.store'), $this->collectionPayload(['slug' => 'bestsellers']))
            ->assertSessionHasErrors('slug');

        $this->assertSame(0, ProductCollection::count());
    }

    /**
     * The typed slug is validated, but a slug DERIVED from the name never went
     * through those rules - so the same clash has to be caught after derivation.
     */
    public function test_a_derived_slug_cannot_duplicate_an_existing_one(): void
    {
        $this->actingAs($this->adminUser, 'admin')->post(route('admin.collections.store'), $this->collectionPayload());

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.collections.store'), $this->collectionPayload())
            ->assertSessionHasErrors('name');

        $this->assertSame(1, ProductCollection::count());
    }

    public function test_a_product_can_be_ticked_into_a_collection(): void
    {
        $this->actingAs($this->adminUser, 'admin')->post(route('admin.collections.store'), $this->collectionPayload());
        $collection = ProductCollection::firstOrFail();
        $product = $this->makeProduct();

        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => 'x',
                'sku' => $product->sku,
                'price' => 999,
                'stock_quantity' => 5,
                'category_id' => $this->category->id,
                'is_active' => 1,
                'collection_ids' => [$collection->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([$product->id], $collection->products()->pluck('products.id')->all());
    }

    public function test_the_collection_page_lists_its_products_and_nothing_else(): void
    {
        $collection = ProductCollection::create([
            'name' => 'Summer Picks', 'slug' => 'summer-picks', 'is_active' => true,
        ]);

        $inside = $this->makeProduct('IN-1', 'Linen Shirt');
        $this->makeProduct('OUT-1', 'Woollen Coat');

        $collection->products()->sync([$inside->id]);

        $html = $this->get(route('collection.show', $collection))->assertOk()->getContent();

        $this->assertStringContainsString('Linen Shirt', $html);
        $this->assertStringNotContainsString('Woollen Coat', $html);
        $this->assertStringContainsString('Summer Picks', $html, 'The page should be headed with the collection name.');
    }

    /**
     * An empty collection must show an empty page, not the entire shop - the
     * failure mode of a whereIn that is handed an empty list.
     */
    public function test_an_empty_collection_does_not_fall_back_to_the_whole_shop(): void
    {
        $collection = ProductCollection::create([
            'name' => 'Empty Edit', 'slug' => 'empty-edit', 'is_active' => true,
        ]);

        $this->makeProduct('OUT-2', 'Woollen Coat');

        $html = $this->get(route('collection.show', $collection))->assertOk()->getContent();

        $this->assertStringNotContainsString('Woollen Coat', $html);
    }

    public function test_a_hidden_collection_is_not_browsable(): void
    {
        $collection = ProductCollection::create([
            'name' => 'Draft Edit', 'slug' => 'draft-edit', 'is_active' => false,
        ]);

        $this->get(route('collection.show', $collection))->assertNotFound();
    }

    public function test_the_header_shows_collections_that_asked_to_be_there(): void
    {
        ProductCollection::create(['name' => 'Summer Picks', 'slug' => 'summer-picks', 'is_active' => true, 'show_in_header' => true]);
        ProductCollection::create(['name' => 'Quiet Edit', 'slug' => 'quiet-edit', 'is_active' => true, 'show_in_header' => false]);
        ProductCollection::create(['name' => 'Off Edit', 'slug' => 'off-edit', 'is_active' => false, 'show_in_header' => true]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Summer Picks', $html);
        $this->assertStringNotContainsString('Quiet Edit', $html, 'A collection that opted out of the header is in it.');
        $this->assertStringNotContainsString('Off Edit', $html, 'A deactivated collection is still linked.');
    }

    /**
     * Order is the admin's, not the database's.
     */
    public function test_the_header_respects_the_menu_order(): void
    {
        ProductCollection::create(['name' => 'Second', 'slug' => 'second', 'is_active' => true, 'show_in_header' => true, 'position' => 2]);
        ProductCollection::create(['name' => 'First', 'slug' => 'first', 'is_active' => true, 'show_in_header' => true, 'position' => 1]);

        $this->assertSame(['First', 'Second'], ProductCollection::forHeader()->pluck('name')->all());
    }

    public function test_deleting_a_collection_leaves_its_products_alone(): void
    {
        $collection = ProductCollection::create(['name' => 'Summer Picks', 'slug' => 'summer-picks', 'is_active' => true]);
        $product = $this->makeProduct();
        $collection->products()->sync([$product->id]);

        $this->actingAs($this->adminUser, 'admin')
            ->delete(route('admin.collections.destroy', $collection))
            ->assertRedirect(route('admin.collections.index'));

        $this->assertDatabaseCount('collection_product', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_both_product_forms_offer_the_collection_list(): void
    {
        ProductCollection::create(['name' => 'Summer Picks', 'slug' => 'summer-picks', 'is_active' => true]);
        $product = $this->makeProduct();

        $forms = [
            'create' => route('admin.products.create'),
            'edit' => route('admin.products.edit', $product),
        ];

        foreach ($forms as $which => $url) {
            $html = $this->actingAs($this->adminUser, 'admin')->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('name="collection_ids[]"', $html, "The {$which} form has no collection list.");
            $this->assertStringContainsString('Summer Picks', $html);
        }
    }

    /**
     * The header is a short, ordered bar of shopping destinations; a CMS page
     * dropped in pushes those out. Policy pages belong in the footer columns.
     */
    public function test_a_page_can_no_longer_be_placed_in_the_header(): void
    {
        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.pages.create'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Header menu', $html);
        $this->assertStringContainsString('Footer - Policies', $html);

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.pages.store'), [
                'title' => 'Shipping Policy',
                'slug' => 'shipping-policy',
                'content' => '<p>x</p>',
                'is_published' => 1,
                'nav_location' => 'header',
            ])
            ->assertSessionHasErrors('nav_location');
    }
}
