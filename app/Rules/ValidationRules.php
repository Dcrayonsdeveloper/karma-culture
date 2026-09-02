<?php

namespace App\Rules;

use DateTimeInterface;
use Illuminate\Support\Str;
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
     * `lettersOnly` drops the hyphen, apostrophe and period from the charset,
     * leaving letters and spaces. Pass it only where a form has been specified
     * that way; the default keeps "O'Connor" and "Mary-Anne" enterable.
     *
     * Client-side counterpart:
     *   required minlength="2" maxlength="100"
     *   pattern="{{ \App\Rules\ValidationRules::namePattern() }}"
     *   data-kk-chars="personName"
     * and, for lettersOnly, namePattern(lettersOnly: true).
     */
    public static function name(bool $required = true, int $min = 2, int $max = 100, bool $lettersOnly = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            "min:{$min}",
            "max:{$max}",
            new PersonName($lettersOnly),
        ];
    }

    /**
     * The HTML `pattern` attribute mirroring {@see PersonName} for a name box.
     *
     * Echo it rather than pasting the string into each blade. Five hand-copied
     * regexes drifted apart once already, and the leading-whitespace clause
     * below is subtle enough that a copy which loses it looks fine and quietly
     * blocks checkout.
     *
     * WHY THE WHITESPACE CLAUSES. TrimStrings runs Str::trim before validation,
     * which strips Str::INVISIBLE_CHARACTERS from both ends - and that list is
     * far wider than the space bar: it includes TAB, NBSP, the ideographic
     * space, the zero-width joiners and the BOM. A name pasted out of Word,
     * WhatsApp Web or a spreadsheet routinely carries one, the server never
     * sees it, and a browser rule that stops at ` *` refuses a name the server
     * would have accepted - over a character the customer cannot see, with a
     * message naming only characters that ARE allowed. So the same set the
     * server trims is tolerated here, at both ends, and nowhere else: the
     * charset in the middle stays exactly PersonName's.
     *
     * The list is derived from Str::INVISIBLE_CHARACTERS rather than retyped,
     * so it cannot fall behind a framework upgrade. \x{...} is PCRE's spelling;
     * JavaScript wants \u{...}, and the `pattern` attribute is compiled with the
     * u/v flag, which is what makes both that and \p{L} legal there.
     */
    public static function namePattern(bool $lettersOnly = false): string
    {
        // \s covers CR/LF/FF, which PHP's trim strips but the list does not name.
        $trimmed = '['.str_replace('\x{', '\u{', Str::INVISIBLE_CHARACTERS).'\s]*';

        $separators = $lettersOnly ? '' : '\'\u{2019}.\-';

        return $trimmed.'[\p{L}\p{M}][\p{L}\p{M}\x20\u{00A0}'.$separators.']*'.$trimmed;
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
     * `strictShape` adds {@see EmailAddress} on top, which narrows the RFC's
     * very wide idea of a local part to the shape providers actually issue -
     * chiefly, it has to open on a letter or a digit. Pass it where an address
     * is being CREATED (signup); leave it off where one is being matched
     * (sign-in, lookups), or an address stored before the rule existed can no
     * longer be typed by the person it belongs to.
     *
     * Client-side counterpart:
     *   type="email" required maxlength="255"
     *   and, for strictShape, the _emailError() mirror in resources/js/app.js
     */
    public static function email(bool $required = true, int $max = 255, bool $strictShape = false): array
    {
        $rules = [
            $required ? 'required' : 'nullable',
            'string',
            'email:strict',
            "max:{$max}",
        ];

        if ($strictShape) {
            $rules[] = new EmailAddress;
        }

        return $rules;
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
     * The site-wide policy: Password::defaults(), whose callback is registered
     * in AppServiceProvider::boot() and reads
     *   Password::min(10)->mixedCase()->numbers()->symbols()
     * so a new password is at least ten characters and carries an uppercase
     * letter, a lowercase letter, a number and a special character.
     *
     * Every form that sets a password goes through here - Auth\RegisterController,
     * Auth\ResetPasswordController, Account\ProfileController, Admin\StaffController,
     * Admin\ProfileController and the API controllers - so the policy has exactly
     * one definition and there is nothing to keep in sync per form.
     *
     * To change the policy site-wide, edit that callback. Do not tighten it here:
     * a rule added in this method would apply to the forms and silently not to
     * the API, which is how the two drifted apart before.
     *
     * Client-side counterpart, which mirrors it message for message:
     *   type="password" required minlength="10" maxlength="255"
     *   autocomplete="new-password"
     * plus the live keystroke check in resources/js/app.js (_passwordError and
     * the "new password" module beside the inline validator).
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
     * The start of a schedule - a date and time that may not be in the past.
     *
     * On an EDIT form pass the stored value as $current, so a coupon or sale
     * that has already begun can be saved without its start being dragged
     * forward; only a changed start has to be in the future.
     *
     *     'starts_at' => V::scheduleStart(required: false, current: $coupon?->starts_at),
     *
     * Client-side counterpart:
     *   type="datetime-local" min="{now, or the stored value if that is older}"
     *   data-schedule-start data-schedule-original="{the stored value}"
     */
    public static function scheduleStart(bool $required = true, DateTimeInterface|string|null $current = null): array
    {
        return [
            $required ? 'required' : 'nullable',
            'date',
            new NotPastDateTime($current),
        ];
    }

    /**
     * The end of a schedule - after its start field, and not in the past.
     *
     *     'expires_at' => V::scheduleEnd('starts_at', required: false, current: $coupon?->expires_at),
     *
     * `after` is deliberately strict rather than after_or_equal: a window that
     * opens and closes on the same minute is never what was meant. Where the
     * start is optional and left empty the ordering rule has nothing to compare
     * against, and the future check below is what still holds.
     *
     * Client-side counterpart:
     *   type="datetime-local" data-schedule-end="{id of the start input}"
     *   data-schedule-original="{the stored value}"
     */
    public static function scheduleEnd(string $startField, bool $required = true, DateTimeInterface|string|null $current = null): array
    {
        return [
            $required ? 'required' : 'nullable',
            'date',
            "after:{$startField}",
            new NotPastDateTime($current),
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
