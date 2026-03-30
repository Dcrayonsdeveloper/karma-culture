<?php

namespace App\Mail;

use App\Models\OrderReturn;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReturnApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OrderReturn $orderReturn) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Return Request Approved - #' . $this->orderReturn->return_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.return-approved',
        );
    }
}
