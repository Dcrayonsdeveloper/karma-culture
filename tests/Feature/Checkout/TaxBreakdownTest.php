<?php

namespace Tests\Feature\Checkout;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\TaxBreakdown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The checkout showed one figure - "Tax Rs. 5,940.17" - and nothing else.
 *
 * A customer cannot tell from that what rate they paid or how it is made up,
 * and an Indian invoice names CGST and SGST rather than the sum. The row opens
 * now and lists the components.
 *
 * Intra-state only: each slab is halved into CGST and SGST, which is right when
 * the buyer is in the seller's state. The inter-state case - one IGST line at
 * the full rate - is deliberately not guessed at, because the shop has no origin
 * state configured to compare a buyer against.
 */
class TaxBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'is_active' => true]);

        Setting::updateOrCreate(['key' => 'tax_enabled'], ['value' => '1', 'group' => 'tax']);
        Cache::forget('setting.tax_enabled');
    }

    private function product(string $sku, float $price, float $rate, bool $taxable = true): Product
    {
        return Product::create([
            'name' => 'Item '.$sku, 'slug' => 'item-'.strtolower($sku), 'description' => 'x',
            'sku' => $sku, 'price' => $price, 'mrp' => $price, 'stock_quantity' => 50,
            'category_id' => $this->category->id, 'is_active' => true, 'status' => 'approved',
            'is_taxable' => $taxable, 'tax_rate' => $rate,
        ]);
    }

    /** @param array<int, array{0: Product, 1: int}> $lines */
    private function cartOf(array $lines): Cart
    {
        $cart = Cart::create(['user_id' => $this->user->id]);

        foreach ($lines as [$product, $qty]) {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $qty,
                'price' => $product->price,
                'total' => $product->price * $qty,
            ]);
        }

        $cart->refresh()->recalculate();

        return $cart->refresh()->load('items.product');
    }

    public function test_one_rate_splits_into_cgst_and_sgst(): void
    {
        $cart = $this->cartOf([[$this->product('T-1', 1000, 18), 1]]);

        $rows = TaxBreakdown::for($cart);

        $this->assertCount(2, $rows);
        $this->assertSame('CGST 9%', $rows[0]['label']);
        $this->assertSame(90.0, $rows[0]['amount']);
        $this->assertSame('SGST 9%', $rows[1]['label']);
        $this->assertSame(90.0, $rows[1]['amount']);
    }

    /**
     * The halves have to add back up to what the customer is charged, or the
     * invoice contradicts the total beside it.
     */
    public function test_the_components_add_up_to_the_tax_charged(): void
    {
        $cart = $this->cartOf([[$this->product('T-1', 33000.96, 18), 1]]);

        $rows = TaxBreakdown::for($cart);
        $sum = array_sum(array_column($rows, 'amount'));

        $this->assertSame(round((float) $cart->tax, 2), round($sum, 2));
    }

    /**
     * A basket can mix slabs, and two slabs are two pairs of lines - averaging
     * them into one would name a rate nobody was charged.
     */
    public function test_two_slabs_stay_on_separate_lines(): void
    {
        $cart = $this->cartOf([
            [$this->product('T-18', 1000, 18), 1],
            [$this->product('T-5', 1000, 5), 1],
        ]);

        $labels = array_column(TaxBreakdown::for($cart), 'label');

        $this->assertSame(['CGST 9%', 'SGST 9%', 'CGST 2.5%', 'SGST 2.5%'], $labels);
    }

    public function test_a_half_rate_keeps_its_decimal(): void
    {
        $cart = $this->cartOf([[$this->product('T-5', 1000, 5), 1]]);

        $this->assertSame('CGST 2.5%', TaxBreakdown::for($cart)[0]['label']);
    }

    public function test_nothing_to_show_when_tax_is_switched_off(): void
    {
        Setting::updateOrCreate(['key' => 'tax_enabled'], ['value' => '0', 'group' => 'tax']);
        Cache::forget('setting.tax_enabled');

        $cart = $this->cartOf([[$this->product('T-1', 1000, 18), 1]]);

        $this->assertSame([], TaxBreakdown::for($cart));
    }

    public function test_a_product_that_is_not_taxable_contributes_nothing(): void
    {
        $cart = $this->cartOf([[$this->product('T-0', 1000, 18, taxable: false), 1]]);

        $this->assertSame([], TaxBreakdown::for($cart));
    }

    public function test_the_checkout_row_opens_to_the_components(): void
    {
        $this->cartOf([[$this->product('T-1', 1000, 18), 1]]);

        $html = $this->actingAs($this->user)->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<details', $html, 'The tax row is not a disclosure.');
        $this->assertStringContainsString('CGST 9%', $html);
        $this->assertStringContainsString('SGST 9%', $html);
    }

    /**
     * A disclosure, not a script: the breakdown has to be readable before any
     * JavaScript runs, and <summary> brings keyboard and screen-reader
     * behaviour with it.
     */
    public function test_the_row_needs_no_javascript(): void
    {
        $this->cartOf([[$this->product('T-1', 1000, 18), 1]]);

        $html = $this->actingAs($this->user)->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<summary[^>]*>.*?Tax/s', $html);
    }
}
