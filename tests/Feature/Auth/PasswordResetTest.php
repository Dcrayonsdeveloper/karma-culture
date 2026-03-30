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

    public function test_reset_link_request_fails_for_invalid_email(): void
    {
        $response = $this->post('/password/email', [
            'email' => 'nonexistent@example.com',
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
