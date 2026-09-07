<?php

namespace Tests\Feature\Returns;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The customer chooses money back or store credit, and above a threshold the
 * store chooses for them.
 *
 * The threshold is a policy about money, so the tests that matter most are the
 * ones that post a choice the UI would never offer: disabling a radio stops a
 * browser, not a curl.
 */
class RefundPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $adminUser;
    private Product $product;
    private UserAddress $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Credit Cat',
            'slug' => 'credit-cat',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Credit Product',
            'slug' => 'credit-product',
            'sku' => 'CRD-001',
            'price' => 500,
            'mrp' => 700,
            'cost_price' => 200,
            'stock_quantity' => 50,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $this->address = UserAddress::create([
            'user_id' => $this->customer->id,
            'label' => 'Home',
            'first_name' => 'Credit',
            'last_name' => 'Customer',
            'phone' => '9876543210',
            'address_line_1' => '1 Credit Road',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'postal_code' => '500001',
            'country' => 'IN',
            'is_default' => true,
        ]);

        // The waiting period must not gate these tests; the countdown owns that.
        Setting::set('return_min_minutes', '0', 'string', 'shipping');
        Setting::set('return_window_days', '7', 'string', 'shipping');
        Setting::flushMemo();
    }

    /** An order delivered inside the window, worth exactly $total. */
    private function makeOrder(float $total): Order
    {
        $order = Order::create([
            'user_id' => $this->customer->id,
            'shipping_address_id' => $this->address->id,
            'billing_address_id' => $this->address->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'subtotal' => $total,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => $total,
            'paid_amount' => $total,
            'source' => 'web',
            'delivered_at' => now()->subDay(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => 'Credit Product',
            'sku' => 'CRD-001',
            'price' => $total,
            'mrp' => $total,
            'quantity' => 1,
            'tax' => 0,
            'discount' => 0,
            'total' => $total,
        ]);

        return $order->fresh('items');
    }

    private function submitReturn(Order $order, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->customer)->post('/account/returns', array_merge([
            'order_id' => $order->id,
            'type' => 'return',
            'refund_preference' => 'refund',
            'reason' => 'Changed my mind',
            'items' => [
                ['order_item_id' => $order->items->first()->id, 'quantity' => 1, 'condition' => 'unopened'],
            ],
        ], $overrides));
    }

    public function test_a_cheap_order_keeps_the_customers_choice(): void
    {
        $order = $this->makeOrder(900);

        $this->submitReturn($order)->assertRedirect();

        $this->assertSame('refund', OrderReturn::latest('id')->first()->refund_preference);
    }

    public function test_a_cheap_order_may_also_choose_store_credit(): void
    {
        $order = $this->makeOrder(900);

        $this->submitReturn($order, ['refund_preference' => 'coupon'])->assertRedirect();

        $this->assertSame('coupon', OrderReturn::latest('id')->first()->refund_preference);
    }

    public function test_an_expensive_order_is_forced_to_store_credit_even_when_refund_is_posted(): void
    {
        // The whole point: this is the request a hand-written POST makes after
        // the radio has been re-enabled in dev tools.
        $order = $this->makeOrder(5000);

        $this->submitReturn($order, ['refund_preference' => 'refund'])->assertRedirect();

        $this->assertSame('coupon', OrderReturn::latest('id')->first()->refund_preference);
    }

    public function test_exactly_on_the_threshold_is_still_the_customers_choice(): void
    {
        // "Above 2000" is strictly greater than, so 2000 itself is not caught.
        $order = $this->makeOrder(2000);

        $this->submitReturn($order, ['refund_preference' => 'refund'])->assertRedirect();

        $this->assertSame('refund', OrderReturn::latest('id')->first()->refund_preference);
    }

    public function test_a_penny_above_the_threshold_is_caught(): void
    {
        $order = $this->makeOrder(2000.01);

        $this->submitReturn($order, ['refund_preference' => 'refund'])->assertRedirect();

        $this->assertSame('coupon', OrderReturn::latest('id')->first()->refund_preference);
    }

    public function test_the_threshold_is_a_setting_not_a_constant(): void
    {
        Setting::set('return_coupon_threshold', '500', 'string', 'shipping');
        Setting::flushMemo();

        $order = $this->makeOrder(900);

        $this->submitReturn($order, ['refund_preference' => 'refund'])->assertRedirect();

        $this->assertSame('coupon', OrderReturn::latest('id')->first()->refund_preference);
    }

    public function test_an_exchange_records_no_refund_preference(): void
    {
        // An exchange is a replacement item; neither answer is true of it, and
        // the threshold must not invent one on an expensive order.
        $order = $this->makeOrder(5000);

        $this->submitReturn($order, ['type' => 'exchange'])->assertRedirect();

        $this->assertNull(OrderReturn::latest('id')->first()->refund_preference);
    }

    public function test_the_form_offers_the_choice_and_publishes_the_threshold(): void
    {
        $this->makeOrder(900);

        $this->actingAs($this->customer)
            ->get('/account/returns/create')
            ->assertStatus(200)
            ->assertSee('Refund Preference')
            ->assertSee('Store Coupon');
    }

    // ---------------------------------------------------------------- admin

    private function makeReturn(Order $order, string $preference): OrderReturn
    {
        return OrderReturn::create([
            'order_id' => $order->id,
            'user_id' => $this->customer->id,
            'type' => 'return',
            'status' => 'received',
            'reason' => 'Changed my mind',
            'refund_preference' => $preference,
        ]);
    }

    public function test_processing_a_credit_return_issues_a_single_use_coupon_for_the_customer(): void
    {
        $order = $this->makeOrder(5000);
        $return = $this->makeReturn($order, 'coupon');

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.returns.refund', $return), [
                'amount' => 5000,
                'refund_method' => 'coupon',
            ])->assertRedirect();

        $return->refresh();
        $coupon = $return->refundCoupon;

        $this->assertNotNull($coupon, 'a credit refund must mint a coupon');
        $this->assertSame('fixed', $coupon->type);
        $this->assertSame('5000.00', (string) $coupon->value);
        $this->assertSame([$this->customer->id], $coupon->applicable_users);
        $this->assertSame(1, (int) $coupon->usage_limit);
        $this->assertTrue((bool) $coupon->is_active);
        $this->assertFalse((bool) $coupon->auto_apply, 'a personal credit must never auto-apply');
        $this->assertSame('coupon', $return->refund_method);
    }

    public function test_the_credit_requires_a_basket_at_least_as_large_as_itself(): void
    {
        // A fixed coupon is capped at the cart subtotal and the remainder is
        // not carried anywhere, so without this the customer silently loses
        // the difference on a smaller order.
        $order = $this->makeOrder(5000);
        $return = $this->makeReturn($order, 'coupon');

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.returns.refund', $return), ['amount' => 5000, 'refund_method' => 'coupon']);

        $this->assertSame('5000.00', (string) $return->fresh()->refundCoupon->min_order_amount);
    }

    public function test_processing_twice_does_not_mint_a_second_voucher(): void
    {
        $order = $this->makeOrder(5000);
        $return = $this->makeReturn($order, 'coupon');

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.returns.refund', $return), ['amount' => 5000, 'refund_method' => 'coupon']);
        $first = $return->fresh()->refund_coupon_id;

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.returns.refund', $return), ['amount' => 5000, 'refund_method' => 'coupon']);

        $this->assertSame($first, $return->fresh()->refund_coupon_id);
        $this->assertSame(1, Coupon::count());
    }

    public function test_an_admin_cannot_cash_refund_a_return_the_customer_was_forced_onto_credit(): void
    {
        // The store told the customer the money would come back as credit. A
        // stale form, or a forged post, must not quietly break that promise.
        $order = $this->makeOrder(5000);
        $return = $this->makeReturn($order, 'coupon');

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.returns.refund', $return), [
                'amount' => 5000,
                'refund_method' => 'original',
            ])->assertRedirect();

        $return->refresh();
        $this->assertSame('coupon', $return->refund_method);
        $this->assertNotNull($return->refundCoupon);
    }

    public function test_a_cash_refund_return_mints_no_coupon(): void
    {
        $order = $this->makeOrder(900);
        $return = $this->makeReturn($order, 'refund');

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.returns.refund', $return), [
                'amount' => 900,
                'refund_method' => 'original',
            ])->assertRedirect();

        $this->assertNull($return->fresh()->refund_coupon_id);
        $this->assertSame(0, Coupon::count());
        $this->assertSame('original', $return->fresh()->refund_method);
    }
}
