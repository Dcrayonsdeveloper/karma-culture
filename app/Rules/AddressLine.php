<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A postal address line.
 *
 * Consumed through {@see ValidationRules::addressLine()}.
 *
 * Wider than {@see PersonName} — an address is mostly digits and punctuation
 * ("Flat 3B, #12/4 M.G. Road (Opp. Metro)") — but still an allowlist, so the
 * markup and scheme checks that matter are structural rather than a blocklist
 * of payloads. Unicode letters are in, because Indian addresses are routinely
 * written in Devanagari, Tamil or Bengali.
 */
class AddressLine implements ValidationRule
{
    private const CHARSET = "/^[\p{L}\p{M}\p{N}\x{0020}\x{00A0}\/\-,.#'\x{2019}()&:;+*_\r\n]+$/u";

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_scalar($value)) {
            $fail('The :attribute must be a valid address.');

            return;
        }

        $value = trim((string) $value);

        if ($value === '') {
            $fail('The :attribute field is required.');

            return;
        }

        // Must carry at least one letter or digit: ",,,," and "---" are not
        // addresses, and the charset alone would admit them.
        if (! preg_match('/[\p{L}\p{N}]/u', $value)) {
            $fail('Please enter a valid :attribute.');

            return;
        }

        if (! preg_match(self::CHARSET, $value)) {
            $fail('The :attribute contains characters that are not allowed.');

            return;
        }

        // The charset already excludes < > and quotes, so anything reaching
        // here is scheme-shaped rather than tag-shaped.
        (new NoHtml)->validate($attribute, $value, $fail);
    }
}
