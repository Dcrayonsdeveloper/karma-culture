<?php

namespace App\Listeners;

use App\Mail\ReviewCouponReward;
use App\Models\Coupon;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendCouponAfterReview implements ShouldQueue
{
    public function handle(Review $review): void
    {
        if (!Setting::get('review_coupon_enabled', true)) {
            return;
        }

        // Only reward non-generated reviews (real human reviews)
        if ($review->is_generated) {
            return;
        }

        $email = $review->user?->email ?? $review->guest_email;
        if (!$email) {
            return;
        }

        // The reward thanks someone who bought the thing; it is not a prize for
        // typing.
        //
        // This closes the hole the $alreadyRewarded guard could not reach. That
        // guard only bites once an address has a review_invitations row to be
        // marked against, and a guest reviewer never has one - so a visitor
        // could still mint a live, immediately usable percentage coupon with no
        // minimum order, once per product, for as long as they cared to type.
        // is_verified_purchase is false for every guest and true only when the
        // reviewer actually bought that product, which is the line this reward
        // was always meant to sit behind.
        if (! $review->is_verified_purchase) {
            return;
        }

        // One reward per address. This query was already here, assigned to
        // $alreadyRewarded, and then never read - so the guard existed in every
        // sense except the one that counts. Every review a customer wrote minted
        // another single-use percentage coupon and mailed it to them, which is a
        // discount generator with a review form in front of it. Nobody noticed
        // because the mail went out over a `log` mailer; it is a real coupon in
        // a real inbox now.
        //
        // The query is per address, while the comment above it always claimed
        // "for this product" - the author's own query is what runs here rather
        // than a rule invented after the fact.
        $alreadyRewarded = DB::table('review_invitations')
            ->where('email', $email)
            ->whereNotNull('coupon_id')
            ->whereNotNull('reviewed_at')
            ->exists();

        if ($alreadyRewarded) {
            return;
        }

        // Create unique coupon
        $couponValue = Setting::get('review_coupon_value', 5);
        $coupon = Coupon::create([
            'code' => 'THANKS-' . strtoupper(Str::random(6)),
            'name' => 'Review Reward - ' . $couponValue . '% Off',
            'description' => 'Thank you for your review! Enjoy ' . $couponValue . '% off your next order.',
            'type' => 'percentage',
            'value' => $couponValue,
            'min_order_amount' => 0,
            'usage_limit' => 1,
            'usage_per_user' => 1,
            'is_active' => true,
            'starts_at' => now(),
            'expires_at' => now()->addDays(60),
        ]);

        // Update invitation record if exists
        DB::table('review_invitations')
            ->where('email', $email)
            ->whereNull('reviewed_at')
            ->update([
                'reviewed_at' => now(),
                'coupon_id' => $coupon->id,
                'updated_at' => now(),
            ]);

        Mail::to($email)->send(new ReviewCouponReward($review, $coupon));
    }
}
