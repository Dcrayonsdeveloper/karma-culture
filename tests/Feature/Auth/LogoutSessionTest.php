<?php

namespace Tests\Feature\Auth;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LogoutSessionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);

        Admin::create([
            'user_id' => $user->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        return $user;
    }

    public function test_storefront_logout_ends_the_session(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertFalse(Auth::guard('web')->check());
        $this->assertGuest();
    }

    public function test_admin_logout_ends_the_admin_session(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post('/admin/logout')->assertRedirect(route('admin.login'));

        $this->assertFalse(Auth::guard('admin')->check());
    }

    /**
     * The 419 fallback used to call the Auth facade bare, which only reaches the
     * `web` guard. An admin whose CSRF token had expired therefore kept their
     * `remember_admin_*` recaller cookie and an uncycled remember_token —
     * invalidating the session removes neither — so the next request signed them
     * straight back in and "Logout" looked like it had done nothing.
     */
    public function test_expired_token_admin_logout_clears_the_admin_guard(): void
    {
        $admin = $this->admin();

        // NOT actingAs($admin, 'admin') — that also calls shouldUse('admin'),
        // which makes the bare `Auth::logout()` resolve to the admin guard and
        // hides the very bug under test. In a real request the default guard is
        // whatever config/auth.php says (web) while the admin sits on `admin`.
        Auth::guard('admin')->setUser($admin);
        $this->assertSame('web', config('auth.defaults.guard'));
        $this->assertTrue(Auth::guard('admin')->check());

        $response = $this->renderExpiredTokenLogout('/admin/logout');

        $this->assertSame(route('admin.login'), $response->getTargetUrl());
        $this->assertFalse(Auth::guard('admin')->check(), 'admin guard survived the expired-token logout');
    }

    public function test_expired_token_storefront_logout_clears_the_web_guard(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        Auth::guard('web')->setUser($user);
        $this->assertTrue(Auth::guard('web')->check());

        $response = $this->renderExpiredTokenLogout('/logout');

        $this->assertSame(url('/'), $response->getTargetUrl());
        $this->assertFalse(Auth::guard('web')->check());
    }

    private function renderExpiredTokenLogout(string $path)
    {
        $request = Request::create($path, 'POST');

        $session = $this->app['session.store'];
        $session->start();
        $request->setLaravelSession($session);

        return $this->app[ExceptionHandler::class]->render(
            $request,
            new HttpException(419, 'CSRF token mismatch.', new TokenMismatchException)
        );
    }

    public function test_signed_in_pages_are_not_stored_by_the_browser(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $cacheControl = $this->actingAs($user)->get('/')->headers->get('Cache-Control');

        // Without no-store the browser hands the rendered page back on Back
        // after logout, so the previous session's pages stay readable.
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }

    public function test_admin_pages_are_not_stored_by_the_browser(): void
    {
        $admin = $this->admin();

        $cacheControl = $this->actingAs($admin, 'admin')->get('/admin')->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
    }

    public function test_guest_pages_keep_their_normal_cache_headers(): void
    {
        // no-store on anonymous traffic would throw away CDN and browser caching
        // for the whole catalogue, so it is scoped to signed-in requests only.
        $cacheControl = $this->get('/')->headers->get('Cache-Control');

        $this->assertStringNotContainsString('no-store', (string) $cacheControl);
    }
}
