<?php

namespace App\Mail;

use App\Models\OrderReturn;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundProcessed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrderReturn $orderReturn,
        public float $amount,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Refund Processed - #' . $this->orderReturn->return_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.refund-processed',
        );
    }
}
