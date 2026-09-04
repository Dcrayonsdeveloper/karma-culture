<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifySignupEmail;
use App\Models\Admin;
use App\Models\SignupEmailVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\VerifiesSignupEmails;
use Tests\TestCase;

/**
 * Signing up proves the address first and creates the account second.
 *
 * The rule the whole flow rests on is that "verified" is a fact the SERVER
 * holds, about a specific address, in a row it wrote itself - never something a
 * request can assert. Most of what follows is that one sentence, tested from a
 * different angle each time.
 */
class SignupEmailVerificationTest extends TestCase
{
    use RefreshDatabase, VerifiesSignupEmails;

    private string $defaultGuard;

    protected function setUp(): void
    {
        parent::setUp();

        // Captured before any request runs. shouldUse() WRITES auth.defaults.guard
        // back into the config repository, so reading it later would read
        // whatever the last auth:admin request left there - which is the very
        // thing asFreshRequest() exists to undo.
        $this->defaultGuard = config('auth.defaults.guard');
    }

    /**
     * Leave the container the way a browser's next request would find it.
     *
     * actingAs($admin, 'admin') calls shouldUse('admin'), which writes
     * auth.defaults.guard into the config repository for the rest of the test.
     * A browser gets a fresh container per request, so it never carries that
     * across - but the test client reuses one, and the `guest` middleware on
     * these routes resolves the DEFAULT guard. Without this the admin looks
     * like the visitor and the request is bounced: a property of the harness,
     * not of the application. Copied from GuardSessionIsolationTest, which
     * found it first.
     */
    private function asFreshRequest(): static
    {
        $this->app['auth']->shouldUse($this->defaultGuard);
        $this->app['auth']->forgetGuards();

        return $this;
    }

    private function signupPayload(array $overrides = []): array
    {
        return array_merge([
            '_register' => '1',
            'full_name' => 'Asha Menon',
            'email' => 'asha@example.test',
            'phone' => '9876543210',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ], $overrides);
    }

    // ---- asking for the email ---------------------------------------------

    public function test_asking_to_validate_an_unused_address_sends_one_email(): void
    {
        Mail::fake();

        $this->postJson(route('signup.email-verifications.store'), ['email' => 'asha@example.test'])
            ->assertStatus(202)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('email', 'asha@example.test');

        Mail::assertSent(VerifySignupEmail::class, 1);
        // Nothing may be queued: production has no worker and no cron, so a
        // queued message is written to `jobs` and never delivered.
        Mail::assertNothingQueued();

        $this->assertDatabaseHas('signup_email_verifications', ['email' => 'asha@example.test']);
    }

    public function test_the_address_is_normalised_so_case_and_spacing_are_one_attempt(): void
    {
        Mail::fake();

        $this->postJson(route('signup.email-verifications.store'), ['email' => '  Asha@Example.Test  '])
            ->assertStatus(202)
            ->assertJsonPath('email', 'asha@example.test');

        $this->assertSame(1, SignupEmailVerification::count());
    }

    public function test_an_address_that_already_has_an_account_is_refused_and_no_email_is_sent(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'asha@example.test']);

        $this->postJson(route('signup.email-verifications.store'), ['email' => 'asha@example.test'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Email address already exists. Please log in or use another email.');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('signup_email_verifications', 0);
    }

    /** Case is not a way past the duplicate check. */
    public function test_a_registered_address_is_recognised_whatever_case_it_is_typed_in(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'asha@example.test']);

        $this->postJson(route('signup.email-verifications.store'), ['email' => 'ASHA@Example.TEST'])
            ->assertStatus(409);

        Mail::assertNothingSent();
    }

    /**
     * A soft-deleted account still owns its address as far as the unique index
     * is concerned, so promising the address would be promising an INSERT that
     * cannot happen.
     */
    public function test_a_soft_deleted_account_still_holds_its_address(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'asha@example.test'])->delete();

        $this->postJson(route('signup.email-verifications.store'), ['email' => 'asha@example.test'])
            ->assertStatus(409);

        Mail::assertNothingSent();
    }

    public function test_a_malformed_address_is_refused_before_anything_is_sent(): void
    {
        Mail::fake();

        $this->postJson(route('signup.email-verifications.store'), ['email' => '_asha@example.test'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        Mail::assertNothingSent();
    }

    // ---- what is, and is not, in the message -------------------------------

    public function test_the_email_carries_a_link_and_nothing_about_the_signup(): void
    {
        Mail::fake();

        $this->postJson(route('signup.email-verifications.store'), ['email' => 'asha@example.test'])
            ->assertStatus(202);

        Mail::assertSent(VerifySignupEmail::class, function (VerifySignupEmail $mail) {
            $body = (string) $mail->render();

            $this->assertStringNotContainsString('Password123!', $body);
            $this->assertStringNotContainsString('password', strtolower($mail->verificationUrl));
            $this->assertStringContainsString('/verify-email/', $mail->verificationUrl);

            return true;
        });
    }

    /**
     * bootstrap/app.php trusts every proxy and honours X-Forwarded-Host, and no
     * trusted-host list is registered - so a link built from the request host is
     * a link built by the caller. This endpoint posts that link to a real
     * person's inbox in a genuine message from this shop.
     */
    public function test_the_link_ignores_a_spoofed_forwarded_host(): void
    {
        Mail::fake();

        $this->withServerVariables(['HTTP_X_FORWARDED_HOST' => 'attacker.invalid'])
            ->postJson(route('signup.email-verifications.store'), ['email' => 'asha@example.test'])
            ->assertStatus(202);

        Mail::assertSent(VerifySignupEmail::class, function (VerifySignupEmail $mail) {
            $this->assertStringStartsWith(rtrim((string) config('app.url'), '/'), $mail->verificationUrl);
            $this->assertStringNotContainsString('attacker.invalid', $mail->verificationUrl);

            return true;
        });
    }

    /** The token in the inbox is not the token in the table. */
    public function test_the_emailed_token_is_never_stored_in_the_clear(): void
    {
        Mail::fake();

        $this->postJson(route('signup.email-verifications.store'), ['email' => 'asha@example.test']);

        $token = null;
        Mail::assertSent(VerifySignupEmail::class, function (VerifySignupEmail $mail) use (&$token) {
            $token = basename(parse_url($mail->verificationUrl, PHP_URL_PATH));

            return true;
        });

        $row = DB::table('signup_email_verifications')->where('email', 'asha@example.test')->first();

        $this->assertNotNull($token);
        $this->assertNotSame($token, $row->token_hash);
        $this->assertSame(hash('sha256', $token), $row->token_hash);
    }

    // ---- opening the link --------------------------------------------------

    public function test_opening_the_link_verifies_the_address(): void
    {
        [$attempt, $token] = $this->pendingSignupEmail('asha@example.test');

        $this->get(route('signup.verify-email', ['token' => $token]))
            ->assertOk()
            ->assertSee('Email verified successfully', false);

        $this->assertNotNull($attempt->fresh()->verified_at);
    }

    public function test_opening_the_link_twice_is_not_an_error(): void
    {
        [, $token] = $this->pendingSignupEmail('asha@example.test');

        $this->get(route('signup.verify-email', ['token' => $token]))->assertOk();

        $this->get(route('signup.verify-email', ['token' => $token]))
            ->assertOk()
            ->assertSee('already verified', false);
    }

    public function test_an_expired_link_says_so_rather_than_failing(): void
    {
        [, $token] = $this->pendingSignupEmail('asha@example.test', now()->subMinute());

        $this->get(route('signup.verify-email', ['token' => $token]))
            ->assertStatus(410)
            ->assertSee('expired', false);
    }

    public function test_a_token_that_was_never_issued_is_a_404_and_not_a_500(): void
    {
        $this->get(route('signup.verify-email', ['token' => Str::random(64)]))
            ->assertStatus(404)
            ->assertSee('not valid', false);
    }

    // ---- the form learning about it ---------------------------------------

    public function test_the_open_form_can_read_the_status_of_its_attempt(): void
    {
        [$attempt, $token] = $this->pendingSignupEmail('asha@example.test');

        $this->getJson(route('signup.email-verifications.show', ['uuid' => $attempt->uuid]))
            ->assertOk()
            ->assertJsonPath('status', 'pending');

        $this->get(route('signup.verify-email', ['token' => $token]))->assertOk();

        $this->getJson(route('signup.email-verifications.show', ['uuid' => $attempt->uuid]))
            ->assertOk()
            ->assertJsonPath('status', 'verified')
            ->assertJsonPath('email', 'asha@example.test');
    }

    /** A shared cache holding one "pending" would strand the form forever. */
    public function test_the_status_answer_is_never_cached(): void
    {
        [$attempt] = $this->pendingSignupEmail('asha@example.test');

        // Symfony reorders and de-duplicates the directives, so the assertion is
        // on the one that matters rather than on the whole string.
        $header = $this->getJson(route('signup.email-verifications.show', ['uuid' => $attempt->uuid]))
            ->assertOk()
            ->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', (string) $header);
    }

    public function test_the_status_endpoint_never_hands_out_the_token_hash(): void
    {
        [$attempt] = $this->pendingSignupEmail('asha@example.test');

        $this->getJson(route('signup.email-verifications.show', ['uuid' => $attempt->uuid]))
            ->assertOk()
            ->assertJsonMissing(['token_hash' => $attempt->token_hash]);
    }

    // ---- resend ------------------------------------------------------------

    public function test_a_second_request_inside_the_cooldown_is_refused(): void
    {
        Mail::fake();

        $this->postJson(route('signup.email-verifications.store'), ['email' => 'asha@example.test'])
            ->assertStatus(202);

        $this->postJson(route('signup.email-verifications.store'), ['email' => 'asha@example.test'])
            ->assertStatus(429)
            ->assertHeader('Retry-After');

        Mail::assertSent(VerifySignupEmail::class, 1);
    }

    public function test_a_resend_after_the_cooldown_rotates_the_token(): void
    {
        Mail::fake();

        $this->postJson(route('signup.email-verifications.store'), ['email' => 'asha@example.test']);

        $first = DB::table('signup_email_verifications')->where('email', 'asha@example.test')->value('token_hash');

        $this->travel(SignupEmailVerification::RESEND_COOLDOWN_SECONDS + 5)->seconds();

        $this->postJson(route('signup.email-verifications.store'), ['email' => 'asha@example.test'])
            ->assertStatus(202);

        $second = DB::table('signup_email_verifications')->where('email', 'asha@example.test')->value('token_hash');

        $this->assertNotSame($first, $second, 'A resend left the previous link working.');
        $this->assertSame(1, SignupEmailVerification::count(), 'A resend created a second attempt.');
    }

    /** One mailbox cannot be flooded however many addresses the caller wears. */
    public function test_one_address_cannot_be_mailed_without_limit(): void
    {
        Mail::fake();

        $sent = 0;

        for ($i = 0; $i < 10; $i++) {
            $status = $this->postJson(
                route('signup.email-verifications.store'),
                ['email' => 'asha@example.test'],
                ['REMOTE_ADDR' => '10.0.0.'.$i]
            )->getStatusCode();

            if ($status === 202) {
                $sent++;
            }

            $this->travel(SignupEmailVerification::RESEND_COOLDOWN_SECONDS + 5)->seconds();
        }

        $this->assertLessThanOrEqual(5, $sent, 'One address could be mailed without limit.');
    }

    // ---- creating the account ---------------------------------------------

    public function test_an_account_cannot_be_created_for_an_unproved_address(): void
    {
        $this->post('/register', $this->signupPayload())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'asha@example.test']);
    }

    /**
     * The whole point, stated once: the browser's opinion is not evidence.
     */
    public function test_claiming_to_be_verified_in_the_request_proves_nothing(): void
    {
        $this->post('/register', $this->signupPayload([
            'emailVerified' => true,
            'email_verified' => '1',
            'verified' => 'true',
            'email_verified_at' => now()->toDateTimeString(),
        ]))->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'asha@example.test']);
    }

    public function test_a_proved_address_can_create_its_account(): void
    {
        $this->verifiedSignupEmail('asha@example.test');

        $this->post('/register', $this->signupPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $user = User::where('email', 'asha@example.test')->firstOrFail();

        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('9876543210', $user->phone);
    }

    /** Proving one address does not prove another. */
    public function test_a_verification_for_one_address_cannot_create_an_account_for_a_different_one(): void
    {
        $this->verifiedSignupEmail('abc@example.test');

        $this->post('/register', $this->signupPayload(['email' => 'xyz@example.test']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'xyz@example.test']);
    }

    public function test_the_case_the_address_was_typed_in_does_not_matter_at_signup(): void
    {
        $this->verifiedSignupEmail('asha@example.test');

        $this->post('/register', $this->signupPayload(['email' => 'Asha@Example.Test']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'Asha@Example.Test']);
    }

    public function test_a_verification_is_spent_by_the_account_it_creates(): void
    {
        $attempt = $this->verifiedSignupEmail('asha@example.test');

        $this->post('/register', $this->signupPayload())->assertSessionHasNoErrors();

        $attempt->refresh();

        $this->assertNotNull($attempt->consumed_at);
        $this->assertNull($attempt->token_hash, 'The emailed link still resolves after the account was made.');
    }

    /** A spent proof cannot be used again, even after the first account is gone. */
    public function test_a_spent_verification_cannot_create_a_second_account(): void
    {
        $this->verifiedSignupEmail('asha@example.test');

        $this->post('/register', $this->signupPayload())->assertSessionHasNoErrors();

        User::where('email', 'asha@example.test')->firstOrFail()->forceDelete();

        $this->post('/register', $this->signupPayload())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_an_expired_verification_does_not_create_an_account(): void
    {
        $attempt = $this->verifiedSignupEmail('asha@example.test');
        $attempt->update(['expires_at' => now()->subMinute()]);

        $this->post('/register', $this->signupPayload())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'asha@example.test']);
    }

    // ---- the mobile number -------------------------------------------------

    public function test_a_mobile_number_that_already_has_an_account_is_refused(): void
    {
        User::factory()->create(['email' => 'other@example.test', 'phone' => '9876543210']);
        $this->verifiedSignupEmail('asha@example.test');

        $response = $this->post('/register', $this->signupPayload());

        $response->assertSessionHasErrors('phone');
        $this->assertSame(
            'Mobile number already exists. Please log in or use another number.',
            session('errors')->first('phone')
        );
        $this->assertDatabaseMissing('users', ['email' => 'asha@example.test']);
    }

    /** The same subscriber, typed differently, is still the same subscriber. */
    public function test_a_differently_formatted_number_is_the_same_number(): void
    {
        User::factory()->create(['email' => 'other@example.test', 'phone' => '9876543210']);
        $this->verifiedSignupEmail('asha@example.test');

        $this->post('/register', $this->signupPayload(['phone' => '+91 98765-43210']))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('users', ['email' => 'asha@example.test']);
    }

    public function test_a_soft_deleted_account_still_holds_its_mobile_number(): void
    {
        User::factory()->create(['email' => 'other@example.test', 'phone' => '9876543210'])->delete();
        $this->verifiedSignupEmail('asha@example.test');

        $this->post('/register', $this->signupPayload())
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('users', ['email' => 'asha@example.test']);
    }

    // ---- losing the race ---------------------------------------------------

    /**
     * Two signups for one address, and only one row.
     *
     * The insert is what settles it, not the check before it: everything above
     * can pass and still be out of date by the time the INSERT lands. The
     * collision is forced deterministically here by writing the conflicting row
     * from inside the transaction, immediately before the real insert - which is
     * exactly the window a real race occupies.
     */
    public function test_a_lost_race_on_the_email_is_a_conflict_and_not_a_500(): void
    {
        $this->verifiedSignupEmail('asha@example.test');

        $this->raceInAConflicting(['email' => 'asha@example.test', 'phone' => '9000000001']);

        $response = $this->postJson('/register', $this->signupPayload());

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Email address already exists. Please log in or use another email.');

        // The loser writes nothing. The winner's row is not asserted on here
        // because it was written inside the transaction this request rolled
        // back - in production it belongs to a different connection that has
        // already committed, which is precisely why our INSERT failed.
        $this->assertSame(0, User::where('first_name', 'Asha')->count());
    }

    public function test_a_lost_race_on_the_mobile_is_a_conflict_and_not_a_500(): void
    {
        $this->verifiedSignupEmail('asha@example.test');

        $this->raceInAConflicting(['email' => 'someone.else@example.test', 'phone' => '9876543210']);

        $this->postJson('/register', $this->signupPayload())
            ->assertStatus(409)
            ->assertJsonPath('message', 'Mobile number already exists. Please log in or use another number.');

        $this->assertDatabaseMissing('users', ['email' => 'asha@example.test']);
    }

    /** A form post loses the race the way a form post loses anything: inline. */
    public function test_a_lost_race_on_a_form_post_comes_back_to_the_form(): void
    {
        $this->verifiedSignupEmail('asha@example.test');

        $this->raceInAConflicting(['email' => 'asha@example.test', 'phone' => '9000000002']);

        $this->post('/register', $this->signupPayload())
            ->assertRedirect()
            ->assertSessionHasErrors('email');
    }

    /**
     * Slip a conflicting row in between the last check and the insert.
     *
     * `creating` fires inside RegisterController's transaction, after every
     * uniqueness check has passed, and the row written here is committed by the
     * same connection - so the INSERT that follows hits a live duplicate key.
     * Once only: the conflicting row is itself a User::create.
     */
    private function raceInAConflicting(array $attributes): void
    {
        $armed = true;

        User::creating(function () use (&$armed, $attributes) {
            if (! $armed) {
                return;
            }

            $armed = false;

            DB::table('users')->insert(array_merge([
                'uuid' => (string) Str::uuid(),
                'first_name' => 'Race',
                'last_name' => 'Winner',
                'password' => bcrypt('Password123!'),
                'role' => 'customer',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ], $attributes));
        });
    }

    // ---- both signup forms -------------------------------------------------

    /**
     * The storefront has TWO live signup forms, and the gate is on the endpoint
     * they share.
     *
     * /login?mode=register is one; the header's Login/Sign Up modal is the other
     * and posts JSON to the same route. Adding the requirement without adding
     * the control to both would leave the second one permanently unable to
     * create an account, with a message about a step it does not offer.
     */
    public function test_the_signup_page_offers_the_validate_email_control(): void
    {
        $html = $this->get('/login?mode=register')->assertOk()->getContent();

        $this->assertStringContainsString('requestVerification()', $html);
        $this->assertStringContainsString('verifyButtonLabel', $html);
        $this->assertStringContainsString('Email Validated', $html);
        $this->assertMatchesRegularExpression(
            '/<button[^>]*type="submit"[^>]*:disabled="!canSubmit"/s',
            $html,
            'Create Account is not gated on the verified address.'
        );
    }

    public function test_the_header_signup_modal_offers_the_validate_email_control(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('kk-loginmodal__validate', $html);
        $this->assertStringContainsString('Email Validated', $html);
        $this->assertStringContainsString('window.kkWithSignupVerification', $html);
        $this->assertStringContainsString(
            ":disabled=\"loading || (mode === 'signup' && !emailVerified)\"",
            $html,
            'The modal Create Account button is not gated on the verified address.'
        );
    }

    /** Both forms are pointed at the real endpoints, not at a stale path. */
    public function test_both_signup_forms_point_at_the_verification_endpoints(): void
    {
        // Both blades pass the routes through @js, which emits
        // JSON.parse('...') with the slashes escaped twice over - once for the
        // JSON and once for the JS string literal - so the assertion is on the
        // path segment rather than on a URL that is not literally in the page.
        foreach (['/login?mode=register', '/'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertStringContainsString('email-verifications', $html, "No verification endpoint on {$path}.");
            $this->assertStringContainsString('__ID__', $html, "No status template on {$path}.");
        }
    }

    /**
     * A signup rejected for the MOBILE number keeps its verified address.
     *
     * The form comes back with the address refilled and the Alpine component
     * brand new, so it has no memory of the tick. The server does, and says so
     * when it re-renders the page - otherwise the customer would have to prove
     * an address they have already proved just to correct their phone number.
     */
    public function test_a_rejected_signup_comes_back_with_its_address_still_proved(): void
    {
        User::factory()->create(['email' => 'other@example.test', 'phone' => '9876543210']);
        $this->verifiedSignupEmail('asha@example.test');

        $this->from(route('login', ['mode' => 'register']))
            ->post('/register', $this->signupPayload())
            ->assertSessionHasErrors('phone');

        $html = $this->followingRedirects()
            ->from(route('login', ['mode' => 'register']))
            ->post('/register', $this->signupPayload())
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'asha@example.test',
            $html,
            'The rejected signup did not carry its proved address back to the form.'
        );

        // The address is handed to the component as its own argument, between
        // the server errors and the routes.
        $this->assertMatchesRegularExpression(
            '/kkRegisterForm\(.*asha@example\.test/s',
            $html,
            'The proved address was not passed into kkRegisterForm, so the tick will not come back.'
        );
    }

    // ---- the admin's session -----------------------------------------------

    /**
     * Admin and customer share one session cookie and one sessions row - only
     * the per-guard key separates them - so anything in the storefront that
     * flushed the session would sign an admin out of the same browser. Nothing
     * in this flow touches the session at all, and this says so.
     */
    public function test_the_verification_flow_leaves_an_admin_signed_in(): void
    {
        Mail::fake();

        // Signed in the way a browser does it, not with actingAs: the property
        // under test is what happens to the SESSION, and actingAs never writes
        // one.
        $admin = User::factory()->create(['role' => 'admin', 'password' => Hash::make('correct-horse-battery')]);
        Admin::create(['user_id' => $admin->id, 'role' => 'super_admin', 'is_active' => true]);

        $this->asFreshRequest()->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => 'correct-horse-battery',
        ])->assertRedirect();

        $this->asFreshRequest()->get(route('admin.notifications'))->assertOk();

        $this->asFreshRequest()
            ->postJson(route('signup.email-verifications.store'), ['email' => 'asha@example.test'])
            ->assertStatus(202);

        $attempt = SignupEmailVerification::where('email', 'asha@example.test')->firstOrFail();

        $this->asFreshRequest()
            ->getJson(route('signup.email-verifications.show', ['uuid' => $attempt->uuid]))
            ->assertOk();

        [, $token] = $this->pendingSignupEmail('someone.else@example.test');
        $this->asFreshRequest()->get(route('signup.verify-email', ['token' => $token]))->assertOk();

        $this->asFreshRequest()->get(route('admin.notifications'))->assertOk(
            'A customer signing up signed the admin out of the same browser.'
        );
    }
}
