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

        // One reward per address. This query was already here, assigned to
        // $alreadyRewarded, and then never read - so the guard existed in every
        // sense except the one that counts. Every review a customer wrote minted
        // another single-use percentage coupon and mailed it to them, which is a
        // discount generator with a review form in front of it. Nobody noticed
        // because the mail went out over a `log` mailer; it is a real coupon in
        // a real inbox now.
        //
        // Two things this does not fix, both wider than a dead variable. The
        // query is per address, while the comment above it always claimed "for
        // this product" - the author's own query is what runs here rather than a
        // rule invented after the fact. And it only bites once the address has a
        // review_invitations row to be marked against: the update below matches
        // nothing for a reviewer who never received an invitation, and nothing
        // sends invitations today because SendReviewInvitationAfterDelivery is a
        // queued listener on a server with no worker. Recording a reward for a
        // reviewer with no invitation would mean inventing an order_id and a
        // token for a row the schema requires both on.
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
