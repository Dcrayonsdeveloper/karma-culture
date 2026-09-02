<?php

namespace App\Rules;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The shared rule sets for every user-writable field in the app.
 *
 * STYLE - there is one, and this is it. Every method returns a plain array of
 * Laravel rules, ready to drop straight into an existing `$request->validate()`
 * call. There is no FormRequest layer to learn and no new base class to extend;
 * the codebase already validates inline and this fits that shape exactly:
 *
 *     use App\Rules\ValidationRules as V;
 *
 *     $validated = $request->validate([
 *         'first_name' => V::name(),
 *         'email'      => [...V::email(), 'unique:users'],
 *         'phone'      => V::mobile(required: false),
 *         'message'    => V::textarea(max: 1000),
 *     ]);
 *
 * Every method takes `bool $required` first and returns an array already
 * beginning with 'required' or 'nullable', so the common case is a bare call
 * and the optional case is one named argument. Append extra rules by spreading:
 * `[...V::email(), 'unique:users']`.
 *
 * The custom Rule objects in this namespace (PersonName, IndianMobile, NoHtml,
 * AddressLine) exist only because no native rule expresses them. Consume them
 * through these helpers rather than instantiating them directly, so the
 * accompanying string/length rules travel with them.
 *
 * NOTE ON TRIMMING: Laravel's TrimStrings and ConvertEmptyStringsToNull
 * middleware are both active (they are Laravel 12 defaults and bootstrap/app.php
 * does not remove them). A whitespace-only value therefore arrives as null and
 * is caught by 'required'. The rule objects trim again anyway so they stay
 * correct outside an HTTP request.
 */
final class ValidationRules
{
    /**
     * A person's name - Unicode, 2-100 characters.
     *
     * Client-side counterpart:
     *   required minlength="2" maxlength="100"
     */
    public static function name(bool $required = true, int $min = 2, int $max = 100): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            "min:{$min}",
            "max:{$max}",
            new PersonName,
        ];
    }

    /**
     * An email address.
     *
     * Uses Laravel's own validator in `strict` mode (Egulias'
     * NoRFCWarningsValidation). Plain 'email' is RFC-permissive and accepts
     * both "john@gmail" (no TLD) and "john @gmail.com" (folded whitespace);
     * 'strict' rejects those and every other malformed shape without a
     * hand-rolled regex, and without the DNS lookup that 'email:dns' performs
     * on every request.
     *
     * Client-side counterpart:
     *   type="email" required maxlength="255"
     */
    public static function email(bool $required = true, int $max = 255): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'email:strict',
            "max:{$max}",
        ];
    }

    /**
     * Lower-case an address for storage or lookup.
     *
     * Call this explicitly - the app does NOT currently normalise email case on
     * write, so applying it wholesale would change the meaning of existing
     * unique constraints and stored rows. It is here for new code and for
     * lookups (the login rate limiter already lower-cases this way).
     */
    public static function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $email = trim($email);

        return $email === '' ? null : mb_strtolower($email);
    }

    /**
     * An Indian mobile number. Accepts +91/0 prefixes, spaces and hyphens.
     *
     * Persist IndianMobile::normalize() of the value, not the raw input.
     *
     * Client-side counterpart:
     *   type="tel" inputmode="numeric" required maxlength="20"
     *   pattern="(\+?91[\s\-]?)?0?[6-9][0-9\s\-]{9,}"
     *   title="Enter a 10-digit Indian mobile number starting with 6, 7, 8 or 9."
     */
    public static function mobile(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:20',
            new IndianMobile,
        ];
    }

    /**
     * An Indian PIN code: six digits, first digit 1-9.
     *
     * Client-side counterpart:
     *   inputmode="numeric" required pattern="[1-9][0-9]{5}"
     *   title="Enter a 6-digit PIN code."
     */
    public static function pincode(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'regex:/^[1-9]\d{5}$/',
        ];
    }

    /**
     * Money. Non-negative, at most 2 decimal places.
     *
     * The default ceiling sits well under the decimal(12,2) columns these
     * values land in, so a bad number is rejected rather than truncated by the
     * database. Raise $max for order totals if a basket can legitimately exceed
     * it.
     *
     * Client-side counterpart:
     *   type="number" step="0.01" min="0" max="9999999.99"
     */
    public static function money(bool $required = true, float $max = 9999999.99, float $min = 0): array
    {
        return [
            $required ? 'required' : 'nullable',
            'numeric',
            'decimal:0,2',
            "min:{$min}",
            "max:{$max}",
        ];
    }

    /**
     * A percentage, 0-100 inclusive, at most 2 decimal places.
     *
     * Client-side counterpart:
     *   type="number" step="0.01" min="0" max="100"
     */
    public static function percentage(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'numeric',
            'decimal:0,2',
            'min:0',
            'max:100',
        ];
    }

    /**
     * A quantity: a whole number, at least 1.
     *
     * Client-side counterpart:
     *   type="number" step="1" min="1" max="999"
     */
    public static function quantity(bool $required = true, int $max = 999, int $min = 1): array
    {
        return [
            $required ? 'required' : 'nullable',
            'integer',
            "min:{$min}",
            "max:{$max}",
        ];
    }

    /**
     * Single-line free text with no markup - a subject, a label, a city.
     *
     * Client-side counterpart:
     *   required minlength="{min}" maxlength="{max}"
     */
    public static function text(bool $required = true, int $max = 255, int $min = 1): array
    {
        return array_values(array_filter([
            $required ? 'required' : 'nullable',
            'string',
            $required ? new NotBlank : null,
            "min:{$min}",
            "max:{$max}",
            new NoHtml,
        ]));
    }

    /**
     * Multi-line free text with no markup - a message, a review, a note.
     *
     * NOT for admin rich-text editors: those are HTML by design and belong to
     * safe_html() in app/helpers.php.
     *
     * Client-side counterpart:
     *   required minlength="{min}" maxlength="{max}" on the <textarea>
     */
    public static function textarea(bool $required = true, int $max = 2000, int $min = 1): array
    {
        return array_values(array_filter([
            $required ? 'required' : 'nullable',
            'string',
            $required ? new NotBlank : null,
            "min:{$min}",
            "max:{$max}",
            new NoHtml,
        ]));
    }

    /**
     * A postal address line - digits, / - , . # and Unicode letters.
     *
     * Client-side counterpart:
     *   required minlength="3" maxlength="255"
     */
    public static function addressLine(bool $required = true, int $max = 255, int $min = 3): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            "min:{$min}",
            "max:{$max}",
            new AddressLine,
        ];
    }

    /**
     * An absolute http/https URL.
     *
     * Laravel's `url:http,https` restricts the scheme itself, which is what
     * rejects `javascript:` and `data:`. It also requires a scheme, so a bare
     * "example.com" fails - label the field "https://..." accordingly.
     *
     * Client-side counterpart:
     *   type="url" maxlength="2048" placeholder="https://example.com"
     */
    public static function url(bool $required = true, int $max = 2048): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            "max:{$max}",
            'url:http,https',
        ];
    }

    /**
     * A new password.
     *
     * This is the app's EXISTING policy, unchanged: Password::defaults(), which
     * has no custom callback registered anywhere, so it resolves to Laravel's
     * built-in minimum of 8 characters. Auth\RegisterController,
     * Auth\ResetPasswordController, Account\ProfileController and the API
     * controllers all already use exactly this; Admin\StaffController and
     * Admin\ProfileController use the equivalent 'min:8|confirmed'.
     *
     * To tighten the policy site-wide, register a callback with
     * Password::defaults() in AppServiceProvider::boot() - every caller of this
     * method then follows automatically. Do not tighten it here.
     *
     * Client-side counterpart:
     *   type="password" required minlength="8" autocomplete="new-password"
     */
    public static function password(bool $required = true, bool $confirmed = true): array
    {
        return array_values(array_filter([
            $required ? 'required' : 'nullable',
            $confirmed ? 'confirmed' : null,
            Password::defaults(),
        ]));
    }

    /**
     * An uploaded image.
     *
     * Both 'mimes' (extension) and 'mimetypes' (sniffed content type) are
     * applied: 'mimes' alone trusts the filename, and a .png that is really a
     * PHP script passes it.
     *
     * Client-side counterpart:
     *   type="file" accept="image/jpeg,image/png,image/webp"
     */
    public static function image(
        bool $required = true,
        int $maxKb = 5120,
        bool $allowGif = false,
        ?int $maxWidth = 5000,
        ?int $maxHeight = 5000,
    ): array {
        $extensions = ['jpeg', 'jpg', 'png', 'webp'];
        $mimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if ($allowGif) {
            $extensions[] = 'gif';
            $mimeTypes[] = 'image/gif';
        }

        $rules = [
            $required ? 'required' : 'nullable',
            'image',
            'mimes:'.implode(',', $extensions),
            'mimetypes:'.implode(',', $mimeTypes),
            "max:{$maxKb}",
        ];

        // A guard against decompression bombs, not a design constraint - the
        // ceiling is far above any real product photo.
        if ($maxWidth !== null && $maxHeight !== null) {
            $rules[] = "dimensions:max_width={$maxWidth},max_height={$maxHeight}";
        }

        return $rules;
    }

    /**
     * An uploaded document - invoices, GST certificates, return paperwork.
     *
     * Client-side counterpart:
     *   type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
     */
    public static function document(bool $required = true, int $maxKb = 10240): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:pdf,doc,docx,jpg,jpeg,png',
            'mimetypes:application/pdf,application/msword,'
                .'application/vnd.openxmlformats-officedocument.wordprocessingml.document,'
                .'image/jpeg,image/png',
            "max:{$maxKb}",
        ];
    }

    /**
     * A <select> or radio group with a fixed set of options.
     *
     * Never trust the rendered options - the value is whatever the client sent.
     *
     *     'status' => V::option(['pending', 'shipped', 'delivered']),
     */
    public static function option(array $allowed, bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            Rule::in($allowed),
        ];
    }

    /**
     * A foreign key arriving from a <select>.
     *
     *     'category_id' => V::foreignId('categories'),
     *     'address_id'  => V::foreignId('user_addresses', required: false),
     *
     * Scope it to the current user where the row is user-owned - `exists`
     * proves the row is real, not that it is theirs:
     *
     *     'address_id' => [...V::foreignId('user_addresses'),
     *                      Rule::exists('user_addresses', 'id')
     *                          ->where('user_id', $request->user()->id)],
     */
    public static function foreignId(string $table, bool $required = true, string $column = 'id'): array
    {
        return [
            $required ? 'required' : 'nullable',
            'integer',
            'min:1',
            Rule::exists($table, $column),
        ];
    }

    /**
     * A checkbox. Use accepted() instead when it must be ticked (terms).
     */
    public static function boolean(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'boolean',
        ];
    }

    /**
     * A checkbox that must be ticked - terms, consent.
     *
     * Client-side counterpart:
     *   type="checkbox" required
     */
    public static function accepted(): array
    {
        return ['accepted'];
    }
}
