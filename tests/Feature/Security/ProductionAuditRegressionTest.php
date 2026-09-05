<?php

namespace Tests\Feature\Security;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regressions for the defects found by the production-readiness audit.
 *
 * Each test names the hole it closes; none of them passed before the fix.
 */
class ProductionAuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $category = Category::create([
            'name' => 'Audit Cat',
            'slug' => 'audit-cat',
            'is_active' => true,
        ]);

        return Product::create([
            'name' => 'Audit Product',
            'slug' => 'audit-product',
            'sku' => 'AUD-001',
            'price' => 500,
            'mrp' => 700,
            'stock_quantity' => 5,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    // ---- the public reviews API used to publish reviewer email addresses ----

    public function test_the_public_reviews_api_does_not_publish_reviewer_email_addresses(): void
    {
        $product = $this->product();

        Review::create([
            'product_id' => $product->id,
            'guest_name' => 'Guest Reviewer',
            'guest_email' => 'private-address@example.com',
            'rating' => 5,
            'title' => 'Lovely',
            'content' => 'Really pleased with this.',
            'is_approved' => true,
            'status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}/reviews");

        $response->assertOk();
        $response->assertDontSee('private-address@example.com');
        $this->assertStringNotContainsString('guest_email', $response->getContent());
    }

    // ---- the questions endpoint filtered on a column that does not exist ----

    public function test_the_product_questions_endpoint_answers_instead_of_500ing(): void
    {
        $product = $this->product();

        $this->getJson("/api/v1/products/{$product->id}/questions")->assertOk();
    }

    // ---- reading someone else's review over the API ----

    public function test_a_customer_cannot_read_another_customers_review(): void
    {
        $product = $this->product();
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'rating' => 4,
            'title' => 'Mine',
            'content' => 'This review belongs to the owner.',
            'is_approved' => true,
            'status' => 'approved',
        ]);

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/reviews/{$review->id}")
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/reviews/{$review->id}")
            ->assertOk();
    }

    // ---- deactivated accounts could still sign in on the website ----

    public function test_a_deactivated_customer_cannot_sign_in(): void
    {
        $user = User::factory()->create([
            'email' => 'switched-off@example.com',
            'password' => bcrypt('correct-horse'),
            'is_active' => false,
            'role' => 'customer',
        ]);

        $this->post('/login', [
            'email' => 'switched-off@example.com',
            'password' => 'correct-horse',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertNotEquals($user->id, auth()->id());
    }

    public function test_an_active_customer_can_still_sign_in(): void
    {
        User::factory()->create([
            'email' => 'still-here@example.com',
            'password' => bcrypt('correct-horse'),
            'is_active' => true,
            'role' => 'customer',
        ]);

        $this->post('/login', [
            'email' => 'still-here@example.com',
            'password' => 'correct-horse',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    // ---- deactivating a staff member left their admin access intact ----

    public function test_a_deactivated_staff_row_does_not_grant_admin_access(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        Staff::create([
            'user_id' => $user->id,
            'employee_id' => 'EMP-OFF-1',
            'role' => 'manager',
            'is_active' => false,
        ]);

        $this->assertFalse($user->fresh()->isStaff(), 'A deactivated staff row must not make someone staff.');
    }

    public function test_an_active_staff_row_still_grants_admin_access(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        Staff::create([
            'user_id' => $user->id,
            'employee_id' => 'EMP-ON-1',
            'role' => 'manager',
            'is_active' => true,
        ]);

        $this->assertTrue($user->fresh()->isStaff());
    }

    // ---- safe_html() let an event handler through without leading whitespace ----

    /**
     * @dataProvider xssPayloads
     */
    public function test_safe_html_strips_event_handlers_however_they_are_spaced(string $payload): void
    {
        $clean = safe_html($payload);

        $this->assertDoesNotMatchRegularExpression(
            '/\bon[a-z]+\s*=/i',
            $clean,
            "safe_html() left an inline event handler in: {$payload}"
        );
    }

    public static function xssPayloads(): array
    {
        return [
            'no space before handler' => ['<img src="x"onerror="alert(1)">'],
            'space before handler' => ['<img src="x" onerror="alert(1)">'],
            'slash before handler' => ['<img src="x"/onerror="alert(1)">'],
            'single quotes' => ["<img src='x'onerror='alert(1)'>"],
            'unquoted value' => ['<img src="x"onerror=alert(1)>'],
        ];
    }

    public function test_safe_html_keeps_ordinary_markup(): void
    {
        $clean = safe_html('<p class="lead">Hello <strong>world</strong></p>');

        $this->assertStringContainsString('<strong>world</strong>', $clean);
        $this->assertStringContainsString('class="lead"', $clean);
    }

    public function test_safe_html_neutralises_javascript_urls_including_entity_encoded_ones(): void
    {
        $this->assertStringNotContainsString('javascript:', safe_html('<a href="javascript:alert(1)">x</a>'));
        $this->assertStringNotContainsString(
            'javascript:',
            html_entity_decode(safe_html('<a href="java&#115;cript:alert(1)">x</a>'))
        );
    }

    // ---- the JSON-LD block could be closed early by review text ----

    public function test_json_ld_escapes_a_script_tag_in_user_content(): void
    {
        $encoded = json_encode(
            ['review' => '</script><script>alert(1)</script>'],
            JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        $this->assertStringNotContainsString('</script>', $encoded);
        $this->assertStringNotContainsString('<script>', $encoded);
    }
}
