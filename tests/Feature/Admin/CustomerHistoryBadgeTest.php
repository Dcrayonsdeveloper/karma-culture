<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Support\CustomerHistory;
use App\Support\CustomerIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The repeat-customer badge on the admin Orders and Returns lists, and the
 * filtered view it opens.
 *
 * The invariant worth defending is that the two agree. A badge is a promise
 * about how long a list will be, made on a screen where the admin can check it
 * in one click, so most of what follows asserts the count and the click-through
 * against each other rather than against a hard-coded number.
 */
class CustomerHistoryBadgeTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    /** An order from a signed-in customer. */
    private function orderFor(User $customer, array $attributes = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $customer->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 100,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 100,
            'paid_amount' => 100,
            'source' => 'web',
        ], $attributes));
    }

    /** An order placed without an account, the way checkout writes one. */
    private function guestOrder(string $phone, string $name = 'A Guest', ?string $email = null): Order
    {
        return Order::create([
            'user_id' => null,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 100,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 100,
            'paid_amount' => 100,
            'source' => 'web',
            'shipping_address_snapshot' => ['name' => $name, 'phone' => $phone],
            'metadata' => [
                'guest_email' => $email ?? 'guest@example.com',
                'guest_phone' => $phone,
                'guest_checkout' => true,
            ],
        ]);
    }

    private function returnFor(Order $order): OrderReturn
    {
        return OrderReturn::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'type' => 'return',
            'status' => 'requested',
            'reason' => 'Did not fit',
        ]);
    }

    /** Open an admin page as a signed-in administrator. */
    private function open(string $url)
    {
        return $this->actingAs($this->adminUser, 'admin')->get($url);
    }

    public function test_a_returning_customer_is_badged_with_their_lifetime_order_count(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'first_name' => 'Reeta', 'last_name' => 'Roy']);
        $this->orderFor($customer);
        $this->orderFor($customer);
        $this->orderFor($customer);

        $response = $this->open('/admin/orders');

        $response->assertStatus(200);
        $response->assertSee('Reeta Roy has 3 orders on this account - show them all');
        $response->assertSee('customer=' . urlencode('u:' . $customer->id), false);
    }

    public function test_a_first_time_customer_gets_no_badge(): void
    {
        $newcomer = User::factory()->create(['role' => 'customer', 'first_name' => 'Solo', 'last_name' => 'Shopper']);
        $this->orderFor($newcomer);

        $response = $this->open('/admin/orders');

        $response->assertStatus(200);
        $response->assertSee('Solo Shopper');
        // No badge at all, rather than one reading "1": it would link to a list
        // of the single order already on screen.
        $response->assertDontSee('Solo Shopper has 1 order on this account - show them all');
        $response->assertDontSee('customer=' . urlencode('u:' . $newcomer->id), false);
    }

    public function test_a_guest_who_orders_twice_from_one_number_is_recognised(): void
    {
        $this->guestOrder('9876543210', 'Aamir Melani');
        $this->guestOrder('+91 98765 43210', 'Aamir Melani');

        $response = $this->open('/admin/orders');

        $response->assertStatus(200);
        // Written two different ways at checkout, still one customer.
        $response->assertSee('Aamir Melani has 2 orders placed from 9876543210 - show them all');
        $response->assertSee('customer=' . urlencode('p:9876543210'), false);
    }

    public function test_guests_sharing_an_email_address_are_kept_apart(): void
    {
        // The shop's own test address really does sit on several unrelated guest
        // orders in production; keying identity on it would merge them.
        $this->guestOrder('9000000001', 'First Guest', 'shared@example.com');
        $this->guestOrder('9000000002', 'Second Guest', 'shared@example.com');

        $response = $this->open('/admin/orders');

        $response->assertStatus(200);
        $response->assertDontSee('First Guest has 2 orders placed from 9000000001 - show them all');
        $response->assertDontSee('Second Guest has 2 orders placed from 9000000002 - show them all');
    }

    public function test_a_guest_order_with_no_usable_number_is_not_grouped(): void
    {
        // Two anonymous orders must not become one customer who ordered twice.
        $this->guestOrder('', 'Nameless One');
        $this->guestOrder('', 'Nameless Two');

        $response = $this->open('/admin/orders');

        $response->assertStatus(200);
        $response->assertDontSee('show them all');
    }

    public function test_the_badge_opens_exactly_the_orders_it_counted(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'first_name' => 'Anita', 'last_name' => 'Bose']);
        $mine = [$this->orderFor($customer), $this->orderFor($customer)];

        $other = User::factory()->create(['role' => 'customer', 'first_name' => 'Someone', 'last_name' => 'Else']);
        $theirs = $this->orderFor($other);

        $response = $this->open('/admin/orders?customer=' . urlencode('u:' . $customer->id));

        $response->assertStatus(200);
        $response->assertSee('Showing every order from');
        $response->assertSee('Anita Bose');
        foreach ($mine as $order) {
            $response->assertSee($order->order_number);
        }
        $response->assertDontSee($theirs->order_number);
    }

    public function test_the_guest_badge_opens_exactly_the_orders_it_counted(): void
    {
        $first = $this->guestOrder('9876543210', 'Aamir Melani');
        $second = $this->guestOrder('9876543210', 'Aamir Melani');
        $stranger = $this->guestOrder('9111111111', 'Someone Else');

        $response = $this->open('/admin/orders?customer=' . urlencode('p:9876543210'));

        $response->assertStatus(200);
        $response->assertSee($first->order_number);
        $response->assertSee($second->order_number);
        $response->assertDontSee($stranger->order_number);
    }

    public function test_the_count_is_a_lifetime_total_not_a_count_within_the_current_tab(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'first_name' => 'Vikram', 'last_name' => 'Shah']);
        $this->orderFor($customer, ['status' => 'cancelled']);
        $this->orderFor($customer, ['status' => 'confirmed']);
        $this->orderFor($customer, ['status' => 'confirmed']);

        // Looking at one tab must not change what the badge says, because the
        // link on it drops the tab.
        $this->open('/admin/orders?status=cancelled')
            ->assertSee('Vikram Shah has 3 orders on this account - show them all');

        $this->open('/admin/orders?status=confirmed')
            ->assertSee('Vikram Shah has 3 orders on this account - show them all');
    }

    public function test_a_malformed_customer_filter_is_ignored_rather_than_obeyed(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->orderFor($customer);

        foreach (["u:1' OR '1'='1", 'u:0', 'p:123', 'nonsense', ''] as $rubbish) {
            $response = $this->open('/admin/orders?customer=' . urlencode($rubbish));

            $response->assertStatus(200);
            $response->assertSee($order->order_number);
            $response->assertDontSee('Showing every order from');
        }
    }

    public function test_the_customer_filter_survives_a_search_and_can_be_cleared(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'first_name' => 'Nadia', 'last_name' => 'Khan']);
        $this->orderFor($customer);
        $this->orderFor($customer);

        $response = $this->open('/admin/orders?customer=' . urlencode('u:' . $customer->id));

        $response->assertStatus(200);
        // The search box has to carry the filter, or searching silently widens
        // the list back out to the whole shop.
        $response->assertSee('name="customer" value="u:' . $customer->id . '"', false);
        $response->assertSee('Show all orders');
    }

    public function test_a_repeat_returner_is_badged_on_the_returns_list(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'first_name' => 'Ravi', 'last_name' => 'Menon']);
        $this->returnFor($this->orderFor($customer));
        $this->returnFor($this->orderFor($customer));

        $onceOnly = User::factory()->create(['role' => 'customer', 'first_name' => 'Just', 'last_name' => 'Once']);
        $this->returnFor($this->orderFor($onceOnly));

        $response = $this->open('/admin/returns');

        $response->assertStatus(200);
        $response->assertSee('Ravi Menon has 2 returns on this account - show them all');
        $response->assertDontSee('Just Once has 1 return on this account - show them all');
    }

    public function test_the_returns_badge_opens_exactly_the_returns_it_counted(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'first_name' => 'Ravi', 'last_name' => 'Menon']);
        $mine = [
            $this->returnFor($this->orderFor($customer)),
            $this->returnFor($this->orderFor($customer)),
        ];

        $other = User::factory()->create(['role' => 'customer']);
        $theirs = $this->returnFor($this->orderFor($other));

        $response = $this->open('/admin/returns?customer=' . urlencode('u:' . $customer->id));

        $response->assertStatus(200);
        $response->assertSee('Showing every return from');
        foreach ($mine as $return) {
            $response->assertSee($return->return_number);
        }
        $response->assertDontSee($theirs->return_number);
    }

    public function test_a_guest_who_returns_twice_is_recognised_on_the_returns_list(): void
    {
        // A guest return carries no user_id, so the returns list has to read the
        // customer off the order the return was raised against.
        $first = $this->guestOrder('9876543210', 'Aamir Melani');
        $second = $this->guestOrder('9876543210', 'Aamir Melani');
        $this->returnFor($first);
        $this->returnFor($second);

        $response = $this->open('/admin/returns');

        $response->assertStatus(200);
        $response->assertSee('Aamir Melani has 2 returns placed from 9876543210 - show them all');

        $filtered = $this->open('/admin/returns?customer=' . urlencode('p:9876543210'));
        $filtered->assertStatus(200);
        $filtered->assertSee('Showing every return from');
        $filtered->assertSee('Aamir Melani (9876543210)');
    }

    public function test_the_orders_badge_counts_orders_and_the_returns_badge_counts_returns(): void
    {
        // Five orders, two of which were returned. The two screens must not
        // borrow each other's number.
        $customer = User::factory()->create(['role' => 'customer', 'first_name' => 'Tara', 'last_name' => 'Iyer']);
        $orders = collect(range(1, 5))->map(fn () => $this->orderFor($customer));
        $this->returnFor($orders[0]);
        $this->returnFor($orders[1]);

        $this->open('/admin/orders')->assertSee('Tara Iyer has 5 orders on this account - show them all');
        $this->open('/admin/returns')->assertSee('Tara Iyer has 2 returns on this account - show them all');
    }

    public function test_every_customer_on_the_page_is_present_in_the_counts(): void
    {
        // The invariant behind every other assertion here: the SQL that counts
        // and the PHP that derives a row's identity must agree on every row. If
        // they ever drift, a key read off the page goes missing from the counts
        // map and the badge simply does not render - a silent failure that looks
        // exactly like a one-off customer. So it is asserted directly.
        $customer = User::factory()->create(['role' => 'customer']);
        $this->orderFor($customer);
        $this->orderFor($customer);

        $spread = [
            '9876543210',       // plain
            '+91 98765 43211',  // spaced, with a country code
            '09876543212',      // leading zero
            '919876543213',     // country code, no plus
        ];
        foreach ($spread as $phone) {
            $this->guestOrder($phone, 'Guest ' . $phone);
            $this->guestOrder($phone, 'Guest ' . $phone);
        }
        $this->guestOrder('', 'No Number At All');

        $orders = Order::with('user')->get();
        $keys = CustomerHistory::keysOfOrders($orders);
        $counts = CustomerHistory::orderCounts($keys);

        foreach ($keys as $key) {
            $this->assertArrayHasKey(
                $key,
                $counts,
                "The counting query lost [{$key}], which CustomerIdentity read off a row."
            );
        }

        // And the totals must account for exactly the identifiable orders.
        $identifiable = $orders->filter(fn ($order) => CustomerIdentity::ofOrder($order) !== null)->count();
        $this->assertSame($identifiable, array_sum($counts));
        $this->assertSame(10, $identifiable, 'The order with no usable number must not be counted.');
    }

    public function test_every_customer_on_the_returns_page_is_present_in_the_counts(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->returnFor($this->orderFor($customer));
        $this->returnFor($this->orderFor($customer));
        $this->returnFor($this->guestOrder('+91 98765 43210', 'A Guest'));
        $this->returnFor($this->guestOrder('9876543210', 'A Guest'));

        $returns = OrderReturn::with('order.user')->get();
        $keys = CustomerHistory::keysOfReturns($returns);
        $counts = CustomerHistory::returnCounts($keys);

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $counts, "The counting query lost [{$key}].");
        }

        $this->assertSame(4, array_sum(array_column($counts, 'requests')));
    }

    public function test_several_returns_against_one_order_say_so(): void
    {
        // The customer-facing form guards duplicates per order ITEM, not per
        // order, so one order can raise several returns. A bare "3" beside a
        // name reads as a serial returner; the tooltip has to say otherwise.
        $customer = User::factory()->create(['role' => 'customer', 'first_name' => 'Meera', 'last_name' => 'Das']);
        $order = $this->orderFor($customer);
        $this->returnFor($order);
        $this->returnFor($order);
        $this->returnFor($order);

        $response = $this->open('/admin/returns');

        $response->assertStatus(200);
        $response->assertSee('Meera Das has 3 returns on this account, across 1 order - show them all');
    }

    public function test_a_matching_count_does_not_labour_the_point(): void
    {
        // Three returns from three separate orders needs no "across 3 orders".
        $customer = User::factory()->create(['role' => 'customer', 'first_name' => 'Meera', 'last_name' => 'Das']);
        $this->returnFor($this->orderFor($customer));
        $this->returnFor($this->orderFor($customer));
        $this->returnFor($this->orderFor($customer));

        $response = $this->open('/admin/returns');

        $response->assertStatus(200);
        $response->assertSee('Meera Das has 3 returns on this account - show them all');
    }

    public function test_a_guest_history_under_the_same_number_is_disclosed_not_merged(): void
    {
        // Bought twice as a guest, then registered with the same number and
        // bought twice more. The two are NOT merged - a merged badge is a number
        // the admin cannot check - but the filtered page has to admit the rest
        // of the story exists.
        $customer = User::factory()->create([
            'role' => 'customer',
            'first_name' => 'Priya',
            'last_name' => 'Sharma',
            'phone' => '9876543210',
        ]);
        $this->orderFor($customer);
        $this->orderFor($customer);
        $this->guestOrder('9876543210', 'Priya Sharma');
        $this->guestOrder('9876543210', 'Priya Sharma');

        // The account's badge counts the account's orders, exactly.
        $this->open('/admin/orders')
            ->assertSee('Priya Sharma has 2 orders on this account - show them all')
            ->assertSee('Priya Sharma has 2 orders placed from 9876543210 - show them all');

        // And the filtered page points at the other half.
        $account = $this->open('/admin/orders?customer=' . urlencode('u:' . $customer->id));
        $account->assertSee('2 more orders placed as a guest on 9876543210');
        $account->assertSee('customer=' . urlencode('p:9876543210'), false);

        // Symmetrically, from the guest side.
        $guest = $this->open('/admin/orders?customer=' . urlencode('p:9876543210'));
        $guest->assertSee('2 more orders placed on their account');
        $guest->assertSee('customer=' . urlencode('u:' . $customer->id), false);
    }

    public function test_no_disclosure_when_there_is_no_other_history(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'phone' => '9111111111']);
        $this->orderFor($customer);
        $this->orderFor($customer);

        $response = $this->open('/admin/orders?customer=' . urlencode('u:' . $customer->id));

        $response->assertStatus(200);
        $response->assertDontSee('more orders placed as a guest');
    }

    public function test_an_oversized_page_request_cannot_widen_the_counting_query(): void
    {
        // Every row on a page contributes a key to the lifetime-count query, so
        // the page size is what bounds that query. It is bounded on both lists -
        // asserted here rather than assumed, because the badge is the reason it
        // now matters.
        $customer = User::factory()->create(['role' => 'customer']);
        $this->returnFor($this->orderFor($customer));

        $this->assertLessThanOrEqual(100, $this->returnsPerPage(100000));
        $this->assertSame(10, $this->returnsPerPage(null));
        $this->assertSame(25, $this->returnsPerPage(25));
    }

    private function returnsPerPage(?int $requested): int
    {
        $url = '/admin/returns' . ($requested === null ? '' : '?per_page=' . $requested);

        return $this->open($url)->viewData('returns')->perPage();
    }
}
