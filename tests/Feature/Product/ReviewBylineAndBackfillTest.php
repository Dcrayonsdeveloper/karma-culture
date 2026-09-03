<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Two consequences of reviews never having carried a user_id.
 *
 * The product page built its byline straight off the relation -
 * $review->user?->first_name ?? 'Anonymous' - and never looked at guest_name.
 * Since GuestReviewController wrote guest_name and left user_id NULL on every
 * review the site ever took, every published review read "Anonymous" under an
 * "A" avatar, however carefully the reviewer had typed their name. The model
 * already had reviewer_name and reviewer_initial accessors that fall back to
 * guest_name; nothing called them.
 *
 * The same block also printed "Verified Buyer" on every review unconditionally,
 * while the flag behind it was hardcoded false at the point of writing. The
 * badge was a decoration, not a claim anyone had checked.
 *
 * The rest covers the backfill that gives reviews written before the fix their
 * authors back, by matching guest_email against an account. That match is safe
 * to make once, under a migration: it is emphatically not safe to make at read
 * time, because guest_email is free text nobody ever proved they own.
 */
class ReviewBylineAndBackfillTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $category = Category::create([
            'name' => 'Byline Wear',
            'slug' => 'byline-wear',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Byline Test Shirt',
            'slug' => 'byline-test-shirt',
            'sku' => 'BTS-001',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 5,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    private function review(array $attributes = []): Review
    {
        return Review::create($attributes + [
            'product_id' => $this->product->id,
            'guest_name' => 'Priya Raghavan',
            'guest_email' => 'guest'.uniqid().'@example.com',
            'rating' => 5,
            'content' => 'A published review that has to carry a name.',
            'is_verified_purchase' => false,
            'is_approved' => true,
            'status' => 'approved',
        ]);
    }

    private function runBackfill(): void
    {
        $migration = require database_path('migrations/2026_09_03_100000_backfill_review_user_id_from_guest_email.php');

        $migration->up();
    }

    public function test_a_guest_review_is_published_under_the_name_the_reviewer_typed(): void
    {
        $this->review();

        $this->get('/products/'.$this->product->slug)
            ->assertOk()
            ->assertSee('Priya Raghavan')
            ->assertDontSee('Anonymous');
    }

    public function test_an_attributed_review_with_no_typed_name_is_published_under_the_account(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Devika',
            'last_name' => 'Menon',
            'role' => 'customer',
        ]);

        $this->review(['user_id' => $user->id, 'guest_name' => null]);

        $this->get('/products/'.$this->product->slug)
            ->assertOk()
            ->assertSee('Devika Menon')
            ->assertDontSee('Anonymous');
    }

    /**
     * The backfill attaches an account to reviews that are already public. If
     * the byline preferred the account it would silently rename them, so
     * guest_name - what the reviewer actually published under - wins.
     */
    public function test_attributing_a_review_does_not_rename_what_was_already_published(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Sandeep',
            'last_name' => '',
            'role' => 'customer',
        ]);

        $this->review([
            'user_id' => $user->id,
            'guest_name' => 'Aamir Melani',
        ]);

        $this->get('/products/'.$this->product->slug)
            ->assertOk()
            ->assertSee('Aamir Melani')
            ->assertDontSee('Sandeep');
    }

    public function test_the_verified_buyer_badge_is_only_shown_on_a_verified_purchase(): void
    {
        $this->review(['is_verified_purchase' => false]);

        $this->get('/products/'.$this->product->slug)
            ->assertOk()
            ->assertSee('Priya Raghavan')
            ->assertDontSee('Verified Buyer');
    }

    public function test_the_verified_buyer_badge_is_shown_when_the_purchase_was_verified(): void
    {
        $this->review(['is_verified_purchase' => true]);

        $this->get('/products/'.$this->product->slug)
            ->assertOk()
            ->assertSee('Verified Buyer');
    }

    public function test_the_backfill_attaches_a_historical_review_to_the_matching_account(): void
    {
        $user = User::factory()->create(['email' => 'sandeep@example.com', 'role' => 'customer']);

        $review = $this->review(['guest_email' => 'sandeep@example.com', 'user_id' => null]);

        $this->runBackfill();

        $this->assertSame(
            $user->id,
            $review->fresh()->user_id,
            'The review was left under this account\'s address and still did not belong to it.'
        );
    }

    public function test_the_backfilled_review_then_shows_up_on_my_reviews(): void
    {
        $user = User::factory()->create(['email' => 'sandeep@example.com', 'role' => 'customer']);

        $this->review([
            'guest_email' => 'sandeep@example.com',
            'user_id' => null,
            'content' => 'Written before reviews carried an account.',
        ]);

        $this->runBackfill();

        $this->actingAs($user)
            ->get(route('account.reviews'))
            ->assertOk()
            ->assertSee('Written before reviews carried an account.');
    }

    /**
     * guest_email was stored exactly as typed. MySQL's utf8mb4 collation would
     * match regardless of case; SQLite would not, so the migration lowers both
     * sides rather than trusting the column.
     */
    public function test_the_backfill_matches_regardless_of_the_case_of_the_address(): void
    {
        $user = User::factory()->create(['email' => 'mixed@example.com', 'role' => 'customer']);

        $review = $this->review(['guest_email' => 'Mixed@Example.COM', 'user_id' => null]);

        $this->runBackfill();

        $this->assertSame($user->id, $review->fresh()->user_id);
    }

    public function test_the_backfill_leaves_a_guest_with_no_account_alone(): void
    {
        $review = $this->review(['guest_email' => 'nobody@example.com', 'user_id' => null]);

        $this->runBackfill();

        $this->assertNull(
            $review->fresh()->user_id,
            'There is no account behind this address, so there was nothing to attribute the review to.'
        );
    }

    public function test_the_backfill_does_not_attribute_a_review_to_a_closed_account(): void
    {
        $user = User::factory()->create(['email' => 'closed@example.com', 'role' => 'customer']);
        $user->delete();

        $review = $this->review(['guest_email' => 'closed@example.com', 'user_id' => null]);

        $this->runBackfill();

        $this->assertNull($review->fresh()->user_id);
    }

    /**
     * It runs on a server that may already have run it, and the deploy may
     * repeat it. A second pass must not move anything.
     */
    public function test_the_backfill_is_safe_to_run_twice(): void
    {
        $user = User::factory()->create(['email' => 'twice@example.com', 'role' => 'customer']);

        $review = $this->review(['guest_email' => 'twice@example.com', 'user_id' => null]);

        $this->runBackfill();
        $this->runBackfill();

        $this->assertSame($user->id, $review->fresh()->user_id);
        $this->assertSame(1, Review::count());
    }

    /**
     * Attribution is not a content edit. Going through Eloquent would bump
     * updated_at on every row and fire Review::updated, which rebuilds the
     * product's rating for a column that is not part of it.
     */
    public function test_the_backfill_does_not_touch_the_review_timestamp(): void
    {
        User::factory()->create(['email' => 'stamp@example.com', 'role' => 'customer']);

        $review = $this->review(['guest_email' => 'stamp@example.com', 'user_id' => null]);
        $before = $review->fresh()->updated_at;

        $this->runBackfill();

        $this->assertEquals(
            $before,
            $review->fresh()->updated_at,
            'The backfill rewrote updated_at, so the audit trail now says these reviews were edited.'
        );
    }
}
