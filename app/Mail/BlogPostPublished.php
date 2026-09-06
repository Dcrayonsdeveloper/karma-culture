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
use Illuminate\Support\Str;

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

    /**
     * HTML and a plain-text alternative, not HTML alone.
     *
     * A bulk message with no text/plain part is one of the cheapest spam
     * signals a filter has, and this is exactly the kind of mail - one sender,
     * many near-identical copies, a link as the whole point - that gets
     * weighed for it. The second part costs one more render and is what a
     * watch, a screen reader and a text-only client show instead of nothing.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.blog-post-published',
            text: 'emails.blog-post-published-text',
            with: [
                'greetingName' => $this->subscriber->name ?: 'there',
                'url' => route('blog.show', $this->post->slug),
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
                'intro' => $this->intro(),
            ],
        );
    }

    /**
     * The teaser: the post's own excerpt, or a trimmed opening of the body.
     *
     * Computed here rather than in the template now that there are two of
     * them. Left in the Blade it would have to be written twice, and the copy
     * that drifts is the one nobody reads - the text part, which is only ever
     * seen by the clients least able to cope with a mistake.
     */
    private function intro(): string
    {
        if (trim((string) $this->post->excerpt) !== '') {
            return $this->post->excerpt;
        }

        return Str::limit(trim(html_entity_decode(strip_tags((string) $this->post->content))), 220);
    }
}
