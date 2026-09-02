<?php

namespace App\Mail;

use App\Models\Enquiry;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnquiryReplied extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Enquiry $enquiry,
        public string $replyMessage,
    ) {}

    public function envelope(): Envelope
    {
        $contactEmail = Setting::get('contact_email');

        return new Envelope(
            subject: 'Re: ' . $this->enquiry->subject,
            // The customer replying to this mail should land in the store inbox,
            // not the transactional from-address.
            replyTo: $contactEmail ? [new Address($contactEmail, config('app.name'))] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.enquiry-replied',
        );
    }
}
