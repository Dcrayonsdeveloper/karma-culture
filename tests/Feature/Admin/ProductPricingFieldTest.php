<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two problems with the product form's pricing card.
 *
 * The rupee symbol is drawn inside each money field, and the room for it came
 * from Tailwind's `pl-7`. The admin stylesheet sets `padding` as a shorthand on
 * every admin input at a higher specificity, which wiped that out - typing
 * 1500 rendered as a symbol sitting on top of the 1. The gutter is now a
 * stylesheet rule (`form-input-prefixed`) the admin rules cannot undo.
 *
 * And a compare-at price below the price was only caught by the server, after
 * a round trip that discards the images already chosen. The form now carries a
 * guard that checks the pair as it is typed.
 */
class ProductPricingFieldTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Pricing',
            'last_name' => 'Admin',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Pricing Shirts',
            'slug' => 'pricing-shirts',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Pricing Test Shirt',
            'slug' => 'pricing-test-shirt',
            'sku' => 'PTS-001',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 5,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    /**
     * @return array<int, string>
     */
    public static function pricingForms(): array
    {
        return [
            'create' => ['create'],
            'edit' => ['edit'],
        ];
    }

    /**
     * @dataProvider pricingForms
     */
    public function test_money_fields_reserve_room_for_the_currency_symbol(string $form): void
    {
        $html = $this->formHtml($form);

        foreach (['price', 'mrp', 'cost_price'] as $field) {
            $this->assertMatchesRegularExpression(
                '/<input[^>]*id="'.$field.'"[^>]*class="[^"]*\bform-input-prefixed\b/',
                $html,
                "The {$field} field on the {$form} form no longer reserves the gutter, so the rupee symbol covers the first digit."
            );
        }

        $this->assertStringNotContainsString(
            'form-input w-full pl-7',
            $html,
            'The pricing fields are back on Tailwind pl-7, which the admin padding shorthand overrides.'
        );
    }

    /**
     * @dataProvider pricingForms
     */
    public function test_form_checks_compare_at_price_before_it_is_submitted(string $form): void
    {
        $html = $this->formHtml($form);

        $this->assertStringContainsString('id="product-form"', $html, 'The guard looks the form up by id.');
        $this->assertStringContainsString('id="mrp-compare-error"', $html, 'There is nowhere to show the live message.');
        $this->assertStringContainsString(
            'Compare-at price must not be less than Price.',
            $html,
            "The {$form} form no longer ships the compare-at guard, so a low compare-at price is only caught after the round trip."
        );
    }

    public function test_the_gutter_rule_outranks_the_admin_padding_shorthand(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/\.layout-admin input\[type="number"\]\.form-input-prefixed[^{]*\{[^}]*padding-left/',
            $css,
            'Without an admin-scoped rule, `.layout-admin input[type="number"]` wins and the left padding is lost again.'
        );
    }

    public function test_server_still_rejects_a_compare_at_price_below_the_price(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->productPayload(['price' => 1500, 'mrp' => 900]))
            ->assertSessionHasErrors('mrp');

        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.products.update', $this->product), $this->productPayload([
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'sku' => $this->product->sku,
                'price' => 1500,
                'mrp' => 900,
            ]))
            ->assertSessionHasErrors('mrp');
    }

    public function test_a_compare_at_price_equal_to_the_price_is_accepted(): void
    {
        // The column is NOT NULL and defaults to the price when the field is
        // left blank, so an equal pair has to stay valid - the guard mirrors
        // that by only flagging a compare-at that is genuinely lower.
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.products.store'), $this->productPayload(['price' => 1500, 'mrp' => 1500]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['sku' => 'PRICE-GUARD-1', 'mrp' => 1500]);
    }

    private function formHtml(string $form): string
    {
        $route = $form === 'create'
            ? route('admin.products.create')
            : route('admin.products.edit', $this->product);

        return $this->actingAs($this->adminUser, 'admin')->get($route)->assertOk()->getContent();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Price Guard Shirt',
            'slug' => 'price-guard-shirt',
            'sku' => 'PRICE-GUARD-1',
            'description' => 'A shirt used to check the pricing rules.',
            'price' => 1500,
            'stock_quantity' => 3,
            'category_id' => $this->category->id,
        ], $overrides);
    }
}
