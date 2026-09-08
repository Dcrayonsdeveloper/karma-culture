<?php

namespace App\Support;

use App\Models\Order;

/**
 * Who placed an order - in a form that can be counted, compared and put in a URL.
 *
 * The admin's Orders and Returns lists each want to answer "how many times has
 * *this* person been here before?" beside every row. That question has no column
 * to read, because half the shop's orders have no customer record at all:
 * `orders.user_id` is nullable and guest checkout leaves the contact details in
 * `metadata.guest_phone` and the address snapshots instead. So "the same
 * customer" has to be *derived*, and derived identically everywhere, or the
 * number on the badge and the length of the list it opens will disagree - which
 * is the one bug in this feature an admin is guaranteed to notice.
 *
 * The rule, in full:
 *
 *  - An order placed by a signed-in customer is identified by that account:
 *    `u:<user id>`. Two orders from one account are the same customer even if
 *    they were delivered to different people at different numbers, because the
 *    account is the stronger claim.
 *  - A guest order is identified by the mobile number it was placed with:
 *    `p:<last ten digits>`. `metadata.guest_phone` is written by checkout from
 *    the same canonicalised value the address snapshot carries, so the two
 *    agree; the snapshot is read only as a fallback for older rows.
 *  - An order with neither an account nor a usable number has NO identity. It
 *    gets no badge and cannot be filtered on. Silence is the right answer here:
 *    grouping unidentifiable orders together would invent a customer.
 *
 * Deliberately NOT part of the rule: the email address. It reads like the
 * obvious identity and it is not one. On production today a single test address
 * (`tim1@in.dcrayons.app`) sits on four guest orders placed by four different
 * people with four different phone numbers - keying on it would merge them into
 * one customer with four orders. The mobile number is the field checkout
 * validates ({@see \App\Rules\IndianMobile}), so it is the field that means
 * something.
 *
 * Numbers are compared on their last ten digits, the same way
 * {@see \App\Http\Controllers\TrackOrderController} lets a customer find their
 * own order: `+91 98765 43210`, `919876543210` and `9876543210` are one person.
 * Anything shorter than ten digits is not a phone number and yields no identity
 * rather than a short key that might collide.
 */
final class CustomerIdentity
{
    /**
     * A key is `u:<positive int>` or `p:<exactly ten digits>`, and nothing else.
     *
     * The `D` modifier is load-bearing: without it PCRE lets `$` match before a
     * final newline, so a key with a trailing line break would be accepted and
     * go on to be interpolated into SQL. It would match no row - but a validator
     * that lets a control character through is not a validator.
     */
    private const KEY_PATTERN = '/^(?:u:[1-9][0-9]{0,17}|p:[0-9]{10})$/D';

    /**
     * The last ten digits of $raw, or null if there are not ten of them.
     *
     * The floor matters: `RIGHT('12345', 10)` in SQL returns `'12345'` rather
     * than failing, so without the same length rule on both sides a five-digit
     * number would become a key in the database and no key in PHP, and the
     * badge would count rows the filtered list then refused to show.
     */
    public static function normalisePhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);

        return strlen($digits) >= 10 ? substr($digits, -10) : null;
    }

    /**
     * The identity of whoever placed $order, or null if it cannot be known.
     *
     * Reads only columns the list pages have already loaded, so putting a badge
     * on every row costs no extra query.
     */
    public static function ofOrder(?Order $order): ?string
    {
        if (! $order) {
            return null;
        }

        if ($order->user_id !== null) {
            return 'u:' . $order->user_id;
        }

        // The model's own accessor, not a second reading of the same JSON:
        // Order::customer_phone already knows where a guest's number lives and
        // in what order to prefer the copies, and the export reads it too. A
        // rule about which field wins has to have one home.
        $phone = self::normalisePhone($order->customer_phone);

        return $phone !== null ? 'p:' . $phone : null;
    }

    /**
     * What to call whoever placed $order.
     *
     * The account's name when there is an account, and otherwise the name the
     * guest gave at checkout, which lives on the address snapshot - the Orders
     * list has always printed a bare "Guest" for those rows, which is fine when
     * there is nothing to click but useless beside a badge saying "2": two
     * orders from *whom*?
     */
    public static function nameOfOrder(?Order $order): ?string
    {
        if (! $order) {
            return null;
        }

        // Order::customer_name is the same rule the export and the list
        // already use - the account's name, else the name the guest gave at
        // checkout. It answers the literal 'Guest' when it knows neither, and
        // for the purposes of a tooltip that is an absence, not a name.
        $name = trim((string) $order->customer_name);

        return ($name === '' || $name === 'Guest') ? null : $name;
    }

    /**
     * How to introduce the customer $key names, for the "showing only these"
     * banner. $sample is any order already known to belong to them - the first
     * row of the filtered page - so no lookup is needed to put a name to a
     * number.
     */
    public static function describe(string $key, ?Order $sample = null): string
    {
        $name = self::nameOfOrder($sample);

        if (self::isGuestKey($key)) {
            $phone = self::phoneOf($key);

            return $name !== null ? "{$name} ({$phone})" : $phone;
        }

        return $name ?? 'this customer';
    }

    /**
     * Is $key something this class could have produced?
     *
     * Every key arrives from a query string, and both the counting query and the
     * filter interpolate it into SQL. Validating the shape here - rather than
     * trusting a binding to save us - keeps that interpolation provably safe and
     * turns a hand-edited `?customer=` into "no filter" instead of an error page.
     */
    public static function isKey(mixed $key): bool
    {
        return is_string($key) && preg_match(self::KEY_PATTERN, $key) === 1;
    }

    /** True when $key names a guest, identified by their mobile number. */
    public static function isGuestKey(string $key): bool
    {
        return str_starts_with($key, 'p:');
    }

    /** The mobile number behind a guest key, formatted for display. */
    public static function phoneOf(string $key): ?string
    {
        return self::isGuestKey($key) ? substr($key, 2) : null;
    }

    /**
     * SQL that yields each order row's own identity key - the exact expression
     * {@see ofOrder()} implements in PHP.
     *
     * Both the per-page counts and the filtered list are built on this one
     * string, which is what stops them drifting apart. $table lets the returns
     * list apply it to the `orders` rows it joins, because a return's customer
     * is simply the customer of the order it belongs to.
     *
     * MySQL 8 in production and MariaDB 11 under test both provide every
     * function used here. `NULLIF(..., 'null')` is not paranoia: JSON_UNQUOTE
     * turns a stored JSON null into the four-character string "null", which
     * would otherwise sail through as a phone number of no digits.
     */
    public static function keyExpression(string $table = 'orders'): string
    {
        // The three JSON copies Order::customer_phone reads, in its order of
        // preference. Its fourth fallback - the account's own phone - is left
        // out deliberately: this branch is only ever reached for a row with no
        // user_id, where that fallback is null by construction.
        $raw = "COALESCE("
            . "NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`{$table}`.`shipping_address_snapshot`, '$.phone')), 'null'), "
            . "NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`{$table}`.`metadata`, '$.guest_phone')), 'null'), "
            . "NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`{$table}`.`billing_address_snapshot`, '$.phone')), 'null'))";

        $normalised = self::normalisedPhoneSql($raw);

        return "CASE"
            . " WHEN `{$table}`.`user_id` IS NOT NULL THEN CONCAT('u:', `{$table}`.`user_id`)"
            . " WHEN {$normalised} IS NOT NULL THEN CONCAT('p:', {$normalised})"
            . " ELSE NULL"
            . " END";
    }

    /**
     * SQL for the last ten digits of $rawExpression, or NULL if there are not
     * ten of them - {@see normalisePhone()} in the database's own words.
     *
     * The length guard is the whole point. `RIGHT('12345', 10)` returns
     * `'12345'` rather than failing, so without it a short number would yield a
     * key in SQL and no key in PHP, and the badge would count rows that the
     * filter then refused to show.
     */
    public static function normalisedPhoneSql(string $rawExpression): string
    {
        $digits = "REGEXP_REPLACE(COALESCE({$rawExpression}, ''), '[^0-9]', '')";

        return "CASE WHEN CHAR_LENGTH({$digits}) >= 10 THEN RIGHT({$digits}, 10) ELSE NULL END";
    }
}
