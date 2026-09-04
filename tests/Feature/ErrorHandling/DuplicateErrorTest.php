<?php

namespace Tests\Feature\ErrorHandling;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The site must never answer one action with two errors.
 *
 * The bug these guard against was structural rather than cosmetic. Blade
 * printed the $errors bag as its own paragraphs, which the site-wide validator
 * in resources/js/app.js had no way to reach - it tracks the note it created on
 * the field itself - so the two renderers stacked. Empty the email box after a
 * rejected sign-in and the page showed
 *
 *     Email Address is required.                         (the browser, now)
 *     The provided credentials do not match our records.  (the server, last POST)
 *
 * two answers to one action, one of them about a value that was no longer on
 * screen.
 *
 * What is asserted here is the SERVER half of the contract, because that is the
 * half a request test can see: every field message is rendered exactly once, by
 * the one component, carrying the data-kk-field-error marker that makes it the
 * validator's to retire. Given that marker, the browser half - retire on edit,
 * retire on submit, never print a second message for a field - is what
 * app.js does with it.
 */
class DuplicateErrorTest extends TestCase
{
    use RefreshDatabase;

    private const CREDENTIALS_FAILED = 'The provided credentials do not match our records.';

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create([
            'email' => 'shopper@example.com',
            'password' => bcrypt('CorrectHorse!9xy'),
            'role' => 'customer',
        ]);
    }

    /** How many times a string occurs anywhere in the response body. */
    private function occurrences(string $html, string $needle): int
    {
        return substr_count($html, $needle);
    }

    /**
     * How many times a MESSAGE is visible in the response body.
     *
     * Attribute values are stripped first, and that is not a nicety. The signup
     * panel seeds its Alpine component with the server's messages -
     * x-data="kkRegisterForm({...'Please enter your full name.'...})" - so every
     * message it is about to render appears once in that attribute as well as
     * once in the paragraph it ends up in. Counting the raw HTML therefore
     * reports two of everything on that page and calls a correct render a
     * duplicate. What this asks is how many times a visitor can READ the
     * sentence, which is the thing under test.
     *
     * Use occurrences() for markup - data-kk-field-error and the like - because
     * that IS an attribute and this would strip it away.
     */
    private function visible(string $html, string $needle): int
    {
        $text = preg_replace('/\s[:@a-zA-Z][-.:\w]*="[^"]*"/', '', $html);

        return substr_count($text, $needle);
    }

    // ---------------------------------------------------------------- login

    public function test_wrong_credentials_render_exactly_one_field_message(): void
    {
        $html = $this->followingRedirects()
            ->from('/login')->post('/login', ['email' => 'shopper@example.com', 'password' => 'wrong'])
            ->assertStatus(200)
            ->getContent();

        $this->assertSame(
            1,
            $this->visible($html, self::CREDENTIALS_FAILED),
            'The credentials message must be printed once, not once inline and once in a banner.'
        );

        $this->assertSame(
            1,
            $this->occurrences($html, 'data-kk-field-error="email"'),
            'Exactly one marked note for the email field.'
        );
    }

    public function test_the_rendered_message_is_marked_for_the_client_validator(): void
    {
        $html = $this->followingRedirects()
            ->from('/login')->post('/login', ['email' => 'shopper@example.com', 'password' => 'wrong'])
            ->getContent();

        // The marker IS the fix: without it app.js cannot retire the message
        // when the visitor edits the field or starts a new submission, and the
        // stale sentence survives into the next attempt.
        $this->assertTrue(str_contains($html, 'data-kk-field-error="email"'), 'the note must be marked for app.js');
        $this->assertTrue(str_contains($html, 'kk-field-error'), 'and styled by the one stylesheet rule');

        // And the old unowned renderer is gone from this page.
        $this->assertFalse(
            str_contains($html, 'mt-2 text-sm text-red-600 flex items-center gap-1'),
            'the hand-rolled paragraph is still being rendered',
        );
    }

    public function test_an_empty_email_never_reaches_the_credentials_check(): void
    {
        // The server half of "do not call the API when validation fails": a
        // missing field is answered by the validator, so the guard is never
        // consulted and the credentials sentence is never generated. The
        // browser stops the request before this, but the two must agree - if
        // the server produced both messages, no amount of client-side work
        // could stop them arriving together for a visitor without JS.
        $response = $this->post('/login', ['email' => '', 'password' => '']);

        $response->assertSessionHasErrors(['email', 'password']);

        $errors = session('errors')->getBag('default');
        $this->assertNotContains(self::CREDENTIALS_FAILED, $errors->all());
        $this->assertGuest();
    }

    public function test_a_field_shows_one_message_even_when_it_breaks_two_rules(): void
    {
        // "not an email" and "too long" are both true of this value. The bag
        // holds both; the component prints the first, which is the highest
        // priority rule because Laravel orders by declaration.
        $html = $this->followingRedirects()
            ->from('/login')->post('/login', ['email' => str_repeat('a', 300), 'password' => 'x'])
            ->getContent();

        $this->assertSame(
            1,
            $this->occurrences($html, 'data-kk-field-error="email"'),
            'One note for the field, whatever the number of broken rules.'
        );
    }

    // ------------------------------------------------------------- register

    public function test_a_rejected_signup_does_not_print_each_message_twice(): void
    {
        // Two requests on purpose. followingRedirects() would run the GET too,
        // and the flashed bag is consumed by that render - so it is read here,
        // from the POST, and the page is fetched separately below.
        $this->from('/login')->post('/register', [
            '_register' => '1',
            'full_name' => '',
            'email' => 'not-an-email',
            'phone' => '',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertRedirect('/login');

        $errors = session('errors')->getBag('default');
        $this->assertTrue($errors->any(), 'the signup should have been rejected');

        // The banner above the form used to list the whole bag while the slots
        // under each field printed the same sentences again.
        $html = $this->get('/login')->assertStatus(200)->getContent();

        foreach (['full_name', 'email', 'phone', 'password'] as $field) {
            $message = $errors->first($field);
            if ($message === '') {
                continue;
            }

            $this->assertSame(
                1,
                $this->visible($html, e($message)),
                "The message for {$field} is printed twice: once in the banner and once under the field.",
            );
        }
    }

    public function test_the_signup_banner_keeps_only_what_no_field_shows(): void
    {
        $this->from('/login')->post('/register', [
            '_register' => '1',
            'full_name' => '',
            'email' => '',
            'phone' => '',
            'password' => '',
            'terms' => '',
        ])->assertRedirect('/login');

        $html = $this->get('/login')->getContent();

        // The headline survives - something failed and the visitor is told so -
        // but the list underneath it does not repeat the fields' own messages.
        // e() because the headline now travels through {{ }} in the component,
        // which escapes the apostrophe; the hand-rolled markup it replaced wrote
        // it straight into the HTML.
        $this->assertTrue(
            str_contains($html, e("We couldn't create your account.")),
            'the form-level headline should survive; only the repeated list goes',
        );
        $this->assertSame(1, $this->occurrences($html, 'data-kk-form-error'));
    }

    // ------------------------------------------------------- password reset

    public function test_reset_password_prints_each_message_under_its_own_field(): void
    {
        $this->from('/password/reset/a-token-that-was-never-issued')->post('/password/reset', [
            'token' => 'a-token-that-was-never-issued',
            'email' => 'shopper@example.com',
            'password' => 'short',
            'password_confirmation' => 'mismatched',
        ])->assertRedirect('/password/reset/a-token-that-was-never-issued');

        $errors = session('errors')->getBag('default');
        $this->assertTrue($errors->any());

        $html = $this->get('/password/reset/a-token-that-was-never-issued')->getContent();

        foreach ($errors->keys() as $key) {
            $this->assertSame(
                1,
                $this->visible($html, e($errors->first($key))),
                "The message for {$key} appears more than once on the reset page.",
            );
        }
    }

    public function test_an_expired_reset_link_says_so_once(): void
    {
        $html = $this->followingRedirects()->from('/password/reset/some-token')->post('/password/reset', [
            'token' => 'expired',
            'email' => 'shopper@example.com',
            'password' => 'CorrectHorse!9xy',
            'password_confirmation' => 'CorrectHorse!9xy',
        ])->getContent();

        $this->assertSame(
            1,
            $this->visible($html, 'This password reset link is invalid or has expired.'),
        );
    }

    // ------------------------------------------------------- forgot password

    public function test_forgot_password_answers_a_bad_address_with_one_message(): void
    {
        $html = $this->followingRedirects()
            ->from('/password/reset')->post('/password/email', ['email' => 'not-an-email'])
            ->getContent();

        $this->assertSame(1, $this->occurrences($html, 'data-kk-field-error="email"'));
    }

    // --------------------------------------------------------------- safety

    public function test_no_internal_detail_reaches_the_sign_in_page(): void
    {
        $html = $this->followingRedirects()
            ->from('/login')->post('/login', ['email' => 'shopper@example.com', 'password' => 'wrong'])
            ->getContent();

        foreach (['SQLSTATE', 'Stack trace', 'vendor/laravel', 'Illuminate\\Database'] as $leak) {
            $this->assertFalse(str_contains($html, $leak), "internal detail leaked: {$leak}");
        }
    }
}
