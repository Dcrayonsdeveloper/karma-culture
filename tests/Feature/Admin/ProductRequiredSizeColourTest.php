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
 * Sizes and colours were optional on both product forms, and a colour whose
 * swatch had never been touched was stored as #000000 - so the storefront
 * showed a black dot nobody had chosen, and a product could be published with
 * no size to pick and no colour at all.
 *
 * Both are now required on create and on edit, and an unpicked swatch is
 * refused instead of quietly filled in.
 */
class ProductRequiredSizeColourTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private Category $category;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Choice',
            'last_name' => 'Admin',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Choice Shirts',
            'slug' => 'choice-shirts',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Stocked Shirt',
            'slug' => 'stocked-shirt',
            'sku' => 'CHOICE-1',
            'description' => 'A shirt that already ships in one size and one colour.',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 5,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
            'attributes' => ['Colours' => [['name' => 'Navy', 'hex' => '#001f3f']]],
        ]);

        ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'M',
            'sku' => 'CHOICE-1-M',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
    }

    public function test_a_product_cannot_be_created_without_a_size(): void
    {
        $this->postProduct(['variants' => []])
            ->assertSessionHasErrors([
                'variants' => 'Add at least one size - a product with no sizes gives a customer nothing to add to their cart.',
            ]);

        $this->assertNull(Product::where('sku', 'NEW-1')->first(), 'A product with no sizes was saved.');
    }

    public function test_a_size_row_left_blank_does_not_count_as_a_size(): void
    {
        // The row the "Add size" button leaves behind. It is ignored by the
        // writer, so it must not be what satisfies the rule either.
        $this->postProduct([
            'variants' => [
                ['name' => '', 'price' => '', 'stock_quantity' => '', 'sku' => '', 'is_active' => 1],
            ],
        ])->assertSessionHasErrors('variants');

        $this->assertNull(Product::where('sku', 'NEW-1')->first());
    }

    public function test_a_product_cannot_be_created_without_a_colour(): void
    {
        $this->postProduct(['colours' => []])
            ->assertSessionHasErrors([
                'colours' => 'Add at least one colour - a product has to say which colours it comes in.',
            ]);

        $this->assertNull(Product::where('sku', 'NEW-1')->first(), 'A product with no colours was saved.');
    }

    public function test_a_colour_without_a_swatch_is_refused_rather_than_saved_as_black(): void
    {
        // What the form posts for a row whose swatch was never picked: a name
        // and no hex at all.
        $this->postProduct([
            'colours' => [
                ['name' => 'Navy'],
            ],
        ])->assertSessionHasErrors([
            'colours.0.hex' => 'Pick a swatch for every colour - one is no longer filled in for you.',
        ]);

        $this->assertNull(Product::where('sku', 'NEW-1')->first(), 'A colour was saved with a swatch nobody picked.');
    }

    public function test_a_colour_without_a_name_is_refused(): void
    {
        $this->postProduct([
            'colours' => [
                ['name' => '', 'hex' => '#001f3f'],
            ],
        ])->assertSessionHasErrors([
            'colours.0.name' => 'Name every colour, or remove the empty row.',
        ]);

        $this->assertNull(Product::where('sku', 'NEW-1')->first());
    }

    public function test_the_swatch_the_admin_picked_is_the_one_that_is_stored(): void
    {
        $this->postProduct([
            'colours' => [
                ['name' => 'Rust', 'hex' => '#b7410e'],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(
            [['name' => 'Rust', 'hex' => '#b7410e']],
            data_get(Product::where('sku', 'NEW-1')->firstOrFail()->attributes, 'Colours')
        );
    }

    public function test_the_edit_form_will_not_delete_the_last_size(): void
    {
        $variant = $this->product->variants()->firstOrFail();

        $this->putProduct([
            'variants' => [
                ['id' => $variant->id, 'delete' => 1, 'name' => 'M', 'price' => 999, 'stock_quantity' => 5, 'sku' => 'CHOICE-1-M', 'is_active' => 1],
            ],
        ])->assertSessionHasErrors('variants');

        $this->assertSame(1, $this->product->variants()->count(), 'The product was left with no size at all.');
    }

    public function test_the_edit_form_will_not_drop_the_colours(): void
    {
        $this->putProduct(['colours' => []])
            ->assertSessionHasErrors('colours');

        $this->product->refresh();
        $this->assertSame(
            [['name' => 'Navy', 'hex' => '#001f3f']],
            data_get($this->product->attributes, 'Colours'),
            "The product's colours were cleared by a save that should have been refused."
        );
    }

    public function test_an_existing_size_keeps_its_place_when_its_name_is_left_blank(): void
    {
        // syncVariants() keeps the stored name for a row that has an id, so such
        // a row is still a size and the save has to go through.
        $variant = $this->product->variants()->firstOrFail();

        $this->putProduct([
            'variants' => [
                ['id' => $variant->id, 'name' => '', 'price' => 999, 'stock_quantity' => 5, 'sku' => 'CHOICE-1-M', 'is_active' => 1],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('M', $this->product->variants()->firstOrFail()->name);
    }

    public function test_the_forms_no_longer_fill_a_swatch_in_for_the_admin(): void
    {
        foreach ([route('admin.products.create'), route('admin.products.edit', $this->product)] as $url) {
            $page = $this->actingAs($this->adminUser, 'admin')->get($url)->assertOk();

            $page->assertDontSee("hex: '#000000'", false);
            // A row is only "picked" once the admin has touched the swatch, and
            // an unpicked row posts no hex at all.
            $page->assertSee('picked: false', false);
            $page->assertSee("c.picked ? 'colours[' + i + '][hex]' : false", false);
        }
    }

    public function test_the_create_form_shows_why_the_save_was_refused(): void
    {
        // Both failures land under the bare `variants` and `colours` keys, which
        // the error blocks on the form used to filter out - so the save bounced
        // back and the page looked like it had simply ignored the button.
        $this->actingAs($this->adminUser, 'admin')
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->payload(['variants' => [], 'colours' => []]))
            ->assertRedirect(route('admin.products.create'));

        $page = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.products.create'))
            ->assertOk();

        // Save is at the bottom of a very long form and the bounce lands at the
        // top of it, so the reasons have to be up there too - against the fields
        // alone they were half a page below the fold and the page came back
        // looking like the button had done nothing.
        $page->assertSee('This product was not saved', false);
        $page->assertSee('Add at least one size', false);
        $page->assertSee('Add at least one colour', false);
    }

    public function test_a_fresh_create_form_carries_no_error_panel(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertDontSee('This product was not saved', false);
    }

    public function test_the_edit_form_shows_why_the_save_was_refused(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->from(route('admin.products.edit', $this->product))
            ->put(route('admin.products.update', $this->product), $this->payload([
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'sku' => $this->product->sku,
                'variants' => [],
                'colours' => [],
            ]))
            ->assertRedirect(route('admin.products.edit', $this->product));

        $page = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.products.edit', $this->product))
            ->assertOk();

        $page->assertSee('Your changes were not saved', false);
        $page->assertSee('Add at least one size', false);
        $page->assertSee('Add at least one colour', false);
    }

    public function test_both_forms_mark_sizes_and_colours_as_required(): void
    {
        foreach ([route('admin.products.create'), route('admin.products.edit', $this->product)] as $url) {
            $page = $this->actingAs($this->adminUser, 'admin')->get($url)->assertOk();

            $page->assertSee('form-label-required" style="color: #303030;">Sizes &amp; pricing', false);
            $page->assertSee('form-label-required" style="color: #303030;">Colours', false);
        }
    }

    public function test_a_failed_save_on_the_edit_form_gives_the_typed_sizes_back(): void
    {
        // The rules are stricter now, so a bounce is a normal part of editing a
        // product - it may not cost the admin the rows they had just typed.
        $this->actingAs($this->adminUser, 'admin')
            ->from(route('admin.products.edit', $this->product))
            ->put(route('admin.products.update', $this->product), $this->payload([
                'name' => '', // trips validation
                'variants' => [
                    ['id' => '', 'name' => 'XXL-46', 'price' => 1499, 'mrp' => 1799, 'stock_quantity' => 2, 'sku' => 'CHOICE-XXL', 'is_active' => 1],
                ],
                'colours' => [
                    ['name' => 'Rust', 'hex' => '#b7410e'],
                ],
            ]))
            ->assertSessionHasErrors('name');

        $page = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.products.edit', $this->product))
            ->assertOk();

        $page->assertSee('XXL-46', false);
        $page->assertSee('CHOICE-XXL', false);
        $page->assertSee('Rust', false);
        $page->assertSee('#b7410e', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Shirt',
            'slug' => 'new-shirt',
            'sku' => 'NEW-1',
            'description' => 'A shirt used to check that sizes and colours are insisted on.',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 5,
            'category_id' => $this->category->id,
            'variants' => [
                ['name' => 'M', 'price' => 999, 'stock_quantity' => 5, 'sku' => '', 'is_active' => 1],
            ],
            'colours' => [
                ['name' => 'Navy', 'hex' => '#001f3f'],
            ],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function postProduct(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->payload($overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function putProduct(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->adminUser, 'admin')->put(
            route('admin.products.update', $this->product),
            $this->payload(array_merge([
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'sku' => $this->product->sku,
            ], $overrides))
        );
    }
}
