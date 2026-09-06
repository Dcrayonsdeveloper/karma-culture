<?php

namespace App\Support;

/**
 * One casing for the column, another for the page.
 *
 * users.first_name and users.last_name are stored folded to lower case, and
 * every screen that prints them puts the capitals back. The store gets one
 * spelling per name - "priyanshu", never "Priyanshu" next to "PRIYANSHU" next
 * to "priyanshu" for the same person - and the customer still reads
 * "Priyanshu Verma", because the capitals are a rendering decision rather than
 * something the keyboard happened to do at signup.
 *
 * WHAT COUNTS AS A WORD BREAK. The same separators {@see \App\Rules\PersonName}
 * lets into a name, and no others: whitespace (the plain space and the NBSP a
 * paste out of Word carries), the hyphen in "Mary-Anne", both apostrophes in
 * "O'Connor" and "O’Connor", and the period in "J.P. Mehta". A capital after
 * each of them is what those names want; a rule that only knew about the space
 * would print "Mary-anne" and "O'connor".
 *
 * WHAT IT CANNOT DO. Casing that is not derivable from the letters is lost:
 * "McDonald" is stored as "mcdonald" and comes back as "Mcdonald", because
 * nothing in the string says the D is capital. That is the cost of holding one
 * canonical spelling, and it is paid on every name in the table, not only on
 * new ones - so the day this stops being the right trade, it is the accessor on
 * {@see \App\Models\User} that has to go, not just the mutator.
 *
 * Scripts without letter case - Devanagari, Tamil, Arabic - pass through both
 * halves untouched, which is correct: mb_strtolower and mb_strtoupper are
 * no-ops on them, so "रवि कुमार" is stored and shown exactly as it was typed.
 */
final class NameCase
{
    /**
     * The characters a capital may follow, as a PCRE class.
     *
     * \s covers CR/LF/TAB as well as the space; \x{00A0} is the non-breaking
     * space, which \s does NOT match under PCRE without the /u-aware Unicode
     * property set, and which arrives in pasted names often enough to matter.
     */
    private const SEPARATORS = '\s\x{00A0}\'\x{2019}.\-';

    /**
     * What goes in the column.
     *
     * Null survives as null: users.last_name is nullable and an account created
     * from a single-word name has '' there, and neither should become the other.
     */
    public static function store(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        return mb_strtolower(trim($name));
    }

    /**
     * What the page prints.
     *
     * \K throws away everything matched so far, so the replacement is the single
     * letter and the separator before it is left exactly as it was - which is
     * what keeps a double space, or an NBSP, from being quietly rewritten into
     * something else on its way to the screen.
     */
    public static function display(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $name = trim($name);

        if ($name === '') {
            return '';
        }

        return preg_replace_callback(
            '/(?:^|['.self::SEPARATORS.'])\K[\p{L}\p{M}]/u',
            static fn (array $match): string => mb_strtoupper($match[0]),
            $name
        ) ?? $name;
    }
}
