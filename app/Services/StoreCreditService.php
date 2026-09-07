<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\OrderReturn;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Turns an approved return into spendable store credit.
 *
 * There is no wallet or store-credit ledger in this application - the
 * `refund_method` enum has always listed 'wallet', but nothing anywhere credits
 * one. What the store does have is coupons, with a per-user restriction, a
 * redemption cap and an expiry, which is enough to express a one-off voucher
 * belonging to one customer. So a credit IS a coupon here, and this class is
 * the single place that mints one, so its shape cannot drift between the admin
 * screen, an email and a test.
 */
class StoreCreditService
{
    /** Characters a customer can retype off an email without ambiguity. */
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Issue the credit for a return, or hand back the one already issued.
     *
     * Idempotent on purpose: processing a return is an admin button, and an
     * admin who clicks it twice - or a page that is submitted twice - must not
     * mint a second voucher for the same money. The already-issued coupon on
     * the return row is the guard.
     */
    public function issueFor(OrderReturn $return, float $amount): Coupon
    {
        if ($return->refund_coupon_id && $return->refundCoupon) {
            return $return->refundCoupon;
        }

        $validityDays = (int) Setting::get('return_credit_validity_days', 365);

        return DB::transaction(function () use ($return, $amount, $validityDays) {
            $coupon = Coupon::create([
                'code' => $this->uniqueCode(),
                'name' => "Store credit for return {$return->return_number}",
                'description' => "Issued in place of a cash refund for return {$return->return_number}"
                    .($return->order ? " on order {$return->order->order_number}." : '.'),
                'type' => 'fixed',
                'value' => round($amount, 2),

                // A fixed discount is capped at the cart subtotal by
                // Coupon::calculateDiscount(), and the remainder is NOT carried
                // forward - there is no ledger to carry it in. Requiring a
                // basket at least as large as the credit is what stops the
                // difference being silently destroyed on a smaller order.
                'min_order_amount' => round($amount, 2),

                // One customer, one redemption. applicable_users is the only
                // per-customer restriction the coupon table has; it is empty
                // for a guest return, which has no account to pin it to, and
                // there the single-use cap plus an unguessable code carry the
                // whole weight.
                'applicable_users' => $return->user_id ? [$return->user_id] : null,
                'usage_limit' => 1,
                'usage_per_user' => 1,

                'is_active' => true,
                // Never auto-applied: this is somebody's money, and it must be
                // spent when they choose rather than swallowed by whichever
                // cart happens to be open when it lands.
                'auto_apply' => false,
                'starts_at' => now(),
                'expires_at' => $validityDays > 0 ? now()->addDays($validityDays) : null,
            ]);

            $return->forceFill([
                'refund_coupon_id' => $coupon->id,
                'refund_method' => 'coupon',
            ])->save();

            return $coupon;
        });
    }

    /**
     * A code that does not collide and cannot be guessed from a neighbouring one.
     *
     * Sequential or predictable codes would matter less now that the cart
     * enforces applicable_users, but a guest credit has no owner to check
     * against - there the randomness is the only thing standing between the
     * voucher and anyone who tries the next code along.
     */
    private function uniqueCode(): string
    {
        do {
            $code = 'KKC-'.collect(range(1, 10))
                ->map(fn () => self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)])
                ->implode('');
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
