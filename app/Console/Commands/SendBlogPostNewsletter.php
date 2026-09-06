<?php

namespace App\Console\Commands;

use App\Mail\BlogPostPublished;
use App\Models\BlogPost;
use App\Models\NewsletterSubscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
                            {--pretend : List what would be sent, send nothing}';

    protected $description = 'Email newly published blog posts to active newsletter subscribers';

    public function handle(): int
    {
        $budget = max(1, (int) $this->option('limit'));
        $pretend = (bool) $this->option('pretend');

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
     * @return array{0: int, 1: int, 2: int} sent, failed, remaining budget
     */
    private function sendPost(BlogPost $post, int $budget, bool $pretend): array
    {
        $sent = 0;
        $failed = 0;

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

                if ($pretend) {
                    $this->line("  would send to {$subscriber->email}");
                    $sent++;

                    continue;
                }

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
