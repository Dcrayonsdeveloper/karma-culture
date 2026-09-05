<?php

namespace App\Console\Commands;

use App\Models\AbandonedCart;
use App\Services\AbandonedCartService;
use Illuminate\Console\Command;

/**
 * Rewritten to go through AbandonedCartService, which closes two bugs the
 * original had.
 *
 * It stored `reminder_sent_at` and `reminder_count` in `carts.metadata`, and
 * that write bumped `carts.updated_at` - the very column its own eligibility
 * window (updated_at between 2 hours and 7 days ago) was measured against. A
 * reminded cart was pushed back into the middle of its own window, so it was
 * mailed again every three days for as long as it existed and could never age
 * out. State lives in `abandoned_carts` now and nothing here touches the cart.
 *
 * Its "has the customer ordered since?" check compared against that same
 * corrupted timestamp, so after the first reminder it only saw orders placed
 * since the last reminder rather than since real cart activity. Recovery is
 * recorded at checkout now, against the frozen last_activity_at of the episode.
 */
class SendAbandonedCartReminders extends Command
{
    protected $signature = 'cart:send-abandoned-reminders {--limit=100 : Maximum reminders to send in one run}';

    protected $description = 'Send recovery emails for carts that have been abandoned and are due a reminder';

    public function handle(AbandonedCartService $service): int
    {
        // Detect first, so a run is self-contained: a cart that went quiet since
        // the last run is picked up in the same pass that mails it.
        $sync = $service->sync();
        $this->info(sprintf('%d newly abandoned, %d recovered, %d expired.', $sync['detected'], $sync['recovered'], $sync['expired']));

        $limit = max(1, (int) $this->option('limit'));

        $due = AbandonedCart::query()
            ->open()
            // Guest carts carry no address anywhere, so there is nothing to mail.
            ->whereNotNull('user_id')
            ->where('reminder_count', '<', $service->maxReminders())
            ->where(function ($q) use ($service) {
                $q->whereNull('last_reminder_at')
                    ->orWhere('last_reminder_at', '<', now()->subHours($service->reminderCooldownHours()));
            })
            ->with(['cart.items.product', 'user'])
            ->orderBy('abandoned_at')
            ->limit($limit)
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($due as $episode) {
            // Each send is a blocking SMTP round trip and one bad address must
            // not abort the run - sendReminder() catches its own failures and
            // hands back the reason.
            if ($reason = $service->sendReminder($episode)) {
                $skipped++;
                $this->line("  #{$episode->id} skipped: {$reason}");

                continue;
            }

            $sent++;
        }

        $this->info("Sent {$sent} abandoned cart reminder(s), skipped {$skipped}.");

        return self::SUCCESS;
    }
}
