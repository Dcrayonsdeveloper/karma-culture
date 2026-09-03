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
 * New In, Bestsellers and Introductory Offer are now pickable from the product
 * form, without ceasing to fill themselves.
 *
 * They were computed - newest by date, best by sales count, whatever is
 * discounted - so there was no list to add a product to and the product form
 * could not offer them at all. They exist as system collections now, which is
 * what puts them in the tick list beside every other shelf.
 *
 * Nothing ticked means nothing changes: the page keeps computing. Tick one
 * product and that page shows the picks instead. Untick them all and it goes
 * back. The switch is per page.
 */
class SystemCollectionsTest extends TestCase
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

    private function makeProduct(string $sku, string $name, array $overrides = []): Product
    {
        return Product::create(array_merge([
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
        ], $overrides));
    }

    public function test_the_three_built_ins_exist_as_collections(): void
    {
        foreach (['new_in' => 'New In', 'bestsellers' => 'Bestsellers', 'deals' => 'Introductory Offer'] as $handle => $name) {
            $collection = ProductCollection::where('handle', $handle)->first();

            $this->assertNotNull($collection, "The {$name} collection is missing.");
            $this->assertSame($name, $collection->name);
            $this->assertTrue($collection->is_system);
        }
    }

    /**
     * The whole point of the request: they show up where products are ticked.
     */
    public function test_the_product_form_offers_them(): void
    {
        $product = $this->makeProduct('P-1', 'Linen Shirt');

        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.products.edit', $product))->assertOk()->getContent();

        foreach (['New In', 'Bestsellers', 'Introductory Offer'] as $name) {
            $this->assertStringContainsString($name, $html, "The product form does not offer {$name}.");
        }
    }

    public function test_new_in_keeps_computing_while_nothing_is_ticked(): void
    {
        $this->makeProduct('P-1', 'Linen Shirt');
        $this->makeProduct('P-2', 'Woollen Coat');

        $html = $this->get(route('new-arrivals'))->assertOk()->getContent();

        $this->assertStringContainsString('Linen Shirt', $html);
        $this->assertStringContainsString('Woollen Coat', $html);
    }

    public function test_ticking_a_product_makes_new_in_show_the_picks_only(): void
    {
        $picked = $this->makeProduct('P-1', 'Linen Shirt');
        $this->makeProduct('P-2', 'Woollen Coat');

        ProductCollection::where('handle', 'new_in')->firstOrFail()->products()->sync([$picked->id]);

        $html = $this->get(route('new-arrivals'))->assertOk()->getContent();

        $this->assertStringContainsString('Linen Shirt', $html);
        $this->assertStringNotContainsString('Woollen Coat', $html, 'New In is still listing the whole catalogue.');
    }

    public function test_unticking_everything_puts_new_in_back_to_computing(): void
    {
        $picked = $this->makeProduct('P-1', 'Linen Shirt');
        $this->makeProduct('P-2', 'Woollen Coat');

        $collection = ProductCollection::where('handle', 'new_in')->firstOrFail();
        $collection->products()->sync([$picked->id]);
        $collection->products()->sync([]);

        $html = $this->get(route('new-arrivals'))->assertOk()->getContent();

        $this->assertStringContainsString('Woollen Coat', $html, 'The page did not go back to filling itself.');
    }

    public function test_bestsellers_takes_its_picks_too(): void
    {
        $picked = $this->makeProduct('P-1', 'Linen Shirt');
        $this->makeProduct('P-2', 'Woollen Coat');

        ProductCollection::where('handle', 'bestsellers')->firstOrFail()->products()->sync([$picked->id]);

        $html = $this->get(route('bestsellers'))->assertOk()->getContent();

        $this->assertStringContainsString('Linen Shirt', $html);
        $this->assertStringNotContainsString('Woollen Coat', $html);
    }

    /**
     * Introductory Offer normally finds the reductions itself, so a picked
     * product at full price is the proof the override really took over.
     */
    public function test_introductory_offer_lists_a_pick_even_at_full_price(): void
    {
        $fullPrice = $this->makeProduct('P-1', 'Linen Shirt', ['price' => 999, 'mrp' => 999]);
        $this->makeProduct('P-2', 'Woollen Coat', ['price' => 500, 'mrp' => 999]);

        ProductCollection::where('handle', 'deals')->firstOrFail()->products()->sync([$fullPrice->id]);

        $html = $this->get(route('deals'))->assertOk()->getContent();

        $this->assertStringContainsString('Linen Shirt', $html);
        $this->assertStringNotContainsString('Woollen Coat', $html);
    }

    /**
     * One page at a time: picking for New In must not touch Bestsellers.
     */
    public function test_the_override_is_per_page(): void
    {
        $picked = $this->makeProduct('P-1', 'Linen Shirt');
        $this->makeProduct('P-2', 'Woollen Coat');

        ProductCollection::where('handle', 'new_in')->firstOrFail()->products()->sync([$picked->id]);

        $html = $this->get(route('bestsellers'))->assertOk()->getContent();

        $this->assertStringContainsString('Woollen Coat', $html, 'Bestsellers stopped computing because New In was picked for.');
    }

    public function test_a_built_in_cannot_be_deleted(): void
    {
        $collection = ProductCollection::where('handle', 'new_in')->firstOrFail();

        $this->actingAs($this->adminUser, 'admin')
            ->delete(route('admin.collections.destroy', $collection));

        $this->assertDatabaseHas('collections', ['handle' => 'new_in']);
    }

    /**
     * The header names these pages and the product form names these rows, so
     * the name and URL stay put - but the admin still owns the rest.
     */
    public function test_a_built_in_keeps_its_name_but_stays_otherwise_editable(): void
    {
        $collection = ProductCollection::where('handle', 'new_in')->firstOrFail();

        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.collections.update', $collection), [
                'name' => 'Renamed By Hand',
                'slug' => 'renamed-by-hand',
                'is_active' => 1,
                'show_in_header' => 1,
                'position' => 5,
            ])
            ->assertSessionHasNoErrors();

        $collection->refresh();

        $this->assertSame('New In', $collection->name, 'A built-in was renamed out from under the header.');
        $this->assertSame('new-in', $collection->slug);
        $this->assertTrue($collection->show_in_header, 'The rest of the row stopped being editable.');
        $this->assertSame(5, $collection->position);
    }

    /**
     * A product taken off sale must not go on being listed by a page that only
     * ever shows live products.
     */
    public function test_a_deactivated_pick_drops_out(): void
    {
        $picked = $this->makeProduct('P-1', 'Linen Shirt');
        ProductCollection::where('handle', 'new_in')->firstOrFail()->products()->sync([$picked->id]);

        $picked->update(['is_active' => false]);

        $html = $this->get(route('new-arrivals'))->assertOk()->getContent();

        // On the link, not the name: the header cycles a search placeholder
        // ("Search for Linen Shirts...") that contains the word too, so a
        // name match reports a leak that is not there.
        $this->assertStringNotContainsString('/product/'.$picked->slug, $html);
        $this->assertStringContainsString('No new arrivals', $html, 'The override should hold and simply show nothing.');
    }
}
