<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Plain text: no markup, no script, no smuggled URL scheme.
 *
 * Consumed through {@see ValidationRules::text()} and
 * {@see ValidationRules::textarea()}.
 *
 * This is defence in depth, not the escaping layer — Blade's {{ }} is what
 * actually stops XSS on output. What this buys is that hostile input never
 * reaches the database in the first place, so a field later rendered with
 * {!! !!} or piped into a PDF, an email or a CSV cannot carry a payload.
 *
 * Deliberately NOT for admin rich-text: those fields are HTML by design and
 * belong to safe_html() in app/helpers.php. Applying this rule to them would
 * reject every legitimate description.
 */
class NoHtml implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_scalar($value)) {
            $fail('The :attribute must be plain text.');

            return;
        }

        $value = (string) $value;

        // A tag opening: `<a`, `</p`, `<!--`. A lone `<` used as "less than"
        // is left alone, so "under <5 kg" still passes.
        if (preg_match('/<\s*[a-z!\/?]/i', $value)) {
            $fail('The :attribute may not contain HTML.');

            return;
        }

        // Entity-encoded tags, which survive a naive strip_tags() round trip.
        if (preg_match('/&(?:lt|#0*60|#x0*3c);\s*\/?\s*[a-z]/i', $value)) {
            $fail('The :attribute may not contain HTML.');

            return;
        }

        // Script-bearing schemes, tolerant of the whitespace/NUL padding used
        // to break up the token (`java\tscript:`).
        if (preg_match('/(?:j\s*a\s*v\s*a\s*s\s*c\s*r\s*i\s*p\s*t|v\s*b\s*s\s*c\s*r\s*i\s*p\s*t|d\s*a\s*t\s*a)\s*:/i', $value)) {
            $fail('The :attribute may not contain a script or data URL.');

            return;
        }

        // Inline event handlers, in case the text is ever interpolated into an
        // attribute rather than a text node.
        if (preg_match('/\son[a-z]+\s*=/i', $value)) {
            $fail('The :attribute may not contain HTML.');
        }
    }
}
