<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * A form left open past the session lifetime submits a stale CSRF token.
 * Laravel's default answer is a 419 page with nothing on it but a dead end,
 * which is where customers were landing on both /login and /admin/login.
 *
 * The CSRF middleware is disabled wholesale in the test environment, so these
 * throw the exception from a route instead: what is under test is the handler
 * registered in bootstrap/app.php, not Laravel's own token comparison.
 */
class ExpiredSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Registering the same method and URI replaces the entry in the route
        // collection, so these stand in for the real endpoints at the paths the
        // handler actually branches on.
        foreach (['login', 'admin/login', 'logout', 'admin/logout'] as $path) {
            Route::post($path, fn () => throw new TokenMismatchException())
                ->middleware(['web', StartSession::class]);
        }
    }

    public function test_expired_token_on_login_returns_to_the_form_with_an_explanation(): void
    {
        $response = $this->from('/login')
            ->post('login', ['email' => 'shopper@example.com']);

        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
        $this->assertStringContainsString('expired', strtolower(session('error')));
    }

    public function test_expired_token_keeps_the_typed_input_but_never_the_password(): void
    {
        $this->from('/login')->post('login', [
            'email' => 'shopper@example.com',
            'password' => 'hunter2',
        ]);

        $this->assertSame('shopper@example.com', session('_old_input.email'));
        $this->assertArrayNotHasKey('password', session('_old_input', []));
    }

    public function test_expired_token_on_an_ajax_post_returns_json_rather_than_an_html_error_page(): void
    {
        $response = $this->postJson('login', ['email' => 'shopper@example.com']);

        $response->assertStatus(419);
        $response->assertJsonStructure(['message']);
    }

    public function test_expired_token_on_admin_login_returns_to_the_admin_form(): void
    {
        $response = $this->from('/admin/login')
            ->post('admin/login', ['email' => 'admin@example.com']);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHas('error');
    }

    public function test_expired_token_still_signs_the_visitor_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/account')
            ->post('logout');

        // Home, not back to the page they left, and no error to act on: the
        // session they were asking us to discard is the only thing that was
        // wrong with the request.
        $response->assertRedirect('/');
        $response->assertSessionMissing('error');
        $this->assertGuest();
    }

    public function test_expired_token_on_admin_logout_lands_on_the_admin_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('admin/logout');

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }
}
