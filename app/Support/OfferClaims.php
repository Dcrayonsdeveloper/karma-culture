<?php

namespace App\Support;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\OfferClaim;
use App\Models\User;
use App\Rules\ValidationRules as V;

/**
 * The exit-popup offer, from "Claim Offer" to a discount on the cart.
 *
 * Two entry points, and the split between them is the whole security model:
 *
 *   record()  - anybody may claim, for any address. Typing an address is not
 *               proof of owning it, so a claim NEVER touches a cart on its own.
 *   applyTo() - only ever called for a signed-in customer, and only ever finds
 *               a claim whose email IS that customer's account email. Being
 *               signed in as the address is the authorisation.
 *
 * That is what makes the email match trustworthy. A hostile visitor who claims
 * with somebody else's address has written a row only that person can ever
 * spend, and the coupon's own usage_limit / usage_per_user / applicable_users
 * still bound what spending it is worth - this class adds no new copy of those
 * rules, it composes Coupon::canBeUsedBy() and Coupon::calculateDiscount().
 */
class OfferClaims
{
    /**
     * Whether there is an offer to claim at all.
     *
     * The popup being switched off is the admin saying "stop offering this", so
     * a claim path that kept writing rows behind a hidden popup would be
     * accumulating promises nobody was made.
     */
    public static function enabled(): bool
    {
        $exit = PopupSettings::all(PopupSettings::EXIT);

        return $exit['enabled'] && trim((string) $exit['code']) !== '';
    }

    /**
     * Record that this address accepted the offer, and return the claim.
     *
     * The code comes from settings, never from the request. The popup posts
     * only an email and a phone number, and it has to stay that way: a
     * client-supplied code would let anyone claim any coupon in the table by
     * editing one JSON field, which is the difference between a 10% discount
     * and a 100% one.
     */
    public static function record(string $email, string $source, ?string $ip = null, ?User $user = null): ?OfferClaim
    {
        if (! self::enabled()) {
            return null;
        }

        $email = V::normalizeEmail($email);

        if ($email === null) {
            return null;
        }

        $exit = PopupSettings::all(PopupSettings::EXIT);
        $code = strtoupper(trim((string) $exit['code']));
        $days = max(1, (int) ($exit['claim_days'] ?? 7));

        // May be null - the admin is allowed to advertise a code before the
        // coupon exists, and the claim has to survive that gap.
        $couponId = Coupon::where('code', $code)->value('id');

        // Claiming again is claiming again: expires_at is refreshed rather than
        // frozen on the first insert. Freezing it looks like anti-farming and
        // is really a denial of service - one POST with a stranger's address
        // would start that person's window silently, and their own later claim
        // would be a no-op that still reported "saved, applies automatically".
        // Nothing is farmable by refreshing it: how many times the coupon can
        // actually be redeemed is the coupon's own business, not this row's.
        // Stamped only when the claimer was signed in AS this address, which is
        // what separates a claim somebody made for themselves from one typed on
        // their behalf. Read by nobody - applyTo() resolves by email, because
        // users.email is unique and the claim has to survive being made before
        // the account exists - so this is for the admin looking at the row.
        $claimedBy = $user && V::normalizeEmail($user->email) === $email ? $user->id : null;

        $claim = OfferClaim::updateOrCreate(
            ['email' => $email, 'code' => $code],
            array_filter([
                'coupon_id' => $couponId,
                'source' => $source,
                'claimed_at' => now(),
                'expires_at' => now()->addDays($days),
                'ip_address' => $ip,
                'user_id' => $claimedBy,
            ], fn ($v) => $v !== null),
        );

        // Mirrors CartController::applyCoupon(), which forgets the flag for the
        // same reason: asking for an offer is a fresh decision and has to
        // override an earlier "no thanks" in this session. Unconditional on
        // purpose - a guest is precisely who cannot clear it any other way, and
        // the guest journey is the one this feature exists for.
        session()->forget('coupon_dismissed');

        return $claim;
    }

    /**
     * Put the customer's claimed coupon on their cart, if it is theirs to have.
     *
     * Returns ['coupon' => ?Coupon, 'attached_now' => bool, 'discount' => float].
     * `coupon` is set whenever the cart is carrying the claimed coupon, whether
     * this call put it there or an earlier one did, so checkout can explain
     * where it came from; `attached_now` is true only on the request that
     * actually attached it, which is what the cart's one-shot note keys off.
     *
     * Every number in the return value is read back off the cart AFTER
     * recalculate() has had its say. Deciding "attached, worth 240" up front
     * and reporting that is how a page ends up announcing a discount that
     * repricing has already taken away.
     */
    public static function applyTo(?Cart $cart, ?User $user): array
    {
        $none = ['coupon' => null, 'attached_now' => false, 'discount' => 0.0];

        if (! $cart || ! $user || ! self::enabled()) {
            return $none;
        }

        // The customer removed a coupon in this session. Auto-apply already
        // honours that (CartController::index), and springing a claimed one
        // back would be the same surprise wearing a different hat. record()
        // clears the flag, so a claim made AFTER the removal still lands.
        if (session('coupon_dismissed', false)) {
            return $none;
        }

        $email = V::normalizeEmail($user->email);

        if ($email === null) {
            return $none;
        }

        // Every live claim, newest first - not just the newest one. Codes get
        // retired and re-run, so a customer can hold more than one, and taking
        // a single row would let a claim whose coupon is spent or disabled hide
        // an older one that still pays.
        $claims = OfferClaim::query()
            ->live()
            ->where('email', $email)
            ->orderByDesc('claimed_at')
            ->orderByDesc('id')
            ->get();

        if ($claims->isEmpty()) {
            return $none;
        }

        // `coupon` is force-reloaded, `items` is not. Cart::recalculate() can
        // attach an auto-apply coupon and leave the relation pointing at the
        // previous one (the hole documented at Cart.php:117), and reading a
        // stale incumbent here would pick the wrong winner below. `items` has
        // no such problem, and reloading it would quietly drop whatever the
        // caller eager-loaded onto it - items.variant, for one.
        $cart->load('coupon');
        $cart->loadMissing('items.product');

        if ($cart->items->isEmpty()) {
            return $none;
        }

        $coupon = null;

        foreach ($claims as $claim) {
            $candidate = self::couponFor($claim);

            // canBeUsedBy() is the one method that already checks validity,
            // applicable_users and the per-user cap together. Composing it is
            // the point: a hand-written version here would be the fifth copy of
            // a predicate this codebase keeps having to reconcile.
            if (! $candidate || ! $candidate->canBeUsedBy($user)) {
                continue;
            }

            // Already on. Report it so checkout can say where it came from, and
            // report the cart's own discount rather than recomputing one.
            if ((int) $cart->coupon_id === (int) $candidate->id) {
                return ['coupon' => $candidate, 'attached_now' => false, 'discount' => (float) $cart->discount];
            }

            if (self::appliesToCart($candidate, $cart)) {
                $coupon = $candidate;
                break;
            }
        }

        if (! $coupon) {
            return $none;
        }

        $incumbent = $cart->coupon;

        if ($incumbent) {
            // A code the customer typed themselves outranks a claim they may
            // have forgotten making. Never overwrite a deliberate choice.
            if (! $incumbent->auto_apply) {
                return $none;
            }

            // A machine-chosen coupon is replaceable, but only by a better one.
            // Otherwise "we applied your offer" would cost the customer money.
            if ($coupon->calculateDiscount((float) $cart->subtotal, $cart->items) <= (float) $cart->discount) {
                return $none;
            }
        }

        $cart->update(['coupon_id' => $coupon->id]);

        // recalculate() reprices first, so this is where min_order_amount is
        // really enforced - against live prices rather than whatever subtotal
        // was stored the last time the cart changed. It also drops the coupon
        // again if it turns out to be worth nothing.
        $cart->recalculate();
        $cart->refresh()->load('coupon');

        // Cast both sides: coupon_id is not in Cart::casts(), so whether it
        // arrives as an int or a numeric string is the driver's business, and a
        // strict === that quietly answered false here would re-attach on every
        // single request.
        // free_shipping is the exception Cart::recalculate() itself carves out
        // at the eviction step: calculateDiscount() returns 0 for it because the
        // saving is on shipping, not the subtotal. Judging "did it stick" on the
        // discount alone would report failure for a coupon that is on the cart
        // and working - and then the restore branch below would tear it off.
        $stuck = (int) $cart->coupon_id === (int) $coupon->id
            && ((float) $cart->discount > 0 || $coupon->type === 'free_shipping');

        if (! $stuck) {
            // The claimed coupon did not survive repricing. If it displaced an
            // auto-applied one on the way in, the customer must not be left
            // worse off than before they claimed - clearing coupon_id lets
            // Coupon::findBestAutoApply() put the old one back.
            if ($incumbent && (int) $cart->coupon_id !== (int) $incumbent->id) {
                $cart->update(['coupon_id' => null]);
                $cart->recalculate();
                $cart->refresh()->load('coupon');
            }

            return $none;
        }

        return ['coupon' => $coupon, 'attached_now' => true, 'discount' => (float) $cart->discount];
    }

    /**
     * Whether a coupon's product/category scoping matches what is in the cart.
     *
     * Coupon::calculateDiscount() only consults applicable_products and
     * applicable_categories for buy_x_get_y, so a percentage coupon scoped to
     * one category would discount the whole basket. CartController::applyCoupon()
     * has the same gap, but there the customer typed the code and a human chose
     * to honour it; here the machine is choosing, so it holds itself to the same
     * bar Coupon::findBestAutoApply() does - which is where this check is from.
     */
    private static function appliesToCart(Coupon $coupon, Cart $cart): bool
    {
        if (! empty($coupon->applicable_products)) {
            $productIds = $cart->items->pluck('product_id')->all();

            if (empty(array_intersect($productIds, $coupon->applicable_products))) {
                return false;
            }
        }

        if (! empty($coupon->applicable_categories)) {
            $categoryIds = $cart->items
                ->map(fn ($item) => $item->product?->category_id)
                ->filter()
                ->unique()
                ->all();

            if (empty(array_intersect($categoryIds, $coupon->applicable_categories))) {
                return false;
            }
        }

        return true;
    }

    /**
     * The coupon a claim refers to, resolving by code when the row was written
     * before that coupon existed - and remembering the answer.
     *
     * This back-fill is what makes the admin's documented "set the code now,
     * create the coupon later" workflow pay out for people who already claimed.
     */
    private static function couponFor(OfferClaim $claim): ?Coupon
    {
        if ($claim->coupon_id) {
            $coupon = Coupon::find($claim->coupon_id);

            if ($coupon) {
                return $coupon;
            }
        }

        $coupon = Coupon::where('code', strtoupper($claim->code))->first();

        if ($coupon && (int) $claim->coupon_id !== (int) $coupon->id) {
            $claim->forceFill(['coupon_id' => $coupon->id])->save();
        }

        return $coupon;
    }
}
