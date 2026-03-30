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

        // Check if we already rewarded this email for this product
        $alreadyRewarded = DB::table('review_invitations')
            ->where('email', $email)
            ->whereNotNull('coupon_id')
            ->where('reviewed_at', '!=', null)
            ->exists();

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
