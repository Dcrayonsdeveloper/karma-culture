<?php

namespace App\Mail;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartReminder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * $recoveryUrl is optional so existing call sites keep working. Without it
     * the mail links to /cart, which is what this email always used to do -
     * correct for a customer who is still signed in, useless for one whose
     * session has since expired.
     *
     * This class deliberately does NOT implement ShouldQueue: no queue worker
     * runs on this host, so a queued mail is simply never delivered.
     */
    public function __construct(
        public Cart $cart,
        public ?string $recoveryUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You left something in your cart!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.abandoned-cart',
            with: [
                'recoveryUrl' => $this->recoveryUrl ?: url('/cart'),
            ],
        );
    }
}
