<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackInStockNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Product $product) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->product->name . ' is back in stock!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.back-in-stock',
        );
    }
}
