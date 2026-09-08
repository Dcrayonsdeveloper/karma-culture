<?php

namespace Tests\Feature\Returns;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The return form's order list must reach Alpine as a JSON ARRAY.
 *
 * The page builds its x-data with Js::from($orders->...), and reject()/filter()
 * preserve the original keys. A collection keyed 0,1,2 serialises to a JSON
 * array; one keyed 1,2 serialises to a JSON OBJECT. So as soon as the FIRST
 * order was filtered out - which happens to any customer who has already
 * returned something - the page shipped `{"2":{...}}`, and `orders.find(...)`
 * threw "find is not a function".
 *
 * Everything reading currentOrder died with it: the refund-preference cards
 * lost their selected state, the "Select Items to Return" step never appeared,
 * and Submit stayed disabled. Steps 1 and 2 kept working because they only read
 * selectedOrder and type, so the page looked merely cosmetically wrong rather
 * than unusable.
 */
class ReturnFormOrderListTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Product $product;
    private UserAddress $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'List Cat',
            'slug' => 'list-cat',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'List Product',
            'slug' => 'list-product',
            'sku' => 'LST-001',
            'price' => 400,
            'mrp' => 500,
            'cost_price' => 150,
            'stock_quantity' => 30,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $this->address = UserAddress::create([
            'user_id' => $this->customer->id,
            'label' => 'Home',
            'first_name' => 'List',
            'last_name' => 'Customer',
            'phone' => '9876500011',
            'address_line_1' => '9 List Street',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'postal_code' => '500001',
            'country' => 'IN',
            'is_default' => true,
        ]);

        Setting::set('return_min_minutes', '0', 'string', 'shipping');
        Setting::set('return_window_days', '7', 'string', 'shipping');
        Setting::flushMemo();
    }

    /** @return array{0: Order, 1: OrderItem} */
    private function makeOrder(int $minutesAgo): array
    {
        $order = Order::create([
            'user_id' => $this->customer->id,
            'shipping_address_id' => $this->address->id,
            'billing_address_id' => $this->address->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'subtotal' => 400,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 400,
            'paid_amount' => 400,
            'source' => 'web',
            'delivered_at' => now()->subMinutes($minutesAgo),
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => 'List Product',
            'sku' => 'LST-001',
            'price' => 400,
            'mrp' => 500,
            'quantity' => 2,
            'tax' => 0,
            'discount' => 0,
            'total' => 800,
        ]);

        return [$order, $item];
    }

    private function alreadyReturn(Order $order, OrderItem $item): void
    {
        $return = OrderReturn::create([
            'order_id' => $order->id,
            'user_id' => $this->customer->id,
            'type' => 'return',
            'status' => 'requested',
            'reason' => 'Changed my mind',
        ]);

        ReturnItem::create([
            'return_id' => $return->id,
            'order_item_id' => $item->id,
            'quantity' => 1,
            'condition' => 'unopened',
        ]);
    }

    public function test_the_order_list_is_a_json_array_when_the_first_order_is_filtered_out(): void
    {
        [$first, $firstItem] = $this->makeOrder(60);
        $this->makeOrder(30);

        // The lower-id order drops out, leaving the survivor at key 1.
        $this->alreadyReturn($first, $firstItem);

        $content = $this->actingAs($this->customer)
            ->get('/account/returns/create')
            ->assertStatus(200)
            ->getContent();

        $orders = $this->decodeOrders($content);

        $this->assertTrue(
            array_is_list($orders),
            'A collection that kept its original keys serialises to a JSON object, '
            .'and orders.find() then throws "find is not a function".'
        );
        $this->assertCount(1, $orders);
    }

    public function test_the_item_list_is_a_json_array_when_the_first_item_is_filtered_out(): void
    {
        // Same trap one level down: the nested items array is built the same
        // way, so a part-returned order would break the item picker.
        [$order, $item] = $this->makeOrder(60);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => 'Second Item',
            'sku' => 'LST-002',
            'price' => 400,
            'mrp' => 500,
            'quantity' => 1,
            'tax' => 0,
            'discount' => 0,
            'total' => 400,
        ]);

        $this->alreadyReturn($order, $item);

        $content = $this->actingAs($this->customer)
            ->get('/account/returns/create')
            ->assertStatus(200)
            ->getContent();

        $orders = $this->decodeOrders($content);

        $this->assertTrue(array_is_list($orders), 'the order list must be a JSON array');
        $this->assertTrue(
            array_is_list($orders[0]['items']),
            'the item list must be a JSON array, or the x-for and the item picker break'
        );
    }

    /**
     * The order list exactly as Alpine receives it.
     *
     * Asserting on the raw HTML is a trap here: Js::from() escapes every double
     * quote, so the readable spelling of a JSON key never appears in the output
     * and a string assertion passes no matter what the page contains. Decoding
     * the payload tests the thing that actually matters - whether Alpine gets a
     * list it can call .find() on, or an object it cannot.
     */
    private function decodeOrders(string $html): array
    {
        $this->assertSame(
            1,
            preg_match("/orders: JSON\.parse\('(.*?)'\),/s", $html, $m),
            'could not find the serialised order list on the page'
        );

        // Two steps, and the order matters. What sits on the page is a JS
        // string literal: Js::from() escapes every quote as a \u sequence, and
        // the browser resolves those before JSON.parse ever runs. Feeding the
        // raw text to json_decode() therefore returns null - a \u escape is
        // only legal inside a JSON string, not where a key begins. Wrapping it
        // in quotes and decoding once turns it back into real JSON text.
        $json = json_decode('"'.$m[1].'"');

        $this->assertIsString($json, 'the serialised order list is not a valid JS string literal');

        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded, 'the order list did not decode as JSON');

        return $decoded;
    }

    public function test_a_customer_with_no_prior_returns_is_unaffected(): void
    {
        $this->makeOrder(60);

        $content = $this->actingAs($this->customer)
            ->get('/account/returns/create')
            ->assertStatus(200)
            ->getContent();

        $this->assertTrue(array_is_list($this->decodeOrders($content)));
    }
}
