<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'How was your order? Share your thoughts & get 5% off!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.review-invitation',
        );
    }
}
