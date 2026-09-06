<?php

use App\Models\BlogPost;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // What stops a post being mailed twice. The send is driven by the
        // scheduler rather than by the save that publishes the post, so
        // "have the subscribers had this one?" has to be answered by a
        // column rather than by the shape of the request - a stamp that is
        // written before the first message goes out and never cleared.
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->timestamp('newsletter_sent_at')->nullable()->after('published_at');
            $table->index(['is_published', 'newsletter_sent_at']);
        });

        // The unsubscribe link's key. A token rather than the row id: the link
        // lives in an inbox forever, so it has to be un-guessable (an id in a
        // URL lets anyone unsubscribe anyone) and it has to keep working, which
        // rules out a signed URL that a rotated APP_KEY would invalidate.
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->string('unsubscribe_token', 64)->nullable()->unique()->after('ip_address');
        });

        // One row per message actually handed to the mailer, which is what
        // makes a send resumable.
        //
        // The stamp on the post alone cannot do it. Mail here is SMTP and
        // synchronous - there is no queue worker on this host - so a send to a
        // few thousand addresses is a long-running command, and anything that
        // interrupts it (a deploy, a restart, a timeout) lands between two
        // subscribers. Stamp the post before the run and the rest of the list
        // silently never hears about the post; stamp it after and the next run
        // mails everyone who already got it a second time. A row per delivery
        // means the next run picks up exactly where this one stopped.
        //
        // The unique pair is the guarantee, held by the database rather than by
        // the loop: two overlapping runs cannot both send to the same person.
        Schema::create('newsletter_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('newsletter_subscriber_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at');
            $table->unique(['blog_post_id', 'newsletter_subscriber_id'], 'newsletter_dispatch_unique');
        });

        // Every post already live pre-dates this feature, so it is stamped as
        // sent. Without this, the first scheduled run reads the whole archive
        // as unsent and mails every subscriber once per historic post - the
        // failure mode where turning a feature on empties the list it was built
        // to serve.
        //
        // published() and not every row, which is the difference between
        // stamping history and burying the future: a draft, or a post dated
        // next Tuesday, is not live yet and must still be announced when it
        // is. Stamping those here would silently cost them their send.
        BlogPost::published()->whereNull('newsletter_sent_at')->update(['newsletter_sent_at' => now()]);

        // Tokens for the addresses already on the list. Chunked and per-row
        // because each one has to be different; the model mints them for
        // everyone who subscribes from here on.
        NewsletterSubscriber::whereNull('unsubscribe_token')
            ->select(['id'])
            ->chunkById(500, function ($subscribers) {
                foreach ($subscribers as $subscriber) {
                    $subscriber->forceFill([
                        'unsubscribe_token' => Str::random(48),
                    ])->save();
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_dispatches');

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'newsletter_sent_at']);
            $table->dropColumn('newsletter_sent_at');
        });

        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropUnique(['unsubscribe_token']);
            $table->dropColumn('unsubscribe_token');
        });
    }
};
