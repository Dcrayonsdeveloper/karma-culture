<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Mail\ReviewInvitation;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendReviewInvitationAfterDelivery implements ShouldQueue
{
    public int $delay;

    public function __construct()
    {
        $delayHours = Setting::get('review_invitation_delay_hours', 48);
        $this->delay = $delayHours * 3600;
    }

    public function handle(OrderDelivered $event): void
    {
        if (!Setting::get('review_coupon_enabled', true)) {
            return;
        }

        $order = $event->order->load('items.product', 'user');
        $user = $order->user;

        if (!$user || !$user->email) {
            return;
        }

        // Don't send duplicate invitation for this order
        $exists = DB::table('review_invitations')
            ->where('order_id', $order->id)
            ->exists();

        if ($exists) {
            return;
        }

        $token = Str::random(64);

        DB::table('review_invitations')->insert([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'token' => $token,
            'sent_at' => now(),
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::to($user->email)->send(new ReviewInvitation($order, $token));
    }
}
