<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "Create Account" button silently did nothing: the terms checkbox was
 * `required` on an `sr-only` element, and Chrome refuses to focus a clipped
 * control to show its validation bubble, so it aborted the submit with no
 * message. These lock in both halves of the fix — the control stays focusable,
 * and the requirement is enforced server-side rather than by markup alone.
 */
class RegistrationFormTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
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

    public function test_a_complete_signup_creates_the_account(): void
    {
        $response = $this->post('/register', $this->validPayload());

        $response->assertRedirect(route('login'));
        $response->assertSessionHasNoErrors();

        $user = User::where('email', 'asha@example.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('Asha', $user->first_name);
        $this->assertSame('Menon', $user->last_name);
        $this->assertSame('customer', $user->role);
    }

    public function test_signup_without_accepting_terms_is_rejected_with_a_message(): void
    {
        $response = $this->post('/register', $this->validPayload(['terms' => null]));

        $response->assertSessionHasErrors('terms');
        $this->assertSame(
            'Please accept the Terms and Privacy Policy to continue.',
            session('errors')->first('terms')
        );
        $this->assertDatabaseMissing('users', ['email' => 'asha@example.test']);
    }

    public function test_signup_requires_a_mobile_number(): void
    {
        $response = $this->post('/register', $this->validPayload(['phone' => '']));

        $response->assertSessionHasErrors('phone');
        $this->assertSame('Please enter your mobile number.', session('errors')->first('phone'));
        $this->assertDatabaseMissing('users', ['email' => 'asha@example.test']);
    }

    public function test_a_number_that_is_not_an_indian_mobile_is_rejected(): void
    {
        // Right length, wrong opening digit - landline and 1-5 ranges are not
        // mobile numbers, which is the whole point of the [6-9] leading digit.
        $response = $this->post('/register', $this->validPayload(['phone' => '1234567890']));

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseMissing('users', ['email' => 'asha@example.test']);
    }

    public function test_a_mobile_number_is_stored_as_bare_digits_however_it_was_typed(): void
    {
        $response = $this->post('/register', $this->validPayload(['phone' => '+91 98765-43210']));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'asha@example.test', 'phone' => '9876543210']);
    }

    public function test_mismatched_confirmation_is_rejected(): void
    {
        $response = $this->post('/register', $this->validPayload([
            'password_confirmation' => 'SomethingElse123!',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'asha@example.test']);
    }

    public function test_a_rejected_signup_reopens_the_form_in_register_mode_showing_the_error(): void
    {
        // from() seeds the previous URL that a validation failure redirects back
        // to; in a browser the GET /login does that.
        $page = $this->from('/login?mode=register')
            ->followingRedirects()
            ->post('/register', $this->validPayload(['terms' => null]));

        $page->assertStatus(200);
        // The Alpine component must come back in register mode, not login mode,
        // or the customer never sees why the submit failed.
        $page->assertSee("mode: 'register'", false);
        $page->assertSee("We couldn't create your account.", false);
        $page->assertSee('Please accept the Terms and Privacy Policy to continue.');
    }

    /**
     * The whole guest group shared one `throttle:10,1`, and the guest limiter
     * keys on domain|ip without the URI — so simply viewing the login page a
     * few times used up the budget for registering.
     */
    public function test_viewing_the_auth_pages_is_not_rate_limited(): void
    {
        foreach (range(1, 25) as $i) {
            $this->get('/login')->assertStatus(200);
        }
    }

    public function test_registering_is_still_rate_limited(): void
    {
        $hitLimit = false;

        foreach (range(1, 20) as $i) {
            $status = $this->post('/register', $this->validPayload([
                'email' => "flood{$i}@example.test",
            ]))->status();

            if ($status === 429) {
                $hitLimit = true;
                break;
            }
        }

        $this->assertTrue($hitLimit, 'POST /register should still be throttled.');
    }

    /**
     * The header modal has no Terms checkbox — its consent is the "By
     * continuing you agree..." notice — and it posts JSON. Laravel's `accepted`
     * rule fails on an ABSENT field, so requiring terms server-side broke that
     * signup path with an error keyed to an input the modal never renders.
     */
    public function test_the_header_modal_json_signup_still_works(): void
    {
        $response = $this->postJson('/register', [
            'full_name' => 'Modal User',
            'email' => 'modal@example.test',
            // The modal collects a mobile number too, now that one is required.
            'phone' => '9812345678',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', ['email' => 'modal@example.test']);
    }

    public function test_a_json_signup_missing_terms_returns_a_readable_field_error(): void
    {
        $response = $this->postJson('/register', [
            'full_name' => 'Modal User',
            'email' => 'modal2@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('terms');
        $this->assertDatabaseMissing('users', ['email' => 'modal2@example.test']);
    }

    /**
     * The regression itself: a `required` control that the browser cannot focus
     * makes Chrome abort submission silently. Guard the exact markup shape.
     */
    public function test_the_terms_checkbox_is_a_focusable_control(): void
    {
        $html = $this->get('/login?mode=register')->assertStatus(200)->getContent();

        $this->assertMatchesRegularExpression(
            '/<input[^>]*name="terms"[^>]*>/',
            $html,
            'The terms checkbox is missing from the register form.'
        );

        preg_match('/<input[^>]*name="terms"[^>]*>/', $html, $m);
        $input = $m[0];

        $this->assertStringNotContainsString('sr-only', $input,
            'The terms checkbox must not be sr-only: Chrome cannot focus a clipped control to '
            .'report its validation error, so the form aborts submission with no message.');
        $this->assertStringNotContainsString('type="hidden"', $input);
        $this->assertStringContainsString('required', $input,
            'The checkbox should still be required — it just has to be focusable.');
        $this->assertStringContainsString('appearance-none', $input,
            'The custom styling should live on the input itself now.');
    }
}
