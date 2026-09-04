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
        // This listener runs from Review::created for EVERY review - approved or
        // not, guest or not - and mints a live, immediately usable percentage
        // coupon with no minimum order. A visitor posting a guest review got
        // one, and there is a product to review for each of them, so anyone
        // could mint working discount codes for as long as they cared to type.
        // is_verified_purchase is false for every guest and true only when the
        // reviewer actually bought that product, which is the line this reward
        // was always meant to sit behind.
        if (! $review->is_verified_purchase) {
            return;
        }

        // Whether this address has already been rewarded.
        //
        // This was computed and then never consulted, so a customer reviewing a
        // second delivered order was issued a second coupon, and a third for a
        // third. One reward per address is what the query says; now it is also
        // what happens.
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
