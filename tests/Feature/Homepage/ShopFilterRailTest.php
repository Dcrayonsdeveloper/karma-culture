<?php

namespace Tests\Feature\Homepage;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The rail's layout, now that its hangers come from the catalogue.
 *
 * These are the same geometry assertions as before - the rail sizes itself to
 * what it holds, a long list scrolls rather than wrapping, an empty tab draws
 * no bar - but the fixture underneath them has changed. Hangers used to be rows
 * an admin typed into `shop_filter_items`; they are derived from the products
 * now, so the way to put seven hangers on the rail is to sell seven sizes.
 */
class ShopFilterRailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * One product carrying the given sizes, which is what puts them on the Size
     * rail. It lists no colours and no textures and there is only one price, so
     * the other three tabs have nothing to show and the assertions below are
     * about the Size rail alone.
     *
     * @param  array<int, string>  $labels
     */
    private function seedSizes(array $labels): void
    {
        $category = Category::create(['name' => 'Men', 'slug' => 'men', 'is_active' => true]);

        $product = Product::create([
            'name' => 'Oxford Shirt',
            'slug' => 'oxford-shirt',
            'sku' => 'OXFORD',
            'price' => 799,
            'mrp' => 999,
            'stock_quantity' => 20,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        foreach ($labels as $i => $label) {
            ProductVariant::create([
                'product_id' => $product->id,
                'name' => $label,
                'sku' => 'OXFORD-'.$i,
                'price' => 799,
                'stock_quantity' => 5,
                'is_active' => true,
            ]);
        }
    }

    public function test_the_rail_sizes_itself_from_the_hanger_count(): void
    {
        // Seven, because that is the count that broke it: the rail was pinned to
        // six, so the seventh hanger dropped onto a second row and the overflow
        // painted over the section below.
        $this->seedSizes(['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL']);

        $this->get('/')
            ->assertOk()
            ->assertSee('--kk-rail-count: 7', false)
            ->assertDontSee('repeat(6, 1fr)', false);
    }

    public function test_six_hangers_still_report_six(): void
    {
        $this->seedSizes(['S', 'M', 'L', 'XL', 'XXL', '3XL']);

        $this->get('/')
            ->assertOk()
            ->assertSee('--kk-rail-count: 6', false);
    }

    public function test_a_long_list_scrolls_sideways_instead_of_wrapping(): void
    {
        $this->seedSizes(['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12']);

        // Twelve stay on one rail and the row scrolls, so the bar still runs
        // through every hook and nothing wraps out of the section.
        $this->get('/')
            ->assertOk()
            ->assertSee('--kk-rail-count: 12', false)
            ->assertSee('kk-rail-scroll', false);
    }

    public function test_a_hanger_carries_a_label_and_nothing_under_it(): void
    {
        // The hangers used to print a sub-label an admin typed ("120 Styles"),
        // a number nobody kept up to date. A derived hanger has no such field at
        // all, so the rail must still draw the label on its own.
        $this->seedSizes(['S']);

        $this->get('/')
            ->assertOk()
            ->assertSee('>S<', false)
            ->assertDontSee('class="kk-rail-count"', false);
    }

    public function test_the_stage_no_longer_pins_a_height_the_rail_can_overflow(): void
    {
        $this->seedSizes(['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL']);

        $html = $this->get('/')->assertOk()->getContent();

        // The old stage was `position: absolute` panels inside a fixed 420px box,
        // so an extra row escaped the section and painted over the one below it.
        $this->assertStringNotContainsString('min-height: 420px', $html);
        $this->assertStringNotContainsString('min-height: 560px', $html);
        $this->assertStringContainsString('grid-area: 1 / 1', $html);
    }

    public function test_an_emptied_tab_does_not_leave_a_bare_rail_hanging(): void
    {
        // Three of the four tabs have nothing at all here, and none of them may
        // render the bar on its own with nothing hooked over it.
        $this->seedSizes(['S', 'M']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'kk-rail-bar"'));
    }

    public function test_a_shop_with_nothing_in_it_draws_no_rail_at_all(): void
    {
        // Every tab is derived now, so an empty catalogue is an empty section
        // rather than a row of buttons onto four bare rails.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('kk-rail-bar"', $html);
        $this->assertStringNotContainsString("tab='size'", $html);
    }
}
