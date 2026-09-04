<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\VerifiesSignupEmails;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase, VerifiesSignupEmails;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'api@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
    }

    public function test_api_register(): void
    {
        // The API is the same act of signing up as the web form and is held to
        // the same condition - it is session-stateful in a browser (Sanctum's
        // EnsureFrontendRequestsAreStateful is prepended to the api group), so
        // a gate on the web route alone would be no gate at all.
        $this->verifiedSignupEmail('newuser@example.com');

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Api',
            'last_name' => 'User',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_api_register_fails_with_existing_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Api',
            'last_name' => 'User',
            'email' => 'api@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_api_login(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'api@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['token']]);
    }

    public function test_api_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'api@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
    }

    public function test_api_logout(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
    }

    public function test_api_profile(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/profile');

        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => 'api@example.com']);
    }

    public function test_api_profile_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }
}
