<?php

namespace App\Console\Commands;

use App\Mail\AbandonedCartReminder;
use App\Models\Cart;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'cart:send-abandoned-reminders';
    protected $description = 'Send email reminders for abandoned carts (idle > 2 hours, < 7 days)';

    public function handle(): int
    {
        $carts = Cart::whereNotNull('user_id')
            ->whereHas('items')
            ->where('updated_at', '<', now()->subHours(2))
            ->where('updated_at', '>', now()->subDays(7))
            ->where(function ($q) {
                $q->whereNull('metadata->reminder_sent_at')
                  ->orWhere('metadata->reminder_sent_at', '<', now()->subDays(3)->toDateTimeString());
            })
            ->with(['user', 'items.product'])
            ->get();

        if ($carts->isEmpty()) {
            $this->info('No abandoned carts to remind.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($carts as $cart) {
            if (!$cart->user || !$cart->user->email) {
                continue;
            }

            // Skip if user has placed an order since cart was last updated
            $hasRecentOrder = $cart->user->orders()
                ->where('created_at', '>', $cart->updated_at)
                ->exists();

            if ($hasRecentOrder) {
                continue;
            }

            Mail::to($cart->user->email)->send(new AbandonedCartReminder($cart));

            $metadata = $cart->metadata ?? [];
            $metadata['reminder_sent_at'] = now()->toDateTimeString();
            $metadata['reminder_count'] = ($metadata['reminder_count'] ?? 0) + 1;
            $cart->update(['metadata' => $metadata]);

            $sent++;
        }

        $this->info("Sent {$sent} abandoned cart reminder(s).");
        return self::SUCCESS;
    }
}
