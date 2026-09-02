<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The product form's category picker listed every category, parents included,
 * so "Men" sat in the dropdown next to "Men > Shirts". A product filed onto
 * the parent then showed up under no sub-category the storefront browses, and
 * the two entries looked interchangeable to whoever was adding the product.
 * Only the bottom level is selectable now.
 */
class ProductCategoryPickerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Category $parent;
    private Category $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->parent = Category::create([
            'name' => 'Picker Men',
            'slug' => 'picker-men',
            'is_active' => true,
        ]);

        $this->child = Category::create([
            'parent_id' => $this->parent->id,
            'name' => 'Picker Sub Men 1',
            'slug' => 'picker-sub-men-1',
            'is_active' => true,
        ]);
    }

    public function test_create_form_offers_sub_categories_but_not_their_parents(): void
    {
        $options = $this->categoryOptions(
            $this->actingAs($this->adminUser, 'admin')
                ->get(route('admin.products.create'))
                ->assertOk()
                ->getContent()
        );

        $this->assertNotContains((string) $this->parent->id, $options, 'The parent category "Picker Men" is still selectable.');
        $this->assertContains((string) $this->child->id, $options, 'The sub-category is missing from the picker.');
    }

    public function test_a_childless_root_category_stays_selectable(): void
    {
        $standalone = Category::create([
            'name' => 'Picker Accessories',
            'slug' => 'picker-accessories',
            'is_active' => true,
        ]);

        $options = $this->categoryOptions(
            $this->actingAs($this->adminUser, 'admin')
                ->get(route('admin.products.create'))
                ->assertOk()
                ->getContent()
        );

        $this->assertContains((string) $standalone->id, $options, 'A root category with no children must still be assignable.');
    }

    public function test_edit_form_keeps_a_product_already_filed_on_a_parent(): void
    {
        $product = Product::create([
            'name' => 'Legacy Parent Product',
            'slug' => 'legacy-parent-product',
            'sku' => 'LPP-001',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 5,
            'category_id' => $this->parent->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->getContent();

        $options = $this->categoryOptions($html);

        $this->assertContains((string) $this->parent->id, $options, 'Editing this product would silently clear its category.');
        $this->assertMatchesRegularExpression(
            '/<option value="'.$this->parent->id.'"[^>]*\bselected\b/',
            $html,
            'The product\'s own category is no longer pre-selected on the edit form.'
        );
    }

    public function test_edit_form_hides_parents_the_product_is_not_using(): void
    {
        $otherParent = Category::create([
            'name' => 'Picker Women',
            'slug' => 'picker-women',
            'is_active' => true,
        ]);
        Category::create([
            'parent_id' => $otherParent->id,
            'name' => 'Picker Sub Women',
            'slug' => 'picker-sub-women',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Leaf Product',
            'slug' => 'leaf-product',
            'sku' => 'LP-001',
            'price' => 499,
            'mrp' => 699,
            'stock_quantity' => 5,
            'category_id' => $this->child->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $options = $this->categoryOptions(
            $this->actingAs($this->adminUser, 'admin')
                ->get(route('admin.products.edit', $product))
                ->assertOk()
                ->getContent()
        );

        $this->assertNotContains((string) $this->parent->id, $options);
        $this->assertNotContains((string) $otherParent->id, $options);
        $this->assertContains((string) $this->child->id, $options);
    }

    public function test_the_index_filter_still_lists_parents(): void
    {
        // Legacy products may sit on a parent, so filtering for one is useful.
        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Picker Men', $html);
    }

    /** Values of the <option>s inside the product form's category select. */
    private function categoryOptions(string $html): array
    {
        $this->assertSame(
            1,
            preg_match('/<select[^>]+name="category_id".*?<\/select>/s', $html, $select),
            'No category select found on the page.'
        );

        preg_match_all('/<option value="(\d*)"/', $select[0], $matches);

        return array_values(array_filter($matches[1], fn ($value) => $value !== ''));
    }
}
