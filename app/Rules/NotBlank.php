<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects a value that is only whitespace.
 *
 * Consumed automatically by the required variants of
 * {@see ValidationRules::text()} and {@see ValidationRules::textarea()}.
 *
 * In an HTTP request this is belt and braces: TrimStrings collapses "   " to ""
 * and ConvertEmptyStringsToNull turns that into null, which 'required' already
 * catches. It matters when the same rule set is reused where that middleware
 * does not run - console commands, queued jobs, CSV imports, API clients
 * posting JSON to routes that skip the web stack - so `min:1` cannot be
 * satisfied by three spaces.
 *
 * Non-breaking spaces and zero-width characters count as blank: they are what
 * gets pasted in to defeat a naive trim().
 */
class NotBlank implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        // \s plus NBSP, zero-width space/joiner and the BOM.
        $stripped = preg_replace('/[\s\x{00A0}\x{200B}-\x{200D}\x{FEFF}]+/u', '', $value);

        if ($stripped === '' || $stripped === null) {
            $fail('The :attribute field is required.');
        }
    }
}
