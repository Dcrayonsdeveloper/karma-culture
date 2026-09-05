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
 * Saving a product blew up with a 500 when a size row was left without a SKU.
 *
 * product_variants.sku is UNIQUE across the whole table, and the controller
 * derives one from "<product sku>-<size>" when the field is blank. That string
 * is not unique on its own, and nothing checked it: the uniqueness rule on
 * variants.*.sku is a closure, and `nullable` drops a closure rule for an empty
 * value, so the derived string went straight to MySQL. Seen in production on
 * 2026-09-02 saving /admin/products/37 - "Duplicate entry '48-S' for key
 * product_variants_sku_unique" - which cost the admin the whole form.
 */
class ProductVariantSkuTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Variant',
            'last_name' => 'Admin',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Variant Shirts',
            'slug' => 'variant-shirts',
            'is_active' => true,
        ]);

        $this->product = $this->makeProduct('Variant Test Shirt', 'variant-test-shirt', 'VTS');
    }

    public function test_a_blank_sku_does_not_collide_with_another_products_size(): void
    {
        // The production case: another product whose SKU slugs down to the same
        // base already holds the value this save is about to derive.
        $other = $this->makeProduct('Rival Shirt', 'rival-shirt', 'VTS.');
        $this->makeVariant($other, 'S', 'VTS-S');

        $this->putProduct([
            'variants' => [
                ['name' => 'S', 'sku' => '', 'price' => 799, 'stock_quantity' => 10, 'is_active' => 1],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(1, $this->product->variants()->count(), 'The size was not saved at all.');
        $this->assertSame(
            'VTS-S-2',
            $this->product->variants()->first()->sku,
            'A derived SKU has to step around a value that is already taken.'
        );
        $this->assertSame('VTS-S', $other->variants()->first()->sku, "The other product's size was rewritten.");
    }

    public function test_a_blank_sku_does_not_collide_with_a_size_the_product_already_has(): void
    {
        $this->makeVariant($this->product, 'S', 'VTS-S');

        // A second "S" row, added with the "Add size" button and left blank, so
        // it derives exactly what the first row already holds.
        $this->putProduct([
            'variants' => [
                ['name' => 'S', 'sku' => '', 'price' => 899, 'stock_quantity' => 7, 'is_active' => 1],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $skus = $this->product->variants()->pluck('sku')->all();
        $this->assertCount(2, $skus, 'The new size was not created.');
        $this->assertSame($skus, array_unique($skus), 'Two sizes ended up sharing a SKU.');
        $this->assertContains('VTS-S-2', $skus);
    }

    public function test_two_blank_rows_in_one_save_get_different_skus(): void
    {
        $this->putProduct([
            'variants' => [
                ['name' => 'M', 'sku' => '', 'price' => 799, 'stock_quantity' => 3, 'is_active' => 1],
                ['name' => 'M', 'sku' => '', 'price' => 899, 'stock_quantity' => 4, 'is_active' => 1],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $skus = $this->product->variants()->pluck('sku')->all();
        $this->assertCount(2, $skus);
        $this->assertSame($skus, array_unique($skus), 'Both rows derived the same SKU.');
    }

    public function test_a_derived_sku_avoids_one_a_later_row_spells_out(): void
    {
        // Row order matters: the blank row is written first, so the value it
        // has to avoid is not in the table yet when it is derived.
        $this->putProduct([
            'variants' => [
                ['name' => 'L', 'sku' => '', 'price' => 799, 'stock_quantity' => 3, 'is_active' => 1],
                ['name' => 'XL', 'sku' => 'VTS-L', 'price' => 899, 'stock_quantity' => 4, 'is_active' => 1],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $skus = $this->product->variants()->pluck('sku')->all();
        $this->assertCount(2, $skus);
        $this->assertSame($skus, array_unique($skus), 'The derived SKU took the value the other row typed.');
        $this->assertContains('VTS-L', $skus);
    }

    public function test_a_sku_that_was_typed_in_is_still_reported_as_a_field_error(): void
    {
        $other = $this->makeProduct('Typed Rival', 'typed-rival', 'TR');
        $this->makeVariant($other, 'S', 'TAKEN-1');

        // Deriving around a clash is for values nobody chose. A SKU the admin
        // typed has to come back as a message rather than be quietly renamed.
        $this->putProduct([
            'variants' => [
                ['name' => 'S', 'sku' => 'TAKEN-1', 'price' => 799, 'stock_quantity' => 3, 'is_active' => 1],
            ],
        ])->assertSessionHasErrors('variants.0.sku');

        $this->assertSame(0, $this->product->variants()->count());
    }

    public function test_a_derived_sku_stays_inside_the_column(): void
    {
        // 46 characters, so "<sku>-S" is 48 and "<sku>-S-2" would be 50 - the
        // suffix only fits if the base is trimmed to make room for it.
        $longSku = str_repeat('L', 46);
        $long = $this->makeProduct('Long Sku Shirt', 'long-sku-shirt', $longSku);
        $this->makeVariant($long, 'S', strtoupper($longSku) . '-S');

        $this->putProduct([
            'variants' => [
                ['name' => 'S', 'sku' => '', 'price' => 799, 'stock_quantity' => 3, 'is_active' => 1],
            ],
        ], $long)->assertSessionHasNoErrors()->assertRedirect();

        $skus = $long->variants()->pluck('sku')->all();
        $this->assertCount(2, $skus);
        $this->assertSame($skus, array_unique($skus));

        foreach ($skus as $sku) {
            $this->assertLessThanOrEqual(50, strlen($sku), 'varchar(50) would truncate this and reintroduce the clash.');
        }
    }

    private function makeProduct(string $name, string $slug, string $sku): Product
    {
        return Product::create([
            'name' => $name,
            'slug' => $slug,
            'sku' => $sku,
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 5,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    private function makeVariant(Product $product, string $size, string $sku): ProductVariant
    {
        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => $size,
            'sku' => $sku,
            'price' => 799,
            'mrp' => 799,
            'stock_quantity' => 4,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function putProduct(array $overrides = [], ?Product $product = null): TestResponse
    {
        $product ??= $this->product;

        return $this->actingAs($this->adminUser, 'admin')->put(
            route('admin.products.update', $product),
            array_merge([
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'description' => 'A shirt used to check how blank size SKUs are filled in.',
                'price' => 999,
                'mrp' => 1299,
                'stock_quantity' => 5,
                'category_id' => $this->category->id,
                // The edit form requires a colour, so every save has to carry the
                // list. Sizes come from each test's own `variants`.
                'colours' => [
                    ['name' => 'Navy', 'hex' => '#001f3f'],
                ],
            ], $overrides)
        );
    }
}
