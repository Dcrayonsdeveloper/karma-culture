<?php

namespace Tests\Feature\Auth;

use App\Models\SignupEmailVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\VerifiesSignupEmails;
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
    use RefreshDatabase, VerifiesSignupEmails;

    /**
     * A payload the server would accept, INCLUDING the proof of the address.
     *
     * Signup now refuses any address that has not been verified, so a payload
     * valid in every other respect is no longer a valid payload. The proof is
     * minted here against whatever email the caller ends up with, so that the
     * tests below go on asking the questions they were written to ask instead
     * of all failing together on the same new gate. What that gate does on its
     * own is tested in SignupEmailVerificationTest.
     */
    private function validPayload(array $overrides = []): array
    {
        $payload = array_merge([
            '_register' => '1',
            'full_name' => 'Asha Menon',
            'email' => 'asha@example.test',
            'phone' => '9876543210',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ], $overrides);

        if (is_string($payload['email']) && $payload['email'] !== '') {
            $normalized = SignupEmailVerification::normalizeEmail($payload['email']);

            if ($normalized !== null && ! SignupEmailVerification::where('email', $normalized)->exists()) {
                $this->verifiedSignupEmail($payload['email']);
            }
        }

        return $payload;
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
        // Escaped, not raw: the headline now travels through {{ }} in
        // <x-form-errors>, so the apostrophe reaches the page as &#039;. The
        // default assertSee() escapes the needle the same way, which is what
        // makes this compare like with like.
        $page->assertSee("We couldn't create your account.");
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
        // The modal is a second, equally real signup form posting to the same
        // endpoint, so it is held to the same condition and carries the same
        // Validate Email step - see the kkSignupVerification block in
        // resources/views/partials/header.blade.php.
        $this->verifiedSignupEmail('modal@example.test');

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

    // ------------------------------------------------------------------
    // What the form asks for, and what it refuses
    // ------------------------------------------------------------------

    public function test_a_name_longer_than_thirty_characters_is_rejected(): void
    {
        // Alternating letters: a repeated one would trip PersonName's
        // keyboard-mashing guard and pass this test for the wrong reason.
        $response = $this->post('/register', $this->validPayload([
            'full_name' => str_repeat('ab', 16),
        ]));

        $response->assertSessionHasErrors('full_name');
        $this->assertSame(
            'Please keep your name to 30 characters or fewer.',
            session('errors')->first('full_name')
        );
        $this->assertDatabaseMissing('users', ['email' => 'asha@example.test']);
    }

    public function test_a_thirty_character_name_is_accepted(): void
    {
        $response = $this->post('/register', $this->validPayload([
            'full_name' => str_repeat('ab', 15),
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'asha@example.test']);
    }

    /**
     * All four of these are legal RFC mail, so `email:strict` alone lets them
     * through. Nobody is issued one.
     */
    public static function addressesOpeningOnASymbol(): array
    {
        return [
            'underscore' => ['_asha@example.test'],
            'dot' => ['.asha@example.test'],
            'hyphen' => ['-asha@example.test'],
            'plus' => ['+asha@example.test'],
        ];
    }

    #[DataProvider('addressesOpeningOnASymbol')]
    public function test_an_email_that_opens_on_a_symbol_is_rejected(string $email): void
    {
        $response = $this->post('/register', $this->validPayload(['email' => $email]));

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_an_email_with_two_dots_in_a_row_is_rejected(): void
    {
        $response = $this->post('/register', $this->validPayload([
            'email' => 'asha..menon@example.test',
        ]));

        $response->assertSessionHasErrors('email');
    }

    /**
     * The other half of the same rule: tightening the shape must not cost the
     * addresses people actually have.
     */
    public function test_an_ordinary_address_still_registers(): void
    {
        $response = $this->post('/register', $this->validPayload([
            'email' => 'asha.menon+shop@mail.example.test',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'asha.menon+shop@mail.example.test']);
    }

    /**
     * Three layers have to agree that a mobile number is ten digits. This is
     * the browser's: the value is capped as it is typed, so an eleventh digit
     * never lands and the shopper cannot submit the fifteen-digit string the
     * server would then have to explain.
     */
    public function test_the_signup_phone_field_is_held_to_ten_digits(): void
    {
        $html = $this->get('/login?mode=register')->assertStatus(200)->getContent();

        preg_match('/<input[^>]*id="phone"[^>]*>/', $html, $m);
        $this->assertNotEmpty($m, 'The mobile number field is missing from the register form.');
        $this->assertStringContainsString('data-kk-mobile="10"', $m[0],
            'The mobile field should cap itself at ten digits as it is typed.');
    }

    /**
     * The 30 is a hard stop in the browser, not a message after the fact.
     *
     * These boxes used to carry maxlength="100" deliberately, so a 31st
     * character was reported rather than swallowed. The field is now capped at
     * the number the server actually enforces: the box stops taking letters at
     * 30 instead of letting a longer name be typed out in full, submitted, and
     * handed straight back. All four places the signup name renders have to
     * agree, or a shopper meets a different rule depending on which one the
     * page happened to show them.
     */
    public function test_the_signup_name_field_stops_at_thirty_characters(): void
    {
        $html = $this->get('/login?mode=register')->assertStatus(200)->getContent();

        preg_match('/<input[^>]*name="full_name"[^>]*>/', $html, $m);
        $this->assertNotEmpty($m, 'The full name field is missing from the register form.');
        $this->assertStringContainsString('maxlength="30"', $m[0],
            'The signup name box must stop at 30 characters, the same limit '
            .'RegisterController::NAME_LIMIT holds.');

        // The storefront carries two more signup boxes - the header's login
        // modal and the layout's own - and neither is on /login, which renders
        // through the guest layout. A cap on this form alone would leave a
        // shopper who signs up from the header still able to type 40.
        $storefront = $this->get('/')->assertStatus(200)->getContent();

        preg_match('/<input[^>]*id="kk-auth-name"[^>]*>/s', $storefront, $header);
        $this->assertNotEmpty($header, 'The header login modal has no name field.');
        $this->assertStringContainsString('maxlength="30"', $header[0],
            'The header modal name box must be capped at 30 too.');

        preg_match('/<input[^>]*x-model="name"[^>]*>/s', $storefront, $layout);
        $this->assertNotEmpty($layout, 'The layout auth modal has no name field.');
        $this->assertStringContainsString('maxlength="30"', $layout[0],
            'The layout auth modal name box must be capped at 30 too.');
    }

    /**
     * The signup form's password boxes must not sit in a scope of their own.
     *
     * `x-ref` registers on the CLOSEST x-data root and `$refs` only walks up, so
     * an `x-data="{ show: false }"` on the wrapper put x-ref="password" out of
     * kkRegisterForm's reach. messageFor('password') then read '' whatever had
     * been typed and answered "Please choose a password." for every password in
     * the world - and because onSubmit() refuses to post while any field carries
     * a message, Create Account did nothing at all on this form. The eye toggles
     * are held on the component instead.
     */
    public function test_the_signup_password_boxes_are_in_the_forms_own_scope(): void
    {
        $html = $this->get('/login?mode=register')->assertStatus(200)->getContent();

        // The register panel only - the sign-in form above it has its own box
        // and no refs to lose.
        $panel = substr($html, (int) strpos($html, 'kkRegisterForm'));

        foreach (['reg_password', 'password_confirmation'] as $id) {
            preg_match('/<div class="relative"[^>]*>(?:(?!<\/div>).)*?id="'.$id.'"/s', $panel, $m);
            $this->assertNotEmpty($m, "The {$id} field is missing from the register form.");
            $this->assertStringNotContainsString(
                'x-data=',
                explode('>', $m[0])[0],
                "The wrapper around {$id} declares its own Alpine scope, which puts x-ref beyond "
                .'the reach of kkRegisterForm and breaks the password check for every signup.'
            );
        }

        $this->assertStringContainsString('x-ref="password"', $panel);
        $this->assertStringContainsString('x-ref="password_confirmation"', $panel);
    }

    public function test_the_confirm_password_field_can_be_revealed_like_the_password_field(): void
    {
        $html = $this->get('/login?mode=register')->assertStatus(200)->getContent();

        preg_match('/<input[^>]*id="password_confirmation"[^>]*>/', $html, $m);
        $this->assertNotEmpty($m, 'The confirm password field is missing from the register form.');
        // `showConfirm`, not `show`: the flag moved onto kkRegisterForm when the
        // per-wrapper x-data was removed - see
        // test_the_signup_password_boxes_are_in_the_forms_own_scope for why. It
        // is still a flag of its own, which is what this test is about.
        $this->assertStringContainsString(':type="showConfirm ? \'text\' : \'password\'"', $m[0],
            'Confirm Password needs the same eye toggle as Password: a typo in a box you '
            .'cannot read is the whole reason the field exists.');

        preg_match('/<input[^>]*id="reg_password"[^>]*>/', $html, $p);
        $this->assertNotEmpty($p, 'The password field is missing from the register form.');
        $this->assertStringContainsString(':type="showPassword ? \'text\' : \'password\'"', $p[0],
            'The two boxes must not share one flag: revealing the password you can already '
            .'see is not what the second box is for.');
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
