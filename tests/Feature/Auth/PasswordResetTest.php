<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
    }

    public function test_reset_link_request_form_is_displayed(): void
    {
        $response = $this->get('/password/reset');

        $response->assertStatus(200);
    }

    public function test_reset_link_can_be_requested(): void
    {
        $response = $this->post('/password/email', [
            'email' => 'user@example.com',
        ]);

        $response->assertSessionHasNoErrors();
    }

    /**
     * An unknown address must be answered exactly like a known one.
     *
     * This test previously asserted the opposite - that an unregistered
     * address comes back with an error on the email field. That behaviour
     * turned the form into a membership oracle: submit a list of addresses,
     * keep the ones that do not error, and you have a confirmed customer list
     * for phishing or credential stuffing. The controller now returns the same
     * neutral status either way, so the assertion is inverted to lock that in.
     */
    public function test_reset_link_request_does_not_reveal_whether_the_account_exists(): void
    {
        $unknown = $this->post('/password/email', [
            'email' => 'nonexistent@example.com',
        ]);

        $unknown->assertSessionHasNoErrors();

        $known = $this->post('/password/email', [
            'email' => 'user@example.com',
        ]);

        $known->assertSessionHasNoErrors();

        // Same outcome for both, which is the whole point.
        $this->assertSame(
            $known->getStatusCode(),
            $unknown->getStatusCode(),
            'A known and an unknown address must be answered identically.'
        );
    }

    public function test_reset_link_request_still_rejects_a_malformed_email(): void
    {
        $response = $this->post('/password/email', [
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_password_reset_form_is_displayed(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->get('/password/reset/' . $token);

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasNoErrors();
    }
}
