<?php

namespace App\Support;

use App\Models\Cart;
use App\Models\Setting;

/**
 * The tax total, split into the lines a GST invoice names.
 *
 * The cart stores one figure. A customer looking at "Tax Rs. 5,940.17" cannot
 * tell what rate that is or how it is made up, and an Indian invoice has to
 * name the components rather than the sum.
 *
 * Grouped by rate, because a basket can mix them: an 18% shirt and a 5% item
 * are two GST slabs and belong on two lines, not averaged into one.
 *
 * INTRA-STATE ONLY. Each slab is split half into CGST and half into SGST,
 * which is correct when the customer is in the same state as the seller. The
 * inter-state case - one IGST line at the full rate - is NOT decided here,
 * because the shop has no origin state configured to compare against
 * (shipping_origin_country is still "US" and no state is set). Setting that,
 * and reading the buyer's state off the chosen address, is what this needs to
 * become complete; until then it names the split it can defend.
 */
class TaxBreakdown
{
    /**
     * @return array<int, array{label: string, rate: float, amount: float}>
     */
    public static function for(Cart $cart): array
    {
        if (! (bool) Setting::get('tax_enabled', false)) {
            return [];
        }

        // Per rate, so mixed slabs stay separate.
        $byRate = [];

        foreach ($cart->items as $item) {
            $product = $item->product;

            if (! $product || ! $product->is_taxable) {
                continue;
            }

            $rate = (float) $product->tax_rate;

            if ($rate <= 0) {
                continue;
            }

            $byRate[(string) $rate] = ($byRate[(string) $rate] ?? 0) + ((float) $item->total * $rate / 100);
        }

        krsort($byRate, SORT_NUMERIC);

        $rows = [];

        foreach ($byRate as $rate => $amount) {
            $rate = (float) $rate;
            $half = round($amount / 2, 2);

            // The halves are rounded independently, so give the remainder to
            // SGST rather than letting the two lines quietly sum to a paisa
            // less than the tax actually charged.
            $rows[] = ['label' => 'CGST '.self::rateLabel($rate / 2), 'rate' => $rate / 2, 'amount' => $half];
            $rows[] = ['label' => 'SGST '.self::rateLabel($rate / 2), 'rate' => $rate / 2, 'amount' => round($amount, 2) - $half];
        }

        return $rows;
    }

    /** "9%" rather than "9.00%", but "2.5%" keeps its half. */
    private static function rateLabel(float $rate): string
    {
        return rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.').'%';
    }
}
