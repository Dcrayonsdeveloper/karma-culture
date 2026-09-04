<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * The verification link, and the host it points at.
 *
 * The password-reset fix anchored its own link to config('app.url') and named
 * this as the twin it was leaving open: the app trusts proxies with `at: '*'`,
 * honours X-Forwarded-Host and registers no trusted-host list, so the signed
 * verification URL was built from a host the caller supplies. Anyone could post
 * the registration form with somebody else's address and their own forwarded
 * host, and that person would be sent a genuine, signed "verify your email"
 * from this shop pointing somewhere else.
 *
 * It never mattered while MAIL_MAILER=log ate the message. It does now.
 */
class EmailVerificationLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_verification_link_ignores_a_spoofed_forwarded_host(): void
    {
        Notification::fake();

        // The fixture used to be POST /register, which fired Registered and so
        // sent this notification. Signup proves the address BEFORE the account
        // exists now, so a new account is already verified and the framework's
        // listener stands itself down - there is no longer a VerifyEmail to
        // capture from that route. The subject of this test is the URL builder,
        // not registration, so it asks for the notification the way the other
        // three tests in this file do. What signup itself now does about the
        // host is covered in SignupEmailVerificationTest.
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->withServerVariables(['HTTP_X_FORWARDED_HOST' => 'attacker.invalid'])
            ->get('/');

        $user->sendEmailVerificationNotification();

        $url = $this->capturedVerificationUrl();

        $this->assertStringStartsWith(
            rtrim((string) config('app.url'), '/'),
            $url,
            'The verification link was built from a caller-supplied host.'
        );
        $this->assertStringNotContainsString('attacker.invalid', $url);
    }

    /**
     * Pinning the host must not cost the link its signature.
     *
     * The route is behind `signed`, which recomputes over the absolute URL, so
     * a signature taken over a relative path - or over http when the shop is
     * https - would turn every verification link into a 403.
     */
    public function test_the_verification_link_still_validates_on_the_real_host(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);
        $user->sendEmailVerificationNotification();

        $url = $this->capturedVerificationUrl();

        $this->assertTrue(
            URL::hasValidSignature(\Illuminate\Http\Request::create($url, 'GET')),
            'The pinned verification link does not validate - every customer would get a 403.'
        );
    }

    /** And the same signature is worth nothing anywhere else. */
    public function test_the_signature_does_not_carry_to_another_host(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);
        $user->sendEmailVerificationNotification();

        $elsewhere = str_replace(
            parse_url((string) config('app.url'), PHP_URL_HOST),
            'attacker.invalid',
            $this->capturedVerificationUrl()
        );

        $this->assertFalse(
            URL::hasValidSignature(\Illuminate\Http\Request::create($elsewhere, 'GET')),
            'A verification signature validated on a host we do not own.'
        );
    }

    /**
     * Every hit on the resend route sends a real message over one shared Gmail
     * app password with one daily quota, so it cannot be free to call.
     */
    public function test_resending_the_verification_email_is_throttled(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);

        $last = null;
        for ($i = 0; $i < 8; $i++) {
            $last = $this->actingAs($user)->post(route('verification.resend'));
        }

        $this->assertSame(
            429,
            $last->getStatusCode(),
            'The resend route sent eight emails without complaint - the send quota is unmetered.'
        );
    }

    private function capturedVerificationUrl(): string
    {
        $url = null;

        Notification::assertSentTo(
            User::whereNotNull('id')->latest('id')->firstOrFail(),
            VerifyEmail::class,
            function (VerifyEmail $notification, array $channels, object $notifiable) use (&$url) {
                $url = $notification->toMail($notifiable)->actionUrl;

                return true;
            }
        );

        $this->assertNotNull($url, 'No verification link was generated.');

        return $url;
    }
}
