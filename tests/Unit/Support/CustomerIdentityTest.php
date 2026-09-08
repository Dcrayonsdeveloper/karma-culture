<?php

namespace Tests\Unit\Support;

use App\Models\Order;
use App\Models\User;
use App\Support\CustomerIdentity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The rule that decides whether two orders were placed by the same person.
 *
 * Everything the repeat-customer badge shows rests on this, and it is the half
 * of the feature that can be wrong without looking wrong: a badge saying 2 is
 * just as convincing whether or not those two orders really came from one
 * customer. So the rule is pinned here, both directions - what must group, and
 * what must never be grouped.
 *
 * No RefreshDatabase: identity is derived from columns already in memory, which
 * is exactly why a badge on every row costs no query.
 */
class CustomerIdentityTest extends TestCase
{
    #[Test]
    #[DataProvider('phoneNumbers')]
    public function it_compares_mobile_numbers_on_their_last_ten_digits(?string $given, ?string $expected): void
    {
        $this->assertSame($expected, CustomerIdentity::normalisePhone($given));
    }

    /** @return array<string, array{0: ?string, 1: ?string}> */
    public static function phoneNumbers(): array
    {
        return [
            'plain ten digits' => ['9876543210', '9876543210'],
            'country code' => ['919876543210', '9876543210'],
            'country code and plus' => ['+919876543210', '9876543210'],
            'spaced and dashed' => ['+91 98765-43210', '9876543210'],
            'leading zero' => ['09876543210', '9876543210'],
            // Short of ten digits it is not a phone number, and a short key
            // could collide with the tail of a real one.
            'too short' => ['12345', null],
            'nine digits' => ['987654321', null],
            'no digits at all' => ['not a phone', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }

    #[Test]
    public function an_order_from_a_signed_in_customer_is_identified_by_the_account(): void
    {
        $order = new Order();
        $order->user_id = 42;
        $order->metadata = ['guest_phone' => '9876543210'];

        // The account wins even when a phone number is present: one account
        // ordering to two different numbers is still one customer.
        $this->assertSame('u:42', CustomerIdentity::ofOrder($order));
    }

    #[Test]
    public function a_guest_order_is_identified_by_the_mobile_number_it_was_placed_with(): void
    {
        $order = new Order();
        $order->metadata = ['guest_phone' => '+91 98765 43210'];

        $this->assertSame('p:9876543210', CustomerIdentity::ofOrder($order));
    }

    #[Test]
    public function a_guest_order_falls_back_to_the_address_snapshot_for_its_number(): void
    {
        // Older rows, written before checkout copied the canonical number onto
        // the metadata, only have it on the snapshot.
        $order = new Order();
        $order->metadata = ['guest_checkout' => true];
        $order->shipping_address_snapshot = ['phone' => '9123456780', 'name' => 'Ghanshyam'];

        $this->assertSame('p:9123456780', CustomerIdentity::ofOrder($order));
    }

    #[Test]
    public function two_guests_who_share_an_email_are_not_the_same_customer(): void
    {
        // This is not hypothetical. On production one test address sits on four
        // guest orders placed by four different people; keying on the email
        // would have shown each of them a badge reading 4.
        $first = new Order();
        $first->metadata = ['guest_email' => 'tim1@in.dcrayons.app', 'guest_phone' => '9000000001'];

        $second = new Order();
        $second->metadata = ['guest_email' => 'tim1@in.dcrayons.app', 'guest_phone' => '9000000002'];

        $this->assertNotSame(CustomerIdentity::ofOrder($first), CustomerIdentity::ofOrder($second));
    }

    #[Test]
    public function an_order_with_no_account_and_no_usable_number_has_no_identity(): void
    {
        $order = new Order();
        $order->metadata = ['guest_email' => 'someone@example.com'];
        $order->shipping_address_snapshot = ['name' => 'Nobody'];

        // Null, not a shared "unknown" bucket - otherwise every anonymous order
        // in the shop would group into one customer with a large badge.
        $this->assertNull(CustomerIdentity::ofOrder($order));
    }

    #[Test]
    public function a_missing_order_has_no_identity(): void
    {
        $this->assertNull(CustomerIdentity::ofOrder(null));
    }

    #[Test]
    #[DataProvider('keyCandidates')]
    public function it_only_accepts_keys_it_could_have_produced(mixed $candidate, bool $valid): void
    {
        $this->assertSame($valid, CustomerIdentity::isKey($candidate));
    }

    /** @return array<string, array{0: mixed, 1: bool}> */
    public static function keyCandidates(): array
    {
        return [
            'account' => ['u:42', true],
            'account with many digits' => ['u:987654321', true],
            'guest' => ['p:9876543210', true],
            'account zero' => ['u:0', false],
            'account with a leading zero' => ['u:042', false],
            'guest with nine digits' => ['p:987654321', false],
            'guest with eleven digits' => ['p:98765432101', false],
            'unknown prefix' => ['x:42', false],
            'no prefix' => ['42', false],
            'empty' => ['', false],
            'not a string' => [42, false],
            'null' => [null, false],
            // The key is interpolated into SQL, so the shape check is the thing
            // standing between a query string and the database.
            'injection' => ["u:1' OR '1'='1", false],
            'injection with a comment' => ['u:1--', false],
            'trailing newline' => ["u:42\n", false],
        ];
    }

    #[Test]
    public function it_names_a_guest_from_the_address_they_gave(): void
    {
        $order = new Order();
        $order->metadata = ['guest_phone' => '9876543210'];
        $order->shipping_address_snapshot = ['name' => 'Aamir Melani', 'phone' => '9876543210'];

        $this->assertSame('Aamir Melani', CustomerIdentity::nameOfOrder($order));
        $this->assertSame('Aamir Melani (9876543210)', CustomerIdentity::describe('p:9876543210', $order));
    }

    #[Test]
    public function it_names_a_signed_in_customer_from_their_account(): void
    {
        $user = new User(['first_name' => 'Dev', 'last_name' => 'Rawat']);

        $order = new Order();
        $order->user_id = 21;
        $order->setRelation('user', $user);

        $this->assertSame('Dev Rawat', CustomerIdentity::nameOfOrder($order));
        $this->assertSame('Dev Rawat', CustomerIdentity::describe('u:21', $order));
    }

    #[Test]
    public function a_nameless_guest_is_still_described_by_their_number(): void
    {
        $this->assertSame('9876543210', CustomerIdentity::describe('p:9876543210', null));
        $this->assertSame('this customer', CustomerIdentity::describe('u:21', null));
    }
}
