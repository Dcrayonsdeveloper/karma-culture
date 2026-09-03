<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The half of "forgot password" that the existing tests never looked at.
 *
 * PasswordResetTest asserts that the endpoints answer without validation
 * errors, which they did even while production was configured with the `log`
 * mailer and no customer ever received anything. A green suite and a feature
 * nobody can actually use are not supposed to be compatible, so these tests
 * follow the mail itself: that a reset request really dispatches a
 * notification, that the link inside it opens the reset form, and that the
 * password on the account genuinely changes afterwards.
 */
class PasswordResetEmailTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_PASSWORD = 'OldPassword123!';

    private const NEW_PASSWORD = 'BrandNewPassword456!';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'shopper@example.com',
            'password' => Hash::make(self::OLD_PASSWORD),
            'is_active' => true,
        ]);
    }

    public function test_requesting_a_reset_actually_dispatches_the_email(): void
    {
        Notification::fake();

        $this->post('/password/email', ['email' => 'shopper@example.com'])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($this->user, ResetPasswordNotification::class);
    }

    /**
     * The shop's own notification, not Illuminate's stock one.
     *
     * The override lives on the User model, which is a single line that a
     * later edit could drop without anything else failing - the framework
     * would quietly fall back to its generic email and nobody would notice
     * until a customer asked why the mail looked like a phishing attempt.
     */
    public function test_the_reset_email_is_the_shops_own_branded_one(): void
    {
        Notification::fake();

        $this->post('/password/email', ['email' => 'shopper@example.com']);

        Notification::assertSentTo(
            $this->user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) {
                $mail = $notification->toMail($this->user);
                $rendered = (string) $mail->render();

                // Wording that only exists in resources/views/emails/reset-password.blade.php.
                // Illuminate's stock notification opens "You are receiving this
                // email because we received a password reset request for your
                // account.", so either of these failing means the override on
                // the User model has stopped taking effect.
                $this->assertStringContainsString('Reset Your Password', $rendered);
                $this->assertStringContainsString('If You Did Not Request This', $rendered);

                $this->assertStringContainsString(
                    'Hi '.$this->user->first_name,
                    $rendered,
                    'The email should greet the customer by name, as the rest of our mail does.'
                );

                // The address rides in the query string, where @ is percent-encoded.
                $this->assertStringContainsString(
                    'email='.rawurlencode('shopper@example.com'),
                    $mail->viewData['url'],
                    'The link has to carry the address back, or the reset form posts a blank email.'
                );

                return true;
            }
        );
    }

    /**
     * Nothing is sent for an address with no account.
     *
     * The endpoint answers identically either way on purpose, so the only
     * place this can be checked is at the notification layer.
     */
    public function test_no_email_goes_out_for_an_unknown_address(): void
    {
        Notification::fake();

        $this->post('/password/email', ['email' => 'stranger@example.com'])
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    /**
     * Follow the link out of the email rather than a hand-made token.
     *
     * PasswordResetTest builds its own token with Password::createToken, which
     * would keep passing even if the URL in the email were malformed. This
     * takes the URL the customer is actually given.
     */
    public function test_the_link_in_the_email_opens_the_reset_form(): void
    {
        Notification::fake();

        $this->post('/password/email', ['email' => 'shopper@example.com']);

        $url = $this->capturedResetUrl();

        $this->assertStringContainsString('/password/reset/', $url);
        $this->assertStringContainsString('email=', $url);

        $this->get($url)->assertOk()->assertSee('shopper@example.com', false);
    }

    public function test_the_password_really_changes_and_the_old_one_stops_working(): void
    {
        Notification::fake();

        $this->post('/password/email', ['email' => 'shopper@example.com']);

        $token = $this->capturedResetToken();

        $this->post('/password/reset', [
            'token' => $token,
            'email' => 'shopper@example.com',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertSessionHasNoErrors();

        $this->user->refresh();

        $this->assertTrue(
            Hash::check(self::NEW_PASSWORD, $this->user->password),
            'The new password was not written to the account.'
        );

        $this->assertFalse(
            Hash::check(self::OLD_PASSWORD, $this->user->password),
            'The old password still works after a reset.'
        );
    }

    public function test_the_customer_can_log_in_with_the_new_password_afterwards(): void
    {
        Notification::fake();

        $this->post('/password/email', ['email' => 'shopper@example.com']);

        $this->post('/password/reset', [
            'token' => $this->capturedResetToken(),
            'email' => 'shopper@example.com',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertSessionHasNoErrors();

        $this->post('/login', [
            'email' => 'shopper@example.com',
            'password' => self::OLD_PASSWORD,
        ])->assertSessionHasErrors();

        $this->assertGuest();

        $this->post('/login', [
            'email' => 'shopper@example.com',
            'password' => self::NEW_PASSWORD,
        ]);

        $this->assertAuthenticatedAs($this->user);
    }

    /**
     * A used token cannot be replayed.
     *
     * Worth pinning because the reset link travels through a mailbox, which is
     * exactly where an old one sits around waiting to be found.
     */
    public function test_a_reset_token_cannot_be_used_twice(): void
    {
        Notification::fake();

        $this->post('/password/email', ['email' => 'shopper@example.com']);

        $token = $this->capturedResetToken();

        $payload = [
            'token' => $token,
            'email' => 'shopper@example.com',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ];

        $this->post('/password/reset', $payload)->assertSessionHasNoErrors();

        $this->post('/password/reset', [
            ...$payload,
            'password' => 'ThirdPassword789!',
            'password_confirmation' => 'ThirdPassword789!',
        ])->assertSessionHasErrors(['email']);

        $this->user->refresh();

        $this->assertTrue(
            Hash::check(self::NEW_PASSWORD, $this->user->password),
            'The second use of a spent token changed the password anyway.'
        );
    }

    /** Pull the reset URL out of the notification the customer was sent. */
    private function capturedResetUrl(): string
    {
        $url = null;

        Notification::assertSentTo(
            $this->user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$url) {
                $url = $notification->toMail($this->user)->viewData['url'] ?? null;

                return true;
            }
        );

        $this->assertIsString($url, 'The reset email carried no link.');

        return $url;
    }

    private function capturedResetToken(): string
    {
        $path = parse_url($this->capturedResetUrl(), PHP_URL_PATH) ?: '';
        $token = basename($path);

        $this->assertNotSame('', $token, 'No token in the reset link.');

        return $token;
    }
}
