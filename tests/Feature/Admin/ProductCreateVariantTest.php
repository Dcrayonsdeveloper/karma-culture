<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * "Add product" had no Sizes and no Colours section.
 *
 * Both cards existed only on the edit screen, and store() never looked at
 * `variants` even if something posted it - so the only way to give a product
 * the sizes it ships in was to save it once without them and immediately open
 * it again. A product created and then never re-opened reached the storefront
 * with no size picker and no colour swatches at all.
 *
 * The two forms now post the same fields and share one set of rules and one
 * writer, so a size added while creating a product is byte-identical to the
 * same size added a minute later on the edit screen.
 */
class ProductCreateVariantTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Create',
            'last_name' => 'Admin',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Create Shirts',
            'slug' => 'create-shirts',
            'is_active' => true,
        ]);
    }

    public function test_the_add_product_page_offers_a_sizes_and_a_colours_section(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.products.create'))
            ->assertOk();

        $response->assertSee('Sizes &amp; pricing', false);
        $response->assertSee('Colours', false);
        // The rows are built by Alpine, so the fields only exist once the
        // component is on the page - asserting on the markup alone would pass
        // against a card whose "Add size" button does nothing.
        $response->assertSee('function kkSizes()', false);
        $response->assertSee('function kkColours()', false);
        $response->assertSee("'variants[' + i + '][name]'", false);
        $response->assertSee("'colours[' + i + '][name]'", false);
    }

    public function test_a_product_can_be_created_with_its_sizes_in_one_request(): void
    {
        $this->postProduct([
            'variants' => [
                ['name' => 'M-40', 'price' => 1099, 'mrp' => 1499, 'stock_quantity' => 6, 'sku' => 'CRT-M40', 'measurements' => 'Chest 40in', 'is_active' => 1],
                ['name' => 'L-42', 'price' => 1199, 'mrp' => 1599, 'stock_quantity' => 3, 'sku' => 'CRT-L42', 'is_active' => 1],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $product = Product::where('sku', 'CRT-001')->firstOrFail();
        $this->assertSame(2, $product->variants()->count(), 'The sizes typed on the create form were dropped.');

        $medium = $product->variants()->where('name', 'M-40')->firstOrFail();
        $this->assertSame('CRT-M40', $medium->sku);
        $this->assertSame('1099.00', (string) $medium->price);
        $this->assertSame('1499.00', (string) $medium->mrp);
        $this->assertSame(6, $medium->stock_quantity);
        $this->assertTrue((bool) $medium->is_active);
        // Measurements ride in the variant attributes, the same shape the edit
        // form writes, so the storefront and the assistant read one format.
        $this->assertSame('Chest 40in', data_get($medium->attributes, 'measurements'));
    }

    public function test_colours_typed_on_the_create_form_are_saved(): void
    {
        $this->postProduct([
            'colours' => [
                ['name' => 'Navy', 'hex' => '#001f3f'],
                ['name' => 'Rust', 'hex' => '#b7410e'],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $product = Product::where('sku', 'CRT-001')->firstOrFail();

        $this->assertSame(
            [['name' => 'Navy', 'hex' => '#001f3f'], ['name' => 'Rust', 'hex' => '#b7410e']],
            data_get($product->attributes, 'Colours'),
            'The storefront reads swatches from attributes.Colours - any other shape renders nothing.'
        );
    }

    public function test_a_size_left_without_a_sku_gets_one_derived(): void
    {
        $this->postProduct([
            'variants' => [
                ['name' => 'S', 'price' => 899, 'stock_quantity' => 2, 'sku' => '', 'is_active' => 1],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $product = Product::where('sku', 'CRT-001')->firstOrFail();

        // sku is NOT NULL and unique, so a blank field cannot simply be stored.
        $this->assertSame('CRT-001-S', $product->variants()->first()->sku);
    }

    public function test_a_derived_sku_steps_around_one_another_product_already_holds(): void
    {
        $rival = Product::create([
            'name' => 'Rival Shirt',
            'slug' => 'rival-shirt',
            'sku' => 'CRT.001',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 5,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $rival->id,
            'name' => 'S',
            'sku' => 'CRT-001-S',
            'price' => 799,
            'mrp' => 799,
            'stock_quantity' => 4,
            'is_active' => true,
        ]);

        $this->postProduct([
            'variants' => [
                ['name' => 'S', 'price' => 899, 'stock_quantity' => 2, 'sku' => '', 'is_active' => 1],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $product = Product::where('sku', 'CRT-001')->firstOrFail();
        $this->assertSame('CRT-001-S-2', $product->variants()->first()->sku);
        $this->assertSame('CRT-001-S', $rival->variants()->first()->sku, "The other product's size was rewritten.");
    }

    public function test_a_sku_that_is_already_taken_is_reported_instead_of_500ing(): void
    {
        $rival = Product::create([
            'name' => 'Rival Shirt',
            'slug' => 'rival-shirt',
            'sku' => 'RIV-001',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 5,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $rival->id,
            'name' => 'S',
            'sku' => 'TAKEN-1',
            'price' => 799,
            'mrp' => 799,
            'stock_quantity' => 4,
            'is_active' => true,
        ]);

        $this->postProduct([
            'variants' => [
                ['name' => 'S', 'price' => 899, 'stock_quantity' => 2, 'sku' => 'TAKEN-1', 'is_active' => 1],
            ],
        ])->assertSessionHasErrors('variants.0.sku');

        // Validation runs before the insert, so nothing at all is written.
        $this->assertNull(Product::where('sku', 'CRT-001')->first());
    }

    public function test_a_blank_size_row_is_ignored_rather_than_saved_as_a_nameless_size(): void
    {
        $this->postProduct([
            'variants' => [
                ['name' => 'M', 'price' => 899, 'stock_quantity' => 2, 'sku' => '', 'is_active' => 1],
                ['name' => '', 'price' => '', 'stock_quantity' => '', 'sku' => '', 'is_active' => 1],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $product = Product::where('sku', 'CRT-001')->firstOrFail();
        $this->assertSame(1, $product->variants()->count(), 'An empty row left by the "Add size" button was saved.');
    }

    public function test_a_size_left_without_a_price_inherits_the_products(): void
    {
        $this->postProduct([
            'variants' => [
                ['name' => 'M', 'price' => '', 'mrp' => '', 'stock_quantity' => 4, 'sku' => '', 'is_active' => 1],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $variant = Product::where('sku', 'CRT-001')->firstOrFail()->variants()->firstOrFail();

        $this->assertSame('999.00', (string) $variant->price);
        $this->assertSame('1299.00', (string) $variant->mrp);
    }

    public function test_an_inherited_mrp_is_never_left_below_the_size_price(): void
    {
        // The row is dearer than the product, and its own MRP is blank - so the
        // inherited 1299 would sit below the 1500 charged and the storefront
        // would strike through a figure lower than the price.
        $this->postProduct([
            'variants' => [
                ['name' => 'XL', 'price' => 1500, 'mrp' => '', 'stock_quantity' => 4, 'sku' => '', 'is_active' => 1],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $variant = Product::where('sku', 'CRT-001')->firstOrFail()->variants()->firstOrFail();

        $this->assertSame('1500.00', (string) $variant->mrp);
    }

    public function test_a_size_mrp_typed_below_its_price_is_refused(): void
    {
        $this->postProduct([
            'variants' => [
                ['name' => 'M', 'price' => 1200, 'mrp' => 900, 'stock_quantity' => 4, 'sku' => '', 'is_active' => 1],
            ],
        ])->assertSessionHasErrors('variants.0.mrp');

        $this->assertNull(Product::where('sku', 'CRT-001')->first());
    }

    public function test_an_unticked_size_is_saved_inactive(): void
    {
        $this->postProduct([
            'variants' => [
                // The hidden 0 the checkbox pairs with, as the browser posts it
                // when the box is cleared.
                ['name' => 'M', 'price' => 899, 'stock_quantity' => 4, 'sku' => '', 'is_active' => 0],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $variant = Product::where('sku', 'CRT-001')->firstOrFail()->variants()->firstOrFail();
        $this->assertFalse((bool) $variant->is_active);
    }

    public function test_a_variant_id_posted_to_create_cannot_reach_another_products_size(): void
    {
        $rival = Product::create([
            'name' => 'Rival Shirt',
            'slug' => 'rival-shirt',
            'sku' => 'RIV-001',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 5,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
        $victim = ProductVariant::create([
            'product_id' => $rival->id,
            'name' => 'S',
            'sku' => 'RIV-S',
            'price' => 799,
            'mrp' => 799,
            'stock_quantity' => 4,
            'is_active' => true,
        ]);

        // create() has no `id` or `delete` rule, so neither survives validated()
        // and a crafted request can neither rewrite nor delete a stranger's row.
        $this->postProduct([
            'variants' => [
                ['id' => $victim->id, 'delete' => 1, 'name' => 'Hijacked', 'price' => 1, 'stock_quantity' => 0, 'sku' => '', 'is_active' => 1],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $victim->refresh();
        $this->assertSame('S', $victim->name, "Another product's size was rewritten from the create form.");
        $this->assertSame($rival->id, $victim->product_id);

        $product = Product::where('sku', 'CRT-001')->firstOrFail();
        $this->assertSame(1, $product->variants()->count());
        $this->assertSame('Hijacked', $product->variants()->first()->name, 'The row should have landed as a new size.');
    }

    public function test_a_failed_save_gives_the_sizes_and_colours_back(): void
    {
        // A rejected save used to hand back an empty table, so every size the
        // admin had typed had to be typed again.
        $this->actingAs($this->adminUser, 'admin')
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => '', // trips validation
                'description' => 'A shirt used to check the create form keeps its rows.',
                'sku' => 'CRT-001',
                'price' => 999,
                'mrp' => 1299,
                'stock_quantity' => 5,
                'category_id' => $this->category->id,
                'variants' => [
                    ['name' => 'M-40', 'price' => 1099, 'mrp' => 1499, 'stock_quantity' => 6, 'sku' => 'CRT-M40', 'is_active' => 1],
                ],
                'colours' => [
                    ['name' => 'Navy', 'hex' => '#001f3f'],
                ],
            ])
            ->assertSessionHasErrors('name');

        $page = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.products.create'))
            ->assertOk();

        $page->assertSee('M-40', false);
        $page->assertSee('CRT-M40', false);
        $page->assertSee('Navy', false);
        $page->assertSee('#001f3f', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function postProduct(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->adminUser, 'admin')->post(
            route('admin.products.store'),
            array_merge([
                'name' => 'Create Test Shirt',
                'slug' => 'create-test-shirt',
                'sku' => 'CRT-001',
                'description' => 'A shirt used to check sizes and colours survive the create form.',
                'price' => 999,
                'mrp' => 1299,
                'stock_quantity' => 5,
                'category_id' => $this->category->id,
            ], $overrides)
        );
    }
}
