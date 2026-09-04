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

    /**
     * Registering over the API needs a proved address, like registering
     * anywhere else.
     *
     * This endpoint is the same act as the web form and is session-stateful in
     * a browser - Sanctum's EnsureFrontendRequestsAreStateful is prepended to
     * the api group and config/sanctum.php's stateful list includes this app's
     * own URL - so a verification gate on POST /register alone would be no gate:
     * skipping the emailed link would be a matter of changing the URL you post
     * to. The Origin header is what makes the request stateful, and therefore
     * what gives it the session its proof was claimed in.
     */
    public function test_api_register(): void
    {
        $this->verifiedSignupEmail('newuser@example.com');

        $response = $this->withHeaders(['Origin' => config('app.url')])
            ->postJson('/api/v1/auth/register', [
                'first_name' => 'Api',
                'last_name' => 'User',
                'email' => 'newuser@example.com',
                'phone' => '9876500011',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com', 'phone' => '9876500011']);
    }

    /**
     * A stateless caller cannot register, and that is the intended outcome.
     *
     * It has no session, so it holds no claim and can spend no proof - and it
     * has no way to obtain one either, because asking for a verification email
     * is a web route. A bearer-token client has therefore not lost a working
     * signup; it has been told plainly that this endpoint is part of the browser
     * flow. Building an API-native verification exchange is a separate piece of
     * work, and doing it badly here would mean leaving the endpoint able to
     * create accounts for addresses nobody has proved.
     */
    public function test_api_register_without_a_session_is_refused(): void
    {
        $this->verifiedSignupEmail('stateless@example.com');

        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Api',
            'last_name' => 'User',
            'email' => 'stateless@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(422)->assertJsonValidationErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'stateless@example.com']);
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
