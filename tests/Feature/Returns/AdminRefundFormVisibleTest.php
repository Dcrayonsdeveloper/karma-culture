<?php

namespace Tests\Feature\Returns;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * refund_amount is cast decimal:2, so an un-refunded return reads back as the
 * string "0.00" — truthy in PHP. Every `@if($return->refund_amount)` in the
 * return views therefore fired on returns that had never been refunded, and the
 * admin page's `&& !$return->refund_amount` gate was false on every row, which
 * hid the Process Refund form outright.
 */
class AdminRefundFormVisibleTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $customer;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->customer = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Refund Gate Cat',
            'slug' => 'refund-gate-cat',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Refund Gate Product',
            'slug' => 'refund-gate-product',
            'sku' => 'RGP-001',
            'price' => 1299,
            'mrp' => 1599,
            'cost_price' => 500,
            'stock_quantity' => 15,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $address = UserAddress::create([
            'user_id' => $this->customer->id,
            'label' => 'Home',
            'first_name' => 'Refund',
            'last_name' => 'Customer',
            'phone' => '9876543210',
            'address_line_1' => '456 Refund Ave',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'postal_code' => '500001',
            'country' => 'IN',
            'is_default' => true,
        ]);

        $this->order = Order::create([
            'user_id' => $this->customer->id,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'subtotal' => 1299,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 1299,
            'paid_amount' => 1299,
            'source' => 'web',
            'delivered_at' => now()->subDays(3),
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $product->id,
            'product_name' => 'Refund Gate Product',
            'sku' => 'RGP-001',
            'price' => 1299,
            'mrp' => 1599,
            'quantity' => 1,
            'tax' => 0,
            'discount' => 0,
            'total' => 1299,
        ]);
    }

    private function makeReturn(string $status, $refundAmount = null): OrderReturn
    {
        return OrderReturn::create(array_filter([
            'order_id' => $this->order->id,
            'user_id' => $this->customer->id,
            'type' => 'return',
            'status' => $status,
            'reason' => 'Wrong size',
            'refund_amount' => $refundAmount,
            'approved_at' => $status === 'requested' ? null : now(),
        ], fn ($v) => $v !== null));
    }

    public function test_an_unrefunded_return_reads_back_as_the_truthy_string_zero(): void
    {
        // The trap this whole test class exists for.
        $return = $this->makeReturn('approved');

        $this->assertSame('0.00', (string) $return->fresh()->refund_amount);
        $this->assertTrue((bool) $return->fresh()->refund_amount, 'decimal:2 returns a truthy "0.00"');
    }

    public function test_approved_return_offers_the_process_refund_form(): void
    {
        $return = $this->makeReturn('approved');

        $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/returns/' . $return->id)
            ->assertStatus(200)
            ->assertSee('Process Refund')
            ->assertSee(route('admin.returns.refund', $return), false);
    }

    public function test_received_return_offers_the_process_refund_form(): void
    {
        $return = $this->makeReturn('received');

        $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/returns/' . $return->id)
            ->assertStatus(200)
            ->assertSee(route('admin.returns.refund', $return), false);
    }

    public function test_an_already_refunded_return_does_not_offer_the_form_twice(): void
    {
        $return = $this->makeReturn('received', 1299);

        $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/returns/' . $return->id)
            ->assertStatus(200)
            ->assertDontSee(route('admin.returns.refund', $return), false);
    }

    public function test_a_requested_return_says_how_to_reach_the_refund_form(): void
    {
        $return = $this->makeReturn('requested');

        $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/returns/' . $return->id)
            ->assertStatus(200)
            // Refunding a return nobody approved would skip the workflow, so the
            // page names the one step needed instead of pointing at a form that
            // is not on the page yet.
            ->assertSee('Approve the return first', false)
            ->assertDontSee(route('admin.returns.refund', $return), false);
    }

    public function test_a_zero_refund_is_not_reported_to_the_customer_as_a_refund(): void
    {
        $return = $this->makeReturn('approved');

        $this->actingAs($this->customer)
            ->get('/account/returns/' . $return->id)
            ->assertStatus(200)
            ->assertDontSee('Refund Amount');
    }
}
