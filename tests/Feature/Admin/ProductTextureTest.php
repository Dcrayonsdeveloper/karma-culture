<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\ShopFilterCatalogue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Texture is entered once, on the product, and nowhere else.
 *
 * That is the whole point of the attribute: an admin types "Matte" into the
 * product they are already editing, and it becomes a filter on the shop, a
 * picker on the product page and a line on the order without being retyped
 * anywhere. This file covers the entry end of that - the form renders, saves,
 * reloads and can be emptied - and the derivation end is covered next door in
 * ShopFilterCatalogueTest.
 */
class ProductTextureTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Men', 'slug' => 'men', 'is_active' => true]);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Admin', 'role' => 'admin']);
        Admin::create(['user_id' => $user->id, 'role' => 'super_admin', 'is_active' => true]);

        return $user;
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Oxford Shirt',
            'slug' => 'oxford-shirt',
            'sku' => 'OXFORD',
            'description' => 'A shirt.',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 10,
            'category_id' => $this->category->id,
            'is_active' => 1,
            // Both are required on the product form, so a payload without them
            // is refused before it can say anything about textures.
            'variants' => [
                ['name' => 'M', 'price' => 999, 'stock_quantity' => 5, 'sku' => '', 'is_active' => 1],
            ],
            'colours' => [['name' => 'Black', 'hex' => '#000000']],
            'textures' => ['Matte', 'Glossy'],
        ], $overrides);
    }

    public function test_the_create_form_offers_a_textures_box(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('Textures')
            ->assertSee('function kkTextures()', false)
            ->assertSee("'textures[' + i + ']'", false);
    }

    public function test_a_product_can_be_created_with_textures(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $product = Product::where('sku', 'OXFORD')->firstOrFail();

        $this->assertSame(['Matte', 'Glossy'], $product->attributes['Textures']);
        // The colours list beside it is untouched: the two are written into the
        // same JSON blob on the same save, so one must not wipe the other.
        $this->assertSame('Black', $product->attributes['Colours'][0]['name']);
    }

    public function test_the_new_textures_are_on_the_shop_filters_at_once(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertEqualsCanonicalizing(
            ['Matte', 'Glossy'],
            ShopFilterCatalogue::values('texture')->pluck('label')->all(),
        );
    }

    public function test_one_texture_typed_twice_is_stored_once(): void
    {
        // "Matte" and "matte" are one texture told twice; stored as two they
        // would open two rows on the shop filter rail.
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.store'), $this->payload(['textures' => ['Matte', 'matte', ' MATTE ']]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(['Matte'], Product::where('sku', 'OXFORD')->firstOrFail()->attributes['Textures']);
    }

    public function test_blank_rows_are_dropped_and_the_list_stays_a_json_array(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.store'), $this->payload(['textures' => ['Matte', '', 'Glossy']]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $product = Product::where('sku', 'OXFORD')->firstOrFail();

        $this->assertSame(['Matte', 'Glossy'], $product->attributes['Textures']);
        // A gap in the keys would encode the list as a JSON object, and every
        // reader downstream expects an array.
        $this->assertStringContainsString('"Textures":["Matte","Glossy"]', $product->getRawOriginal('attributes'));
    }

    public function test_the_edit_form_loads_the_textures_the_product_already_has(): void
    {
        $product = Product::create([
            'name' => 'Oxford Shirt',
            'slug' => 'oxford-shirt',
            'sku' => 'OXFORD',
            'description' => 'A shirt.',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 10,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
            'attributes' => ['Textures' => ['Matte', 'Glossy']],
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Matte')
            ->assertSee('Glossy');
    }

    public function test_a_texture_can_be_changed_on_edit(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.store'), $this->payload(['textures' => ['Matte']]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $product = Product::where('sku', 'OXFORD')->firstOrFail();

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.products.update', $product), $this->payload(['textures' => ['Smooth']]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(['Smooth'], $product->fresh()->attributes['Textures']);
        $this->assertSame(['Smooth'], ShopFilterCatalogue::values('texture')->pluck('label')->all());
    }

    public function test_removing_every_row_empties_the_list(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $product = Product::where('sku', 'OXFORD')->firstOrFail();

        // Removing the last row posts no `textures` key at all, which is the
        // only way an admin can clear the list.
        $payload = $this->payload();
        unset($payload['textures']);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.products.update', $product), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertArrayNotHasKey('Textures', $product->fresh()->attributes ?? []);
        $this->assertSame([], ShopFilterCatalogue::values('texture')->all());
    }

    public function test_a_product_saved_with_no_textures_at_all_is_fine(): void
    {
        $payload = $this->payload();
        unset($payload['textures']);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $product = Product::where('sku', 'OXFORD')->firstOrFail();

        $this->assertArrayNotHasKey('Textures', $product->attributes ?? []);
        $this->get(route('product.show', $product->slug))->assertOk();
    }

    public function test_markup_in_a_texture_is_refused(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.store'), $this->payload(['textures' => ['<script>alert(1)</script>']]))
            ->assertSessionHasErrors('textures.0');

        $this->assertSame(0, Product::query()->count());
    }

    public function test_a_bulk_deactivate_takes_the_textures_off_the_shop(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $product = Product::where('sku', 'OXFORD')->firstOrFail();

        $this->assertNotSame([], ShopFilterCatalogue::values('texture')->all());

        // A bulk action is a query-builder write and fires no model events, so
        // the filter cache has to be retired by hand or the rails keep offering
        // a product nobody can buy.
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.products.bulk-action'), [
                'action' => 'deactivate',
                // The screen posts the checked ids as a JSON string.
                'ids' => json_encode([$product->id]),
            ])
            ->assertRedirect();

        $this->assertSame([], ShopFilterCatalogue::values('texture')->all());
    }
}
