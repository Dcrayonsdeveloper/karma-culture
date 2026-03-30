<?php

namespace App\Mail;

use App\Models\Coupon;
use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewCouponReward extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Review $review,
        public Coupon $coupon,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thank you for your review! Here\'s your ' . (int) $this->coupon->value . '% discount',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.review-coupon-reward',
        );
    }
}
