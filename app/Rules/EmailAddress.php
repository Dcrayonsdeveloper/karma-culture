<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The shape of an address someone is asking us to create an account on.
 *
 * Consumed through {@see ValidationRules::email()} with `strictShape: true`.
 *
 * `email:strict` already runs ahead of this and answers "is this legal RFC
 * mail?". The RFC is far wider than what any mail provider actually issues:
 * every one of `_ - + ! # $ % & ' * / = ? ^ ` { | } ~` is a legal opening
 * character for a local part, so "_@gmail.com" and "!!!@gmail.com" pass it
 * cleanly. Signup is where an address is minted rather than matched, so this
 * narrows the shape to the one people are actually given:
 *
 *   - the local part opens on a letter or a digit, never punctuation
 *   - after that it may carry . _ % + - and nothing else, and may not end on
 *     one of them
 *   - no ".." anywhere, on either side of the @
 *   - the domain is dot-separated labels of letters, digits and hyphens, each
 *     opening and closing on a letter or a digit, ending in a 2-63 letter TLD
 *
 * This is deliberately ASCII: an internationalised domain ("аsha@почта.рф")
 * is refused rather than half-checked. If that ever needs to change, punycode
 * the domain before this runs rather than widening the pattern.
 *
 * Apply it only where an address is being created. On a sign-in form it would
 * shut out anyone whose stored address predates the rule, which is exactly the
 * failure a stricter rule must never cause.
 */
class EmailAddress implements ValidationRule
{
    /** Opens and closes on a letter or digit; . _ % + - allowed between. */
    private const LOCAL = '/^[A-Za-z0-9](?:[A-Za-z0-9._%+\-]*[A-Za-z0-9])?$/';

    /** One or more hyphen-safe labels, then a letters-only TLD. */
    private const DOMAIN = '/^(?:[A-Za-z0-9](?:[A-Za-z0-9\-]*[A-Za-z0-9])?\.)+[A-Za-z]{2,63}$/';

    public const GENERIC = 'Enter a valid email address, like you@example.com.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(self::GENERIC);

            return;
        }

        $email = trim($value);

        // 'required' owns the empty case; saying it twice puts two sentences
        // under one box.
        if ($email === '') {
            return;
        }

        if (substr_count($email, '@') !== 1) {
            $fail(self::GENERIC);

            return;
        }

        [$local, $domain] = explode('@', $email);

        if ($local === '' || ! preg_match('/^[A-Za-z0-9]/', $local)) {
            $fail('An email address must start with a letter or a number.');

            return;
        }

        if (str_contains($email, '..')) {
            $fail('An email address cannot contain two dots in a row.');

            return;
        }

        if (! preg_match(self::LOCAL, $local)) {
            $fail('The part before the @ may only contain letters, numbers and . _ % + -');

            return;
        }

        if (! preg_match(self::DOMAIN, $domain)) {
            $fail(self::GENERIC);
        }
    }
}
