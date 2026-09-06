<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\NewsletterSubscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Answer "why has nobody had the newsletter?" without a database client.
 *
 * The send is driven by the scheduler and writes nothing to the log when it
 * has no work, so the healthy case and every broken case look identical from
 * the outside: no mail. This prints the four things that decide whether a post
 * goes out - is it live, has it been stamped, is anyone on the list, and did a
 * message actually reach the mailer - so the answer is read rather than
 * guessed.
 */
class NewsletterStatus extends Command
{
    protected $signature = 'newsletter:status
                            {--posts=10 : Recent posts to list}
                            {--email= : Report on one address instead}';

    protected $description = 'Show why blog posts have or have not been mailed to the newsletter list';

    public function handle(): int
    {
        if ($address = $this->option('email')) {
            return $this->reportOn($address);
        }

        $this->newLine();
        $this->components->info('Newsletter list');

        $total = NewsletterSubscriber::count();
        $active = NewsletterSubscriber::active()->count();

        $this->line("  subscribers: {$total} total, {$active} active, ".($total - $active).' unsubscribed');

        if ($active === 0) {
            $this->warn('  Nobody active - a post published now is stamped as done without a single message.');
        }

        $this->newLine();
        $this->line('  most recent signups:');

        foreach (NewsletterSubscriber::orderByDesc('id')->limit(8)->get() as $subscriber) {
            $this->line(sprintf(
                '    #%-4s %-34s %-9s %s  %s',
                $subscriber->id,
                mb_strimwidth($subscriber->email, 0, 34, '...'),
                $subscriber->is_active ? 'active' : 'OFF',
                $subscriber->created_at?->toDateTimeString() ?? '?',
                $subscriber->source ?: '-'
            ));
        }

        $this->newLine();
        $this->components->info('Recent posts');

        $posts = BlogPost::orderByDesc('id')->limit((int) $this->option('posts'))->get();

        if ($posts->isEmpty()) {
            $this->line('  none');
        }

        foreach ($posts as $post) {
            $delivered = DB::table('newsletter_dispatches')->where('blog_post_id', $post->id)->count();

            $this->line(sprintf(
                '  #%-4s %-38s %s',
                $post->id,
                mb_strimwidth($post->title ?? '', 0, 38, '...'),
                $this->verdict($post, $delivered)
            ));
        }

        $this->newLine();
        $this->components->info('Waiting to go out');

        $awaiting = BlogPost::awaitingNewsletter()->get();
        $this->line('  posts: '.$awaiting->count());

        foreach ($awaiting as $post) {
            $left = NewsletterSubscriber::active()
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                    ->from('newsletter_dispatches')
                    ->whereColumn('newsletter_dispatches.newsletter_subscriber_id', 'newsletter_subscribers.id')
                    ->where('newsletter_dispatches.blog_post_id', $post->id))
                ->count();

            $this->line("    #{$post->id} {$post->title} - {$left} still to mail");
        }

        $this->newLine();
        $this->components->info('Mail transport');

        $mailer = config('mail.default');
        $this->line('  mailer: '.$mailer);

        if ($mailer === 'smtp') {
            $smtp = config('mail.mailers.smtp');
            $this->line('  host: '.($smtp['host'] ?? '-').':'.($smtp['port'] ?? '-'));
            $this->line('  username: '.($smtp['username'] ?: '(none)'));
            $this->line('  password: '.(filled($smtp['password'] ?? null) ? 'set' : 'NOT SET'));
        }

        $this->line('  from: '.config('mail.from.address').' ("'.config('mail.from.name').'")');

        if ($mailer === 'log') {
            $this->warn('  Mailer is "log" - messages are written to storage/logs and never sent.');
        }

        $this->newLine();
        $this->components->info('Delivery record');
        $this->line('  messages handed to the mailer: '.DB::table('newsletter_dispatches')->count());

        $last = DB::table('newsletter_dispatches')->max('sent_at');
        $this->line('  most recent: '.($last ?: 'never'));

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * One address, for the "I subscribed and got nothing" question.
     *
     * Which is three different faults wearing the same face: the address is
     * not on the list at all, it is on the list but unsubscribed, or it was
     * mailed and the message did not survive the trip. Only the last one is a
     * delivery problem, and they are told apart here rather than guessed at.
     */
    private function reportOn(string $address): int
    {
        $subscriber = NewsletterSubscriber::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($address))])->first();

        $this->newLine();

        if (! $subscriber) {
            $this->error("  {$address} is not on the list - the subscribe never saved.");
            $this->newLine();

            return self::SUCCESS;
        }

        $this->line("  {$subscriber->email}");
        $this->line('  subscriber #'.$subscriber->id.', joined '.($subscriber->created_at?->toDateTimeString() ?? '?'));
        $this->line('  source: '.($subscriber->source ?: '-'));

        if (! $subscriber->is_active) {
            $this->warn('  UNSUBSCRIBED on '.($subscriber->unsubscribed_at?->toDateTimeString() ?? '?').' - nothing is sent to this address.');
            $this->newLine();

            return self::SUCCESS;
        }

        $this->info('  active - on the list');

        $rows = DB::table('newsletter_dispatches')
            ->join('blog_posts', 'blog_posts.id', '=', 'newsletter_dispatches.blog_post_id')
            ->where('newsletter_subscriber_id', $subscriber->id)
            ->orderByDesc('newsletter_dispatches.sent_at')
            ->get(['blog_posts.id', 'blog_posts.title', 'newsletter_dispatches.sent_at']);

        $this->newLine();
        $this->line('  messages sent to this address: '.$rows->count());

        foreach ($rows as $row) {
            $this->line("    #{$row->id} ".mb_strimwidth($row->title, 0, 40, '...').' - '.$row->sent_at);
        }

        if ($rows->isNotEmpty()) {
            $this->newLine();
            $this->warn('  These left the server and SMTP accepted them. If they never arrived,');
            $this->warn('  the fault is delivery (spam folder, or the sending domain), not the send.');
        }

        $this->newLine();

        return self::SUCCESS;
    }

    private function verdict(BlogPost $post, int $delivered): string
    {
        if (! $post->is_published) {
            return 'draft - not announced until it is published';
        }

        if ($post->published_at && $post->published_at->isFuture()) {
            return 'scheduled for '.$post->published_at->toDateTimeString();
        }

        if (! $post->newsletter_sent_at) {
            return 'LIVE, waiting for the next run';
        }

        if ($delivered === 0) {
            return 'stamped '.$post->newsletter_sent_at->toDateTimeString().' but 0 mailed - use --resend';
        }

        return "mailed to {$delivered} on ".$post->newsletter_sent_at->toDateTimeString();
    }
}
