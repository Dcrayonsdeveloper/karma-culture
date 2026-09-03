<?php

namespace App\Support;

use App\Models\Cart;
use App\Models\Setting;

/**
 * What shipping costs for a given order value.
 *
 * One definition, because there were none: Settings -> Shipping has had a flat
 * rate and a free-shipping threshold for as long as the screen has existed, and
 * nothing read either of them. Cart::recalculate() never set `shipping`, the
 * checkout view printed the word FREE unconditionally, and the order was
 * written with shipping_cost => 0. So the shop could not charge for delivery
 * however the admin filled that form in.
 *
 * The rules, in the order they apply:
 *
 *  1. No flat rate configured (switched off, or zero) - delivery is free. That
 *     is what every shop with this untouched has been doing, so switching the
 *     feature on is what starts charging, not deploying this.
 *  2. A free_shipping coupon waives it. Cart::recalculate() already keeps such
 *     a coupon attached even though it produces no discount, precisely so that
 *     something downstream can honour it - this is that something.
 *  3. Order value at or above the free-shipping threshold - free. Measured on
 *     the subtotal AFTER discount, which is what the customer is actually
 *     paying and what "minimum order value" means on every shop that has one.
 *  4. Otherwise the flat rate.
 */
class ShippingCharge
{
    public static function for(Cart $cart): float
    {
        $flatEnabled = (bool) Setting::get('flat_rate_enabled', false);
        $flat = (float) Setting::get('flat_rate_amount', 0);

        if (! $flatEnabled || $flat <= 0) {
            return 0.0;
        }

        if ($cart->coupon && $cart->coupon->type === 'free_shipping' && $cart->coupon->isValid()) {
            return 0.0;
        }

        return self::isOverThreshold((float) $cart->subtotal - (float) $cart->discount)
            ? 0.0
            : $flat;
    }

    /**
     * Whether an order value earns free delivery.
     *
     * Free shipping switched on with no threshold means free for everyone -
     * an admin who ticks the box and leaves the box empty has said "do not
     * charge", not "charge everybody".
     */
    public static function isOverThreshold(float $orderValue): bool
    {
        if (! (bool) Setting::get('free_shipping_enabled', false)) {
            return false;
        }

        $threshold = (float) Setting::get('free_shipping_threshold', 0);

        return $threshold <= 0 || $orderValue >= $threshold;
    }

    /** The flat rate as configured, whether or not a given order pays it. */
    public static function flatRate(): float
    {
        return (bool) Setting::get('flat_rate_enabled', false)
            ? (float) Setting::get('flat_rate_amount', 0)
            : 0.0;
    }
}
