<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A product could only ever be on one shelf.
 *
 * products.category_id is a single column, so a unisex shirt was listed under
 * MEN > Shirts or WOMEN > Shirts and never both - whoever added it had to pick
 * one and lose the other. The column stays as the PRIMARY category, because
 * the breadcrumb, the canonical URL, coupon scoping and the reports all want
 * one canonical answer; the category_product pivot answers the different
 * question the listings ask, which is "should this product appear here".
 */
class ProductMultiCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Category $menParent;
    private Category $menShirts;
    private Category $womenParent;
    private Category $womenShirts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->menParent = Category::create(['name' => 'Men', 'slug' => 'men', 'is_active' => true]);
        $this->menShirts = Category::create([
            'name' => 'Men Shirts', 'slug' => 'men-shirts', 'is_active' => true,
            'parent_id' => $this->menParent->id,
        ]);
        $this->womenParent = Category::create(['name' => 'Women', 'slug' => 'women', 'is_active' => true]);
        $this->womenShirts = Category::create([
            'name' => 'Women Shirts', 'slug' => 'women-shirts', 'is_active' => true,
            'parent_id' => $this->womenParent->id,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Unisex Oxford Shirt',
            'slug' => 'unisex-oxford-shirt',
            'description' => '<p>A shirt for everyone.</p>',
            'sku' => 'UNI-1',
            'price' => 1499,
            'stock_quantity' => 10,
            'category_id' => $this->menShirts->id,
            'is_active' => 1,
        ], $overrides);
    }

    private function makeProduct(Category $primary, string $sku = 'P-1'): Product
    {
        return Product::create([
            'name' => 'Product '.$sku,
            'slug' => Str()->slug('product '.$sku),
            'description' => 'x',
            'sku' => $sku,
            'price' => 999,
            'mrp' => 999,
            'stock_quantity' => 5,
            'category_id' => $primary->id,
            'is_active' => true,
            'status' => 'approved',
        ]);
    }

    public function test_a_product_can_be_saved_onto_several_shelves(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->payload([
                'extra_category_ids' => [$this->womenShirts->id],
            ]))
            ->assertSessionHasNoErrors();

        $product = Product::firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$this->menShirts->id, $this->womenShirts->id],
            $product->categories()->pluck('categories.id')->all()
        );
    }

    /**
     * The filed category is what the breadcrumb and the canonical URL read, so
     * it has to be on the shelf list too - a listing that omitted it would
     * contradict the product's own page.
     */
    public function test_the_primary_category_is_always_on_the_list(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [$this->menShirts->id],
            Product::firstOrFail()->categories()->pluck('categories.id')->all()
        );
    }

    /**
     * Every other write path - imports, seeders, console commands, the API -
     * bypasses the product form, and a product with an empty pivot would drop
     * out of every listing on the site.
     */
    public function test_a_product_created_outside_the_form_still_lands_on_its_shelf(): void
    {
        $product = $this->makeProduct($this->menShirts, 'IMPORTED-1');

        $this->assertSame(
            [$this->menShirts->id],
            $product->categories()->pluck('categories.id')->all()
        );
    }

    public function test_unticking_a_shelf_removes_the_product_from_it(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->payload([
                'extra_category_ids' => [$this->womenShirts->id],
            ]));

        $product = Product::firstOrFail();

        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.products.update', $product), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [$this->menShirts->id],
            $product->categories()->pluck('categories.id')->all()
        );
    }

    /**
     * The listing is the whole point: a shelved product has to come back from
     * the category page, not merely exist in the pivot.
     */
    public function test_the_second_shelf_lists_the_product(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->payload([
                'extra_category_ids' => [$this->womenShirts->id],
            ]));

        $html = $this->get(route('category.show', $this->womenShirts))->assertOk()->getContent();

        $this->assertStringContainsString('Unisex Oxford Shirt', $html);
    }

    /**
     * Parents list their whole subtree, so a product shelved on a child shows
     * on the parent page as well - under both parents, in this case.
     */
    public function test_both_parent_pages_list_the_product(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->payload([
                'extra_category_ids' => [$this->womenShirts->id],
            ]));

        foreach ([$this->menParent, $this->womenParent] as $parent) {
            $html = $this->get(route('category.show', $parent))->assertOk()->getContent();
            $this->assertStringContainsString('Unisex Oxford Shirt', $html, "Missing from the {$parent->name} page.");
        }
    }

    /**
     * The subquery exists so a product on two of the categories being asked
     * about is not returned twice and paginated as two cards.
     */
    public function test_a_product_on_two_shelves_is_listed_once(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->payload([
                'extra_category_ids' => [$this->womenShirts->id],
            ]));

        $product = Product::firstOrFail();

        $matched = Product::query()
            ->inAnyCategory([$this->menShirts->id, $this->womenShirts->id])
            ->pluck('products.id');

        $this->assertSame([$product->id], $matched->all());
    }

    /**
     * An unresolvable filter must return nothing rather than the whole shop -
     * the behaviour the old whereIn on category_id had.
     */
    public function test_an_empty_category_list_matches_nothing(): void
    {
        $this->makeProduct($this->menShirts);

        $this->assertSame(0, Product::query()->inAnyCategory([])->count());
    }

    public function test_a_category_the_product_is_not_on_does_not_list_it(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->payload());

        $this->assertSame(0, Product::query()->inAnyCategory([$this->womenShirts->id])->count());
    }

    public function test_deleting_a_category_does_not_leave_a_dangling_shelf(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->payload([
                'extra_category_ids' => [$this->womenShirts->id],
            ]));

        $this->womenShirts->delete();

        $this->assertDatabaseMissing('category_product', ['category_id' => $this->womenShirts->id]);
        $this->assertSame(1, DB::table('category_product')->count());
    }

    public function test_an_unknown_category_is_rejected(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->payload([
                'extra_category_ids' => [999999],
            ]))
            ->assertSessionHasErrors('extra_category_ids.0');
    }

    public function test_both_product_forms_offer_the_shelf_list(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->payload([
                'extra_category_ids' => [$this->womenShirts->id],
            ]));

        $product = Product::firstOrFail();

        $forms = [
            'create' => route('admin.products.create'),
            'edit' => route('admin.products.edit', $product),
        ];

        foreach ($forms as $which => $url) {
            $html = $this->actingAs($this->adminUser, 'admin')->get($url)->assertOk()->getContent();

            $this->assertStringContainsString(
                'name="extra_category_ids[]"',
                $html,
                "The {$which} form has no shelf list."
            );
        }
    }

    /**
     * Reopening the edit screen has to show the shelves the product is on, or
     * saving an unrelated change silently drops it off them.
     */
    public function test_the_edit_form_ticks_the_shelves_already_chosen(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->payload([
                'extra_category_ids' => [$this->womenShirts->id],
            ]));

        $product = Product::firstOrFail();
        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.products.edit', $product))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<input[^>]*value="'.$this->womenShirts->id.'"[^>]*checked/s',
            $html,
            'The second shelf is not ticked, so the next save would drop it.'
        );
    }
}
