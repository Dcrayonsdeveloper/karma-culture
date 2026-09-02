<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An Indian mobile number: ten digits opening 6-9.
 *
 * Consumed through {@see ValidationRules::mobile()}.
 *
 * Customers type the number the way they read it — "+91 98765 43210",
 * "098765-43210", "(98765) 43210" — and all of those are the same subscriber.
 * The rule strips the decoration and the +91/91/0 trunk prefix first, then
 * validates what is left, so a correct number is never rejected for its
 * punctuation. Repdigits (9999999999) are refused: they are syntactically
 * legal and never real, and they are what people type to get past a form.
 *
 * Use {@see IndianMobile::normalize()} before persisting so the column holds
 * one canonical shape and lookups/uniqueness actually work.
 */
class IndianMobile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($value) || self::normalize((string) $value) === null) {
            $fail('Please enter a valid 10-digit mobile number starting with 6, 7, 8 or 9.');
        }
    }

    /**
     * Canonical form: the bare ten digits, which is what the users.phone and
     * address phone columns already hold. Returns null when the input is not a
     * valid Indian mobile number, so it doubles as the validity check.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Everything that is not a digit is decoration, including the + itself.
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        // Trunk and country prefixes, longest first.
        if (strlen($digits) === 13 && str_starts_with($digits, '091')) {
            $digits = substr($digits, 3);
        } elseif (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (! preg_match('/^[6-9]\d{9}$/', $digits)) {
            return null;
        }

        // 6666666666, 9999999999 — legal shape, never a subscriber.
        if (preg_match('/^(\d)\1{9}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    /**
     * E.164 form (+919876543210) for gateways that demand it — SMS, WhatsApp,
     * Shiprocket. Null when the number is not valid.
     */
    public static function toE164(?string $value): ?string
    {
        $normalized = self::normalize($value);

        return $normalized === null ? null : '+91'.$normalized;
    }
}
