<?php

namespace App\Console\Commands;

use App\Mail\BackInStockNotification;
use App\Models\BackInStockSubscription;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyBackInStock extends Command
{
    protected $signature = 'stock:notify-back-in-stock';
    protected $description = 'Notify subscribers when out-of-stock products are back in stock';

    public function handle(NotificationService $notifications): int
    {
        // 'user' as well as 'product': which of the two ways a subscriber is
        // told depends on whether the row belongs to an account, and asking per
        // row inside the loop would be a query per subscription.
        $subscriptions = BackInStockSubscription::where('notified', false)
            ->with(['product', 'user'])
            ->get()
            ->filter(fn ($sub) => $sub->product && $sub->product->isInStock());

        if ($subscriptions->isEmpty()) {
            $this->info('No back-in-stock notifications to send.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($subscriptions as $sub) {
            // Left un-notified on failure so the next run picks the row up
            // again, which is what happened before too - except that one bad
            // address used to abort the whole command and strand every
            // subscription behind it.
            if (! $this->deliver($notifications, $sub)) {
                continue;
            }

            $sub->update(['notified' => true, 'notified_at' => now()]);
            $count++;
        }

        $this->info("Sent {$count} back-in-stock notification(s).");
        return self::SUCCESS;
    }

    /**
     * Tell one subscriber their product is back, and say whether the attempt
     * got far enough to count as told.
     *
     * This used to be a bare Mail::to()->send() for everybody, which bypassed
     * NotificationService entirely: the customer's email_back_in_stock and
     * in_app_back_in_stock switches were ignored, no in-app row was ever
     * written for a notification the preferences screen offers to turn off, and
     * every message went out synchronously inside the loop, so one slow SMTP
     * handshake stalled the entire run.
     *
     * Subscriptions are keyed by email and user_id is nullable, so a row raised
     * by a guest has no account and no preferences to consult. Those keep the
     * direct send they have always had - they asked for the mail and there is
     * nothing else to honour.
     */
    private function deliver(NotificationService $notifications, BackInStockSubscription $sub): bool
    {
        try {
            if ($sub->user) {
                $notifications->notify($sub->user, 'back_in_stock', [
                    'title' => 'Back in Stock',
                    'content' => "{$sub->product->name} is back in stock.",
                    'product_id' => $sub->product->id,
                    'product_slug' => $sub->product->slug,
                ], new BackInStockNotification($sub->product));

                return true;
            }

            Mail::to($sub->email)->send(new BackInStockNotification($sub->product));

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send a back-in-stock notification', [
                'subscription_id' => $sub->id,
                'product_id' => $sub->product_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
