<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Reviews screen kept two records of the same decision: `status`, which the
 * badges and tab counts read, and `is_approved`, which the filters and the
 * storefront read. Moderating wrote only the boolean, so the two drifted:
 *
 *  - approving left `status` at 'pending', so an approved review still showed a
 *    Pending badge and the "Approved" tab counted 0;
 *  - rejecting set `is_approved` to false, which is also what a brand new review
 *    looks like, so the Rejected tab listed every unmoderated review too - reject
 *    one, reload, and two came back.
 *
 * Moderation now goes through Review::approve()/reject(), which move both
 * columns together, and the screen filters on the column it displays.
 */
class ReviewModerationStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Review',
            'last_name' => 'Admin',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Review Shirts',
            'slug' => 'review-shirts',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Review Test Shirt',
            'slug' => 'review-test-shirt',
            'sku' => 'RTS-001',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 5,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    private function review(string $content = 'A pending review'): Review
    {
        return Review::create([
            'product_id' => $this->product->id,
            'guest_name' => 'Guest',
            'guest_email' => 'guest'.uniqid().'@example.com',
            'rating' => 4,
            'content' => $content,
            'is_verified_purchase' => false,
            'is_approved' => false,
            'status' => 'pending',
        ]);
    }

    public function test_approving_moves_status_and_the_visibility_flag_together(): void
    {
        $review = $this->review();

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.reviews.approve', $review))
            ->assertRedirect();

        $review->refresh();

        $this->assertSame('approved', $review->status, 'Approving left status behind, so the row still renders a Pending badge.');
        $this->assertTrue($review->is_approved, 'Approving did not make the review visible on the storefront.');
        $this->assertNotNull($review->moderated_at);
        $this->assertSame($this->adminUser->id, $review->moderated_by);
    }

    public function test_rejecting_moves_status_and_the_visibility_flag_together(): void
    {
        $review = $this->review();

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.reviews.reject', $review))
            ->assertRedirect();

        $review->refresh();

        $this->assertSame('rejected', $review->status);
        $this->assertFalse($review->is_approved);
        $this->assertNotNull($review->moderated_at);
    }

    public function test_the_rejected_tab_lists_only_rejected_reviews(): void
    {
        $rejected = $this->review('This one gets rejected');
        $untouched = $this->review('This one was never moderated');

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.reviews.reject', $rejected));

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.reviews.index', ['status' => 'rejected']))
            ->assertOk()
            ->assertSee($rejected->content)
            ->assertDontSee($untouched->content);
    }

    public function test_the_approved_tab_lists_approved_reviews_and_badges_them_as_approved(): void
    {
        $approved = $this->review('This one gets approved');
        $untouched = $this->review('This one was never moderated');

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.reviews.approve', $approved));

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.reviews.index', ['status' => 'approved']))
            ->assertOk()
            ->assertSee($approved->content)
            ->assertDontSee($untouched->content)
            ->assertSee('badge badge-success')
            ->assertDontSee('badge badge-warning">Pending');
    }

    public function test_the_tab_counts_follow_the_moderation_decisions(): void
    {
        $approved = $this->review('Approved one');
        $rejected = $this->review('Rejected one');
        $this->review('Still pending');

        $this->actingAs($this->adminUser, 'admin')->post(route('admin.reviews.approve', $approved));
        $this->actingAs($this->adminUser, 'admin')->post(route('admin.reviews.reject', $rejected));

        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.reviews.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('All <span style="color: #616161; font-size: 12px;">(3)</span>', $html);
        $this->assertStringContainsString('Pending <span style="color: #616161; font-size: 12px;">(1)</span>', $html);
        $this->assertStringContainsString('Approved <span style="color: #616161; font-size: 12px;">(1)</span>', $html);
        $this->assertStringContainsString('Rejected <span style="color: #616161; font-size: 12px;">(1)</span>', $html);
    }

    public function test_an_unknown_status_filter_is_rejected(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.reviews.index', ['status' => 'nonsense']))
            ->assertSessionHasErrors('status');
    }
}
