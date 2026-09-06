<?php

namespace App\Console\Commands;

use App\Mail\BlogPostPublished;
use App\Models\BlogPost;
use App\Models\NewsletterSubscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Tell the newsletter list about posts that have gone live.
 *
 * Driven by the scheduler rather than by the save that publishes a post, for
 * two reasons. A post can be scheduled - is_published with a published_at next
 * Tuesday - and nothing about the save that sets that up should send mail on
 * Tuesday. And mail here is synchronous SMTP with no queue worker on the host,
 * so sending from the admin request would hang the browser until the list ran
 * out and then time out somewhere in the middle of it.
 *
 * Safe to run every minute: it takes only the work that is left.
 */
class SendBlogPostNewsletter extends Command
{
    protected $signature = 'newsletter:send-blog-posts
                            {--limit=150 : Messages to send in this run}
                            {--post= : Only this post id}
                            {--resend : Clear the sent stamp on --post first, so it goes out again}
                            {--test= : Send one copy of --post to this address and stop}
                            {--pretend : List what would be sent, send nothing}';

    protected $description = 'Email newly published blog posts to active newsletter subscribers';

    public function handle(): int
    {
        $budget = max(1, (int) $this->option('limit'));
        $pretend = (bool) $this->option('pretend');

        if ($address = $this->option('test')) {
            return $this->sendTest($address);
        }

        if ($this->option('resend') && ! $this->unstamp()) {
            return self::FAILURE;
        }

        $posts = BlogPost::awaitingNewsletter()
            ->when($this->option('post'), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('published_at')
            ->get();

        if ($posts->isEmpty()) {
            $this->info('Nothing waiting.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($posts as $post) {
            if ($budget <= 0) {
                // Out of budget with work left. The stamp stays off the post,
                // so the next run resumes on exactly the addresses this one
                // did not reach.
                $this->line("Budget spent - {$post->title} will continue next run.");
                break;
            }

            [$postSent, $postFailed, $budget] = $this->sendPost($post, $budget, $pretend);
            $sent += $postSent;
            $failed += $postFailed;

            // Done only when nobody is left unsent. Checked after the run
            // rather than assumed from the budget, so a post whose whole list
            // failed is not marked as delivered.
            if (! $pretend && $this->remainingFor($post)->count() === 0) {
                $post->forceFill(['newsletter_sent_at' => now()])->save();
                $this->info("Finished: {$post->title}");
            }
        }

        $this->info(($pretend ? '[pretend] ' : '')."Sent {$sent}, failed {$failed}.");

        return self::SUCCESS;
    }

    /**
     * One copy of a post to one address, to prove the mail actually works.
     *
     * "Nobody got the newsletter" has two halves that look the same from the
     * outside - the send never happened, or it happened and the message did
     * not survive the trip to the inbox - and no amount of reading the
     * database tells them apart. This sends a real message through the real
     * transport so the answer arrives, or does not, in a mailbox someone can
     * look at.
     *
     * It writes no dispatch row and stamps nothing, so a test is never
     * mistaken for the announcement and never costs a subscriber theirs.
     */
    private function sendTest(string $address): int
    {
        $id = $this->option('post');

        if (! $id) {
            $this->error('--test needs --post=<id> to know which post to send.');

            return self::FAILURE;
        }

        $post = BlogPost::find($id);

        if (! $post) {
            $this->error("No blog post with id {$id}.");

            return self::FAILURE;
        }

        // The real subscriber where the address is on the list, so the test is
        // the exact message that person is sent - their name in the greeting
        // and their own unsubscribe link, not a stand-in that renders and
        // proves nothing about theirs.
        $subscriber = NewsletterSubscriber::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($address))])->first();

        if (! $subscriber) {
            $subscriber = new NewsletterSubscriber(['email' => $address]);

            // Set in memory and never saved. unsubscribeUrl() mints and SAVES a
            // token when it finds none, which on an unsaved model would put a
            // brand new subscriber on the list as a side effect of testing.
            $subscriber->unsubscribe_token = Str::random(48);
        }

        try {
            Mail::to($address)->send(new BlogPostPublished($post, $subscriber));
        } catch (Throwable $e) {
            $this->error('The mailer refused it: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Test copy of #{$post->id} \"{$post->title}\" sent to {$address}.");
        $this->line('  Nothing was stamped and no dispatch row was written.');
        $this->line('  If it does not arrive, check spam - the send itself worked.');

        return self::SUCCESS;
    }

    /**
     * Take the sent stamp back off a post so the next pass announces it.
     *
     * The dispatch rows are deliberately left alone. They are the record of
     * who has actually had a message, so keeping them means a resend reaches
     * exactly the people the first attempt missed - the whole list when the
     * post was stamped without a single send, and nobody at all when it really
     * did go out. Without that, "send it again" would mean a second copy in
     * the inbox of everyone who already read it.
     *
     * Scoped to one post on purpose: unstamping the archive would announce
     * every historic post at once.
     */
    private function unstamp(): bool
    {
        $id = $this->option('post');

        if (! $id) {
            $this->error('--resend needs --post=<id>, so the whole archive cannot go out by accident.');

            return false;
        }

        $post = BlogPost::find($id);

        if (! $post) {
            $this->error("No blog post with id {$id}.");

            return false;
        }

        if (! $post->newsletter_sent_at) {
            $this->line("#{$post->id} is not stamped - it was already waiting to go out.");

            return true;
        }

        if ($this->option('pretend')) {
            $this->line("[pretend] would clear the stamp on #{$post->id}.");

            return true;
        }

        $post->forceFill(['newsletter_sent_at' => null])->save();
        $this->info("Stamp cleared on #{$post->id} - {$post->title}");

        return true;
    }

    /**
     * @return array{0: int, 1: int, 2: int} sent, failed, remaining budget
     */
    private function sendPost(BlogPost $post, int $budget, bool $pretend): array
    {
        $sent = 0;
        $failed = 0;

        // Listed in one query rather than through the batching loop below.
        // That loop re-reads the remaining list after each batch, which only
        // shrinks because a real send writes a dispatch row - a pretend run
        // writes nothing, so the same fifty addresses would be read back and
        // printed over and over until the budget ran out.
        if ($pretend) {
            foreach ($this->remainingFor($post)->limit($budget)->get() as $subscriber) {
                $this->line("  would send to {$subscriber->email}");
                $sent++;
                $budget--;
            }

            return [$sent, 0, $budget];
        }

        // Re-queried per batch rather than iterated as one cursor: each send
        // writes a dispatch row, which changes the result of this query, and a
        // cursor opened before the first send would hand back rows that have
        // since been mailed.
        while ($budget > 0) {
            $batch = $this->remainingFor($post)->limit(min($budget, 50))->get();

            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $subscriber) {
                if ($budget <= 0) {
                    break;
                }

                $budget--;

                // Claimed before the send, not after. The unique index is what
                // stops two overlapping runs mailing the same person twice, and
                // it can only do that if the row exists before the message
                // leaves. A claim that loses the race throws, and this
                // subscriber is simply skipped.
                try {
                    DB::table('newsletter_dispatches')->insert([
                        'blog_post_id' => $post->id,
                        'newsletter_subscriber_id' => $subscriber->id,
                        'sent_at' => now(),
                    ]);
                } catch (Throwable) {
                    continue;
                }

                try {
                    Mail::to($subscriber->email)->send(new BlogPostPublished($post, $subscriber));
                    $sent++;
                } catch (Throwable $e) {
                    $failed++;

                    // The claim is released so a later run retries this
                    // address: a refused connection or a greylisted recipient
                    // is a transient failure, and losing the message for it
                    // would be a silent hole in the send.
                    DB::table('newsletter_dispatches')
                        ->where('blog_post_id', $post->id)
                        ->where('newsletter_subscriber_id', $subscriber->id)
                        ->delete();

                    Log::warning('Newsletter send failed', [
                        'post' => $post->id,
                        'subscriber' => $subscriber->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return [$sent, $failed, $budget];
    }

    /**
     * Active subscribers who have not had this post.
     *
     * active() is the unsubscribe honoured at the point of sending: someone who
     * opts out halfway through a long run is not mailed by the rest of it.
     */
    private function remainingFor(BlogPost $post)
    {
        return NewsletterSubscriber::active()
            ->whereNotExists(function ($query) use ($post) {
                $query->select(DB::raw(1))
                    ->from('newsletter_dispatches')
                    ->whereColumn('newsletter_dispatches.newsletter_subscriber_id', 'newsletter_subscribers.id')
                    ->where('newsletter_dispatches.blog_post_id', $post->id);
            })
            ->orderBy('id');
    }
}
