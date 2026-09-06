<?php

namespace App\Mail;

use App\Models\BlogPost;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * The new-post announcement, addressed to one subscriber.
 *
 * Per subscriber rather than one message BCC'd to the list, because the two
 * things this email has to carry are personal: the name in the greeting, and
 * an unsubscribe link that belongs to this address alone. A shared BCC send
 * can offer neither, and a shared unsubscribe link would take the whole list
 * off in one click.
 */
class BlogPostPublished extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BlogPost $post,
        public NewsletterSubscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->post->title,
        );
    }

    /**
     * List-Unsubscribe, and it is not decoration.
     *
     * Gmail and Outlook read it to draw their own unsubscribe button beside
     * the sender name, and a bulk sender that does not offer one gets marked
     * as spam by people who cannot find the link in the footer - which costs
     * the delivery of every later message to that domain. One-Click is the
     * POST half: without it the client only shows the button on some accounts.
     */
    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->subscriber->unsubscribeUrl().'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.blog-post-published',
            with: [
                'greetingName' => $this->subscriber->name ?: 'there',
                'url' => route('blog.show', $this->post->slug),
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }
}
