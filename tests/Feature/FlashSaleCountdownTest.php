<?php

namespace Tests\Feature;

use App\Models\FlashSale;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashSaleCountdownTest extends TestCase
{
    use RefreshDatabase;

    private function liveSale(): FlashSale
    {
        return FlashSale::create([
            'name' => 'Test',
            'slug' => 'test',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(3)->addSeconds(30),
            'is_active' => true,
        ]);
    }

    public function test_countdown_binds_to_a_registered_alpine_component(): void
    {
        $sale = $this->liveSale();

        $this->get(route('flash-sale.show', $sale))
            ->assertOk()
            ->assertSee('x-data="saleCountdown(', false)
            // The old markup called a global defined in a @push('scripts') block
            // the storefront layout never rendered, so the timer stayed blank.
            ->assertDontSee('kkSaleCountdown', false);
    }

    public function test_remaining_seconds_is_a_whole_number(): void
    {
        // Pinned to a whole second so the assertion is exact. MySQL stores
        // datetimes without microseconds, so an unpinned now() sits a fraction
        // past the stored ends_at and the (int) cast truncates a second away.
        $this->travelTo(Carbon::create(2026, 9, 2, 12, 0, 0));
        $sale = $this->liveSale();

        $remaining = $this->get(route('flash-sale.show', $sale))
            ->assertOk()
            ->viewData('remainingSeconds');

        $this->assertIsInt($remaining);
        $this->assertSame(3 * 3600 + 30, $remaining);
    }

    public function test_ended_sale_shows_the_ended_notice_instead_of_a_timer(): void
    {
        $sale = FlashSale::create([
            'name' => 'Over',
            'slug' => 'over',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subHour(),
            'is_active' => true,
        ]);

        $this->get(route('flash-sale.show', $sale))
            ->assertOk()
            ->assertDontSee('saleCountdown(', false)
            ->assertSee('This sale has ended');
    }
}
