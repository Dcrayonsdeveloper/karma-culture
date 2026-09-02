<?php

namespace Tests\Feature\Homepage;

use App\Models\ShopFilterItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopFilterRailTest extends TestCase
{
    use RefreshDatabase;

    private function seedSizes(array $labels): void
    {
        foreach ($labels as $i => $label) {
            ShopFilterItem::create([
                'type' => 'size',
                'label' => $label,
                'sub_label' => (10 * ($i + 1)) . ' Styles',
                'position' => $i,
                'is_active' => true,
            ]);
        }
    }

    public function test_the_rail_gives_itself_one_column_per_hanger(): void
    {
        // Seven, because that is the count that broke it: the grid was pinned to
        // six columns, so the seventh hanger wrapped onto a second row.
        $this->seedSizes(['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL']);

        $this->get('/')
            ->assertOk()
            ->assertSee('--kk-rail-cols: 7', false);
    }

    public function test_six_hangers_still_lay_out_in_six_columns(): void
    {
        $this->seedSizes(['S', 'M', 'L', 'XL', 'XXL', '3XL']);

        $this->get('/')
            ->assertOk()
            ->assertSee('--kk-rail-cols: 6', false);
    }

    public function test_a_long_list_is_capped_rather_than_shrinking_each_hanger(): void
    {
        $this->seedSizes(['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12']);

        // Wrapping to a second row is fine now that the stage grows with its
        // content; twelve slivers in one row would not be.
        $this->get('/')
            ->assertOk()
            ->assertSee('--kk-rail-cols: 8', false);
    }

    public function test_the_sub_label_is_printed_as_the_admin_authored_it(): void
    {
        $this->seedSizes(['S']);

        $this->get('/')
            ->assertOk()
            ->assertSee('>10 Styles<', false)
            ->assertDontSee('Styles Styles', false);
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
}
