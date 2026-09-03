<?php

namespace Tests\Feature\Account;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * My Reviews told every customer they had never reviewed anything, including
 * ones whose review was approved and live on the product page.
 *
 * The storefront has exactly one review form. It sits on the product page and
 * posts to product.guest-review for every visitor, signed in or not, and
 * GuestReviewController wrote guest_name and guest_email and never user_id. The
 * form that does record a user - Account\ReviewController::store - is behind
 * account.reviews.create, which nothing on the site links to. So every review
 * the site had ever collected carried user_id NULL, while
 * Account\ReviewController::index reads $request->user()->reviews(), a hasMany
 * on user_id. The page could only ever be empty.
 *
 * Two more things fell out of the same cause. The product page printed the
 * byline as $review->user?->first_name ?? 'Anonymous' with no guest_name
 * fallback, so every published review read "Anonymous" no matter how carefully
 * the reviewer had typed their name. And is_verified_purchase was hardcoded
 * false at the point of writing, because with nobody attached to the row there
 * was no order history to check it against.
 *
 * The reviewer is now taken from the session rather than from the form. That
 * matters beyond attribution: the email box is free text, so a signed-in
 * visitor could type someone else's address, and the duplicate check keyed on
 * exactly that address.
 */
class MyReviewsAttributionTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // product.guest-review is throttle:3,60 and the array cache backing the
        // limiter outlives each test, so a later test would post into a bucket
        // an earlier one had already spent.
        Cache::flush();

        $category = Category::create([
            'name' => 'Review Shirts',
            'slug' => 'review-shirts',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Attribution Test Shirt',
            'slug' => 'attribution-test-shirt',
            'sku' => 'ATS-001',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 5,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    private function customer(array $overrides = []): User
    {
        return User::factory()->create($overrides + ['role' => 'customer']);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'rating' => 5,
            'title' => 'Really good',
            'content' => 'The fabric is heavier than I expected and it has washed well so far.',
        ];
    }

    private function submit(?User $user, array $payload): \Illuminate\Testing\TestResponse
    {
        $request = $user ? $this->actingAs($user) : $this;

        return $request->post(route('product.guest-review', $this->product), $payload);
    }

    /**
     * Marks the product delivered to this account, which is what earns the
     * review its Verified Buyer badge.
     */
    private function deliverTo(User $user): void
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-'.uniqid(),
            'status' => 'delivered',
            'subtotal' => 999,
            'total' => 999,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'sku' => $this->product->sku,
            'mrp' => 1299,
            'price' => 999,
            'quantity' => 1,
            'total' => 999,
        ]);
    }

    public function test_a_signed_in_reviewer_is_recorded_against_their_account(): void
    {
        $user = $this->customer();

        $this->submit($user, $this->payload());

        $review = Review::firstOrFail();

        $this->assertSame(
            $user->id,
            $review->user_id,
            'The product page form filed a signed-in customer as a guest, so My Reviews could never find the review.'
        );
    }

    public function test_a_signed_in_reviewer_sees_the_review_on_my_reviews(): void
    {
        $user = $this->customer();

        $this->submit($user, $this->payload(['content' => 'A review that must appear under My Reviews.']));

        $this->actingAs($user)
            ->get(route('account.reviews'))
            ->assertOk()
            ->assertSee('A review that must appear under My Reviews.')
            ->assertDontSee('No reviews yet');
    }

    /**
     * The page lists what the account wrote, not what the account bought. A
     * review of a product that was never ordered belongs on it just the same.
     */
    public function test_my_reviews_lists_reviews_of_products_the_customer_never_bought(): void
    {
        $user = $this->customer();

        $this->submit($user, $this->payload(['content' => 'Never ordered this, still had something to say.']));

        $review = Review::firstOrFail();
        $this->assertFalse(
            $review->is_verified_purchase,
            'Nothing was ordered, so this must not be marked a verified purchase.'
        );

        $this->actingAs($user)
            ->get(route('account.reviews'))
            ->assertOk()
            ->assertSee('Never ordered this, still had something to say.');
    }

    /**
     * Moderation state decides what the storefront shows, not what My Reviews
     * shows - the reviewer is entitled to see their own review waiting.
     */
    public function test_my_reviews_lists_the_review_while_it_is_still_pending(): void
    {
        $user = $this->customer();

        $this->submit($user, $this->payload(['content' => 'Waiting on a moderator right now.']));

        $this->assertSame('pending', Review::firstOrFail()->status);

        $this->actingAs($user)
            ->get(route('account.reviews'))
            ->assertOk()
            ->assertSee('Waiting on a moderator right now.')
            ->assertSee('Pending');
    }

    public function test_my_reviews_lists_the_review_once_it_is_approved(): void
    {
        $user = $this->customer();

        $this->submit($user, $this->payload(['content' => 'This one gets approved by a moderator.']));

        Review::firstOrFail()->approve();

        $this->actingAs($user)
            ->get(route('account.reviews'))
            ->assertOk()
            ->assertSee('This one gets approved by a moderator.')
            ->assertSee('Approved');
    }

    public function test_one_customers_reviews_do_not_appear_on_another_customers_page(): void
    {
        $mine = $this->customer();
        $theirs = $this->customer();

        $this->submit($theirs, $this->payload(['content' => 'Written by somebody else entirely.']));

        $this->actingAs($mine)
            ->get(route('account.reviews'))
            ->assertOk()
            ->assertSee('No reviews yet')
            ->assertDontSee('Written by somebody else entirely.');
    }

    /**
     * guest_email is free text that nobody ever proved they own. If the account
     * page matched on it, typing a stranger's address into the product page form
     * would put your review inside their My Reviews.
     */
    public function test_a_guest_cannot_plant_a_review_on_someone_elses_account_by_typing_their_email(): void
    {
        $victim = $this->customer(['email' => 'victim@example.com']);

        $this->submit(null, $this->payload([
            'guest_name' => 'Someone Else',
            'guest_email' => 'victim@example.com',
            'content' => 'Planted under an address the writer does not own.',
        ]));

        $this->assertNull(
            Review::firstOrFail()->user_id,
            'An unauthenticated submission must never attach itself to an account.'
        );

        $this->actingAs($victim)
            ->get(route('account.reviews'))
            ->assertOk()
            ->assertDontSee('Planted under an address the writer does not own.');
    }

    /**
     * The account is the source of truth, so a typed address cannot redirect the
     * review - the reason the page reads user_id alone and never guest_email.
     */
    public function test_a_signed_in_reviewer_cannot_file_the_review_against_another_account(): void
    {
        $author = $this->customer(['email' => 'author@example.com']);
        $victim = $this->customer(['email' => 'victim@example.com']);

        $this->submit($author, $this->payload([
            'guest_email' => 'victim@example.com',
            'guest_name' => 'Not The Author',
            'content' => 'Submitted while signed in with somebody elses address typed in.',
        ]));

        $review = Review::firstOrFail();

        $this->assertSame($author->id, $review->user_id);
        $this->assertSame(
            'author@example.com',
            $review->guest_email,
            'The address typed into the form overrode the account it was submitted from.'
        );

        $this->actingAs($victim)
            ->get(route('account.reviews'))
            ->assertOk()
            ->assertDontSee('Submitted while signed in with somebody elses address typed in.');
    }

    public function test_a_guest_review_still_works_and_stays_unattributed(): void
    {
        $this->submit(null, $this->payload([
            'guest_name' => 'Zebediah Guest',
            'guest_email' => 'Zebediah@Example.com',
            'content' => 'Left without an account, exactly as before.',
        ]));

        $review = Review::firstOrFail();

        $this->assertNull($review->user_id);
        $this->assertSame('Zebediah Guest', $review->guest_name);
        $this->assertSame(
            'zebediah@example.com',
            $review->guest_email,
            'Addresses are stored normalised so the duplicate check matches on SQLite as well as MySQL.'
        );
        $this->assertSame('pending', $review->status);
    }

    public function test_a_guest_must_still_give_a_name_and_an_email(): void
    {
        $this->submit(null, $this->payload())
            ->assertSessionHasErrors(['guest_name', 'guest_email']);

        $this->assertSame(0, Review::count());
    }

    public function test_a_signed_in_reviewer_is_not_asked_for_a_name_or_an_email(): void
    {
        $user = $this->customer();

        // No guest_name, no guest_email in the payload at all.
        $this->submit($user, $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Review::count());
    }

    public function test_a_signed_in_customer_cannot_review_the_same_product_twice(): void
    {
        $user = $this->customer();

        $this->submit($user, $this->payload(['content' => 'The first review of this product.']));
        $this->submit($user, $this->payload(['content' => 'A second bite at the same product.']));

        $this->assertSame(1, Review::count(), 'The duplicate guard did not see the account behind the review.');
    }

    /**
     * The guard keyed on the typed address alone, so changing it was all it took
     * to review the same product again.
     */
    public function test_a_signed_in_customer_cannot_dodge_the_duplicate_guard_with_a_different_email(): void
    {
        $user = $this->customer();

        $this->submit($user, $this->payload(['content' => 'The first review of this product.']));
        $this->submit($user, $this->payload([
            'guest_email' => 'a-completely-different@example.com',
            'guest_name' => 'Someone Else',
            'content' => 'A second bite using another address.',
        ]));

        $this->assertSame(1, Review::count());
    }

    public function test_a_guest_still_cannot_review_the_same_product_twice_from_one_address(): void
    {
        $payload = $this->payload([
            'guest_name' => 'Repeat Guest',
            'guest_email' => 'repeat@example.com',
        ]);

        $this->submit(null, $payload + ['content' => 'The first guest review of this product.']);
        $this->submit(null, $payload + ['content' => 'A second guest review of this product.']);

        $this->assertSame(1, Review::count());
    }

    /**
     * MySQL's utf8mb4 collation would catch this on its own; SQLite would not,
     * which is why the address is normalised on write and lowered in the check.
     */
    public function test_the_duplicate_guard_ignores_the_case_of_the_address(): void
    {
        $this->submit(null, $this->payload([
            'guest_name' => 'Case Guest',
            'guest_email' => 'case@example.com',
            'content' => 'The first review, in lower case.',
        ]));

        $this->submit(null, $this->payload([
            'guest_name' => 'Case Guest',
            'guest_email' => 'CASE@EXAMPLE.COM',
            'content' => 'The same address shouted this time.',
        ]));

        $this->assertSame(1, Review::count());
    }

    public function test_a_delivered_order_makes_the_review_a_verified_purchase(): void
    {
        $user = $this->customer();
        $this->deliverTo($user);

        $this->submit($user, $this->payload());

        $this->assertTrue(
            Review::firstOrFail()->is_verified_purchase,
            'is_verified_purchase was hardcoded false on this form, so no review it took could ever be verified.'
        );
    }

    public function test_a_guest_review_is_never_a_verified_purchase(): void
    {
        $this->submit(null, $this->payload([
            'guest_name' => 'Unknown Guest',
            'guest_email' => 'unknown@example.com',
        ]));

        $this->assertFalse(Review::firstOrFail()->is_verified_purchase);
    }
}
