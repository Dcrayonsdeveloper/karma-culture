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
                'position' => $i,
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

    public function test_a_sub_label_left_behind_in_the_database_is_not_printed(): void
    {
        // The hangers carry a label and nothing else now. The column outlived
        // the field that fed it - every live row still holds whatever an admin
        // last typed there - so the rail has to ignore it rather than merely
        // stop offering it. query()->update writes straight past $fillable,
        // which no longer lists the column.
        $this->seedSizes(['S']);
        ShopFilterItem::query()->update(['sub_label' => '10 Styles']);

        $this->get('/')
            ->assertOk()
            ->assertSee('>S<', false)
            ->assertDontSee('10 Styles')
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
        // Price and shade have no rows at all here, so their panels must not
        // render the bar on its own with nothing hooked over it.
        $this->seedSizes(['S', 'M']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'kk-rail-bar"'));
    }
}
