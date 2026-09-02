<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A human name, in any script.
 *
 * Consumed through {@see ValidationRules::name()} rather than directly — that
 * helper is the documented entry point and adds the string/length rules this
 * object deliberately leaves out.
 *
 * The charset is an allowlist of Unicode letters and combining marks plus the
 * four separators names actually use (space, hyphen, apostrophe, period), so
 * "José", "O’Connor", "Mary-Anne", "रवि कुमार" and "山田太郎" all pass while
 * digits, angle brackets, emoji and punctuation soup do not. Combining marks
 * (\p{M}) are not optional: Devanagari matras and Vietnamese tone marks are
 * separate code points, and dropping them rejects those names outright.
 *
 * Two heuristics sit on top of the charset, because a charset alone cannot see
 * them: names that are really URLs ("evil.com" is all letters and a dot), and
 * keyboard mashing ("Aaaaaa"), which is the usual shape of junk signups.
 */
class PersonName implements ValidationRule
{
    /**
     * Must open with a letter, then letters/marks and separators only.
     * \x{00A0} is a non-breaking space — pasted names carry them in from Word.
     */
    private const CHARSET = '/^[\p{L}\p{M}][\p{L}\p{M}\x{0020}\x{00A0}\'\x{2019}.\-]*$/u';

    /** Five or more of the same letter in a row is not a name. */
    private const MASHED = '/(\p{L})\1{4,}/u';

    /** A scheme, a www. prefix, or a bare domain in a common TLD. */
    private const URLISH = '/(?:https?|ftp|file|javascript|data|vbscript):|(?:^|\s)www\.|\.(?:com|net|org|edu|gov|io|co|in|uk|ru|de|fr|xyz|info|biz|shop|online|site|top|club|live|app|dev)(?:\b|$)/iu';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid name.');

            return;
        }

        // TrimStrings has normally already done this; trimming again keeps the
        // rule honest when it is used outside an HTTP request (queues, imports).
        $value = trim($value);

        if ($value === '') {
            $fail('The :attribute field is required.');

            return;
        }

        if (! preg_match(self::CHARSET, $value)) {
            $fail('The :attribute may only contain letters, spaces, hyphens, apostrophes and periods.');

            return;
        }

        if (preg_match(self::URLISH, $value)) {
            $fail('The :attribute may not contain a web address.');

            return;
        }

        if (preg_match(self::MASHED, $value)) {
            $fail('Please enter a real name.');
        }
    }
}
