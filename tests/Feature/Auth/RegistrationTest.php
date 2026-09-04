<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\VerifiesSignupEmails;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase, VerifiesSignupEmails;

    public function test_registration_form_is_displayed(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('login', ['mode' => 'register']));
    }

    public function test_new_user_can_register(): void
    {
        // Signup will not create an account for an address nobody has proved
        // they can read mail at. The proof is a row, not a request field - see
        // Tests\Concerns\VerifiesSignupEmails.
        $this->verifiedSignupEmail('test@example.com');

        $response = $this->post('/register', [
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            // Both of these are required server-side, so a valid payload has to
            // carry them: terms is enforced rather than merely marked in the
            // form, and a mobile number is now mandatory at signup.
            'phone' => '9876543210',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);

        // The address was proved before the row existed, so the account starts
        // verified rather than being mailed a second link asking it to do again
        // what it has just done.
        $this->assertNotNull(User::where('email', 'test@example.com')->first()->email_verified_at);
    }

    public function test_registration_fails_with_missing_required_fields(): void
    {
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors(['full_name', 'email', 'phone', 'password']);
    }

    public function test_registration_fails_with_invalid_email(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'not-an-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_registration_fails_with_password_mismatch(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword!',
        ]);

        $response->assertSessionHasErrors(['password']);
    }
}
