<?php

namespace App\Support;

/**
 * How healthy one stock figure is, in the one vocabulary the alerts use.
 *
 * "Low stock" is forked five ways across this app - the daily email tests
 * `stock_quantity <= low_stock_threshold` with no floor, the admin inventory
 * screens add `> 0`, and both report controllers hardcode `BETWEEN 1 AND 10`
 * - so a product can be low on one screen and healthy on the next. This is not
 * an attempt to unify them; it is the single definition the ADMIN BELL speaks,
 * kept in one place so the trait that raises a notification, the notification's
 * own wording and the tests cannot drift apart.
 *
 * The floor matters: an empty shelf is OUT, not LOW. Without it a product sold
 * to zero would be announced as "running low", which is what the daily email
 * already does (it has no floor, so it lists sold-out products under "low
 * stock" and then counts them again under its own out-of-stock call-out).
 *
 * `stock_status` is deliberately not consulted. Nothing outside the importers
 * has ever written that column - checkout drives a product to zero and leaves
 * `stock_status = 'in_stock'` forever - so a detector that read the flag would
 * never fire at all.
 */
final class StockLevel
{
    public const OK = 'ok';
    public const LOW = 'low';
    public const OUT = 'out';

    /**
     * How bad each level is. Only a move to a HIGHER number is worth
     * announcing: it is what separates "this just sold out" from the 4,456
     * sizes that were already sold out before the feature existed.
     */
    private const SEVERITY = [
        self::OK => 0,
        self::LOW => 1,
        self::OUT => 2,
    ];

    /**
     * Where one quantity sits against the threshold it is judged by.
     *
     * A threshold of 0 means "never warn me about this one" - the admin
     * product list already treats it that way - so only an empty shelf is
     * reported for those.
     */
    public static function for(?int $quantity, ?int $threshold): string
    {
        $quantity = (int) $quantity;
        $threshold = (int) $threshold;

        if ($quantity <= 0) {
            return self::OUT;
        }

        return $threshold > 0 && $quantity <= $threshold ? self::LOW : self::OK;
    }

    /**
     * Did the shelf get worse crossing from $before to $after?
     *
     * Transitions, never state. A shop with 326 products and 7,056 sizes
     * already sitting under their threshold would otherwise announce every one
     * of them - to every admin - the first time anything ran.
     */
    public static function worsened(string $before, string $after): bool
    {
        return (self::SEVERITY[$after] ?? 0) > (self::SEVERITY[$before] ?? 0);
    }
}
