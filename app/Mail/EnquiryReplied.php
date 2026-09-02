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

        // A junk contact_email would throw inside the transport and take every
        // reply down with it, so an unusable setting just means no Reply-To.
        $replyTo = $contactEmail && filter_var($contactEmail, FILTER_VALIDATE_EMAIL)
            ? [new Address($contactEmail, config('app.name'))]
            : [];

        return new Envelope(
            subject: 'Re: ' . $this->enquiry->subject,
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.enquiry-replied',
            with: [
                'headingSubject' => self::escapeMarkdown($this->enquiry->subject),
                'senderName' => self::escapeMarkdown($this->enquiry->name),
                'replyLines' => self::markdownLines($this->replyMessage),
                'originalLines' => self::markdownLines($this->enquiry->message),
            ],
        );
    }

    /**
     * Split plain text into lines this mail can drop straight into markdown.
     *
     * The reply is whatever staff typed, so it has to survive CommonMark rather
     * than be parsed by it: a line like "[Order]: KK-4821" is a link reference
     * definition and renders as nothing at all, and a line indented four spaces
     * turns into a code block that also breaks out of the surrounding panel.
     *
     * @return array<int, string>
     */
    private static function markdownLines(string $text): array
    {
        return array_map(
            static fn (string $line): string => self::escapeMarkdown(ltrim($line)),
            preg_split('/\R/', trim($text)) ?: []
        );
    }

    /**
     * Backslash-escape the ASCII punctuation CommonMark treats as markup.
     */
    private static function escapeMarkdown(string $line): string
    {
        return preg_replace_callback(
            '/[\\`*_{}\[\]()#+\-.!>|~]/',
            static fn (array $match): string => '\\'.$match[0],
            $line
        );
    }
}
