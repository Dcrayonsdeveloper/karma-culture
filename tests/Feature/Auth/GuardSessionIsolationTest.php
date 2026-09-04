<?php

namespace Tests\Feature\Auth;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Signing out of one guard must not sign out the other.
 *
 * One session cookie carries both: config/auth.php gives the `admin` guard the
 * same `users` provider as `web`, and Laravel keeps each guard's login state
 * under its own `login_<guard>_<hash>` key inside that single session. Both
 * logout methods called $request->session()->invalidate(), which flushes the
 * lot - so a shopper signing out in one tab silently signed the admin out in
 * another, and an admin signing out emptied the shopper's session under them.
 *
 * It surfaced with the admin bell's ten-second poll: the polling stopped, with
 * no admin action that could explain it, because a customer had logged out in a
 * different tab of the same browser.
 *
 * What must still happen is the rest of invalidate(): the departing side's
 * session data is flushed and the session id is replaced, so nothing they left
 * behind can be read by whoever uses the browser next.
 */
class GuardSessionIsolationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    private string $defaultGuard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultGuard = config('auth.defaults.guard');
    }

    /**
     * Leave the container the way a browser's next request would find it.
     *
     * Illuminate\Auth\Middleware\Authenticate calls shouldUse() on whichever
     * guard it authenticated with, and shouldUse() writes auth.defaults.guard
     * into the config repository. A browser gets a fresh container per request,
     * so that lasts exactly one request. The test client reuses one container
     * for the whole test, so a single call to an auth:admin route leaves 'admin'
     * as the default for every later request that does NOT name a guard - and
     * /account sits behind a bare `auth`.
     *
     * Without this, the storefront reads as signed out when it is not: a
     * property of the harness, not of the application. Verified directly -
     * Auth::guard('web')->check() is true at the moment the bare `auth`
     * middleware redirects. forgetGuards() goes with it so that no assertion can
     * pass on a user a previous request happened to leave resolved in memory.
     */
    private function asFreshRequest(): static
    {
        $this->app['auth']->shouldUse($this->defaultGuard);
        $this->app['auth']->forgetGuards();

        return $this;
    }

    private function shopper(): User
    {
        return User::factory()->create([
            'role' => 'customer',
            'password' => Hash::make(self::PASSWORD),
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make(self::PASSWORD),
        ]);

        Admin::create([
            'user_id' => $user->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        return $user;
    }

    private function signIn(string $route, User $user): void
    {
        $this->asFreshRequest()->post(route($route), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->assertRedirect();
    }

    public function test_a_shopper_signing_out_leaves_the_admin_signed_in(): void
    {
        $this->signIn('login', $this->shopper());
        $this->signIn('admin.login', $this->admin());

        $this->asFreshRequest()->post(route('logout'))->assertRedirect('/');

        // The shopper is out...
        $this->asFreshRequest()->get(route('account.dashboard'))->assertRedirect(route('login'));

        // ...and the admin, in the same browser session, is not - which is what
        // keeps the notification poll running.
        $this->asFreshRequest()->get(route('admin.notifications'))->assertOk();
    }

    public function test_an_admin_signing_out_leaves_the_shopper_signed_in(): void
    {
        $this->signIn('login', $this->shopper());
        $this->signIn('admin.login', $this->admin());

        $this->asFreshRequest()->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->asFreshRequest()->get(route('admin.notifications'))->assertRedirect(route('admin.login'));
        $this->asFreshRequest()->get(route('account.dashboard'))->assertOk();
    }

    /**
     * The half that was never in doubt, kept here so the fix above cannot be
     * read as "logging out stopped working".
     */
    public function test_signing_out_still_signs_that_guard_out(): void
    {
        $this->signIn('login', $this->shopper());
        $this->asFreshRequest()->get(route('account.dashboard'))->assertOk();

        $this->asFreshRequest()->post(route('logout'))->assertRedirect('/');
        $this->asFreshRequest()->get(route('account.dashboard'))->assertRedirect(route('login'));
    }

    public function test_an_admin_signing_out_alone_still_ends_the_admin_session(): void
    {
        $this->signIn('admin.login', $this->admin());
        $this->asFreshRequest()->get(route('admin.notifications'))->assertOk();

        $this->asFreshRequest()->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->asFreshRequest()->get(route('admin.notifications'))->assertRedirect(route('admin.login'));
    }

    /**
     * Only the surviving guard's login keys are carried over. Anything else the
     * departing side put in the session goes with invalidate(), so the next
     * person at this browser cannot read it.
     */
    public function test_the_departing_sides_session_data_is_still_thrown_away(): void
    {
        $this->signIn('login', $this->shopper());
        $this->signIn('admin.login', $this->admin());

        $this->withSession(['checkout_note' => 'leave at the back door']);
        $this->assertSame('leave at the back door', session('checkout_note'));

        $this->asFreshRequest()->post(route('logout'))->assertRedirect('/');

        $this->assertNull(session('checkout_note'));
        $this->asFreshRequest()->get(route('admin.notifications'))->assertOk();
    }
}
