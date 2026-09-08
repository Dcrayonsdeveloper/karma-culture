<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * How much of a shop's history one customer accounts for, and how to see it.
 *
 * Two jobs, deliberately kept in one place: counting a customer's orders (or
 * returns) for the badge, and narrowing a list to that same customer when the
 * badge is clicked. They are the same question asked twice, so they are built
 * from the same {@see CustomerIdentity::keyExpression()} - a badge reading "5"
 * that opens a list of three would be worse than no badge at all.
 *
 * Two rules keep the two answers in step:
 *
 *  1. A count is a LIFETIME total. It ignores the status tab, the date range and
 *     the search box the admin is currently looking through, because "how many
 *     times has this person ordered" is not a question about the current filter.
 *  2. So the badge links to the customer filter ALONE, dropping whatever else
 *     was applied. Click "5" and five rows arrive.
 *
 * Cost: two extra queries per list page, whatever the page size - one to count
 * and, when a filter is active, none at all beyond the list's own. Neither can
 * use an index, because the identity of a guest order is computed from JSON
 * rather than stored. That is a deliberate trade against a schema change and a
 * backfill on a production box that is edited live; at the shop's current 39
 * orders it is unmeasurable, and it stays comfortable into the tens of
 * thousands. Past that, the fix is a stored, indexed identity column - and this
 * class is where it would go, with nothing above it needing to change.
 */
final class CustomerHistory
{
    /**
     * Lifetime order count for each of $keys, as `key => count`.
     *
     * Keys with no orders are simply absent from the result rather than mapped
     * to zero, so a caller reading `$counts[$key] ?? 0` gets the right answer
     * whether the key was unknown or unidentifiable.
     *
     * @param  array<int, string>  $keys  identity keys, as produced by CustomerIdentity
     * @return array<string, int>
     */
    public static function orderCounts(array $keys): array
    {
        $keys = self::clean($keys);

        if ($keys === []) {
            return [];
        }

        $expression = CustomerIdentity::keyExpression('orders');
        $slots = self::placeholders($keys);

        return DB::table('orders')
            ->selectRaw("{$expression} AS customer_key, COUNT(*) AS tally")
            ->whereRaw("{$expression} IN ({$slots})", $keys)
            ->groupBy('customer_key')
            ->pluck('tally', 'customer_key')
            ->map(fn ($tally) => (int) $tally)
            ->all();
    }

    /**
     * Lifetime return figures for each of $keys, as
     * `key => ['requests' => int, 'orders' => int]`.
     *
     * A return's customer is the customer of the order it was raised against.
     * `returns.user_id` is not consulted at all: it is nullable, it says nothing
     * about a guest, and reading it here would give the Returns list a different
     * notion of "same person" than the Orders list has.
     *
     * Two numbers rather than one because a single order can raise SEVERAL
     * returns - the customer-facing form guards duplicates per order ITEM, not
     * per order, and a rejected return frees its items to be sent again. So one
     * order returned in three parcels is three rows here, and a bare "3" beside
     * a name on the Returns queue reads as a serial returner when it is nothing
     * of the sort. The badge shows the request count, which is what was asked
     * for; the distinct-order count is what its tooltip uses to say so.
     *
     * @param  array<int, string>  $keys
     * @return array<string, array{requests: int, orders: int}>
     */
    public static function returnCounts(array $keys): array
    {
        $keys = self::clean($keys);

        if ($keys === []) {
            return [];
        }

        $expression = CustomerIdentity::keyExpression('orders');
        $slots = self::placeholders($keys);

        return DB::table('returns')
            ->join('orders', 'orders.id', '=', 'returns.order_id')
            ->selectRaw("{$expression} AS customer_key, COUNT(*) AS tally, COUNT(DISTINCT `returns`.`order_id`) AS orders_tally")
            ->whereRaw("{$expression} IN ({$slots})", $keys)
            ->groupBy('customer_key')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->customer_key => [
                'requests' => (int) $row->tally,
                'orders' => (int) $row->orders_tally,
            ]])
            ->all();
    }

    /**
     * The same person's OTHER identity, if they have one - or null.
     *
     * An account and a mobile number are different things here, and one human is
     * routinely both: they bought as a guest, then registered. Those orders do
     * not merge, and they should not - a merged badge is a number the admin
     * cannot check, and `users.phone` being unique is not the same as it being
     * the number every guest order was placed with.
     *
     * So the badge stays exact and this is offered instead: on a filtered page,
     * a link saying the rest of the story exists. The ambiguity is disclosed and
     * left as the admin's call, rather than settled silently in code.
     */
    public static function counterpartOf(string $key): ?string
    {
        if (! CustomerIdentity::isKey($key)) {
            return null;
        }

        $normalised = CustomerIdentity::normalisedPhoneSql('`users`.`phone`');

        if (CustomerIdentity::isGuestKey($key)) {
            $id = DB::table('users')
                ->whereRaw("{$normalised} = ?", [CustomerIdentity::phoneOf($key)])
                ->orderBy('id')
                ->value('id');

            return $id ? 'u:' . $id : null;
        }

        $row = DB::table('users')
            ->where('id', (int) substr($key, 2))
            ->selectRaw("{$normalised} AS phone_key")
            ->first();

        return $row?->phone_key ? 'p:' . $row->phone_key : null;
    }

    /**
     * Narrow an Order query to the one customer $key names.
     *
     * Note the grouping in the two counts above is by the SELECT alias, not by
     * a second copy of the expression. MySQL runs with ONLY_FULL_GROUP_BY and
     * refuses `GROUP BY <that CASE expression>` as not covering the `user_id`
     * inside it, however exactly the two are spelled - and grouping by the alias
     * is the shorter way to say it in any case.
     */
    public static function scopeOrders(Builder $query, string $key): Builder
    {
        return $query->whereRaw(CustomerIdentity::keyExpression('orders') . ' = ?', [$key]);
    }

    /**
     * Narrow an OrderReturn query to the one customer $key names.
     *
     * Expressed as a whereHas rather than a join so the returns themselves are
     * never duplicated - and it matches the counting query exactly, because
     * `returns.order_id` is a non-nullable foreign key, so "the orders joined to
     * this return" and "an order exists for this return" are the same one row.
     */
    public static function scopeReturns(Builder $query, string $key): Builder
    {
        return $query->whereHas(
            'order',
            fn ($order) => $order->whereRaw(CustomerIdentity::keyExpression('orders') . ' = ?', [$key])
        );
    }

    /**
     * The identity keys present on a page of orders, de-duplicated.
     *
     * @param  iterable<int, Order>  $orders
     * @return array<int, string>
     */
    public static function keysOfOrders(iterable $orders): array
    {
        $keys = [];

        foreach ($orders as $order) {
            if ($key = CustomerIdentity::ofOrder($order)) {
                $keys[$key] = true;
            }
        }

        return array_keys($keys);
    }

    /**
     * The identity keys present on a page of returns, de-duplicated.
     *
     * @param  iterable<int, OrderReturn>  $returns
     * @return array<int, string>
     */
    public static function keysOfReturns(iterable $returns): array
    {
        $keys = [];

        foreach ($returns as $return) {
            if ($key = CustomerIdentity::ofOrder($return->order)) {
                $keys[$key] = true;
            }
        }

        return array_keys($keys);
    }

    /**
     * Drop anything that is not a well-formed key.
     *
     * The keys reaching the two count queries are read off the page's own rows,
     * but the one reaching a filter came out of a query string. Both go through
     * here, so the `IN` lists below can never carry anything but `u:<digits>` or
     * `p:<ten digits>`.
     *
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    private static function clean(array $keys): array
    {
        return array_values(array_unique(array_filter($keys, CustomerIdentity::isKey(...))));
    }

    /** @param  array<int, string>  $keys */
    private static function placeholders(array $keys): string
    {
        return implode(', ', array_fill(0, count($keys), '?'));
    }
}
