<?php

namespace App\Console\Commands;

use App\Mail\BackInStockNotification;
use App\Models\BackInStockSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifyBackInStock extends Command
{
    protected $signature = 'stock:notify-back-in-stock';
    protected $description = 'Notify subscribers when out-of-stock products are back in stock';

    public function handle(): int
    {
        $subscriptions = BackInStockSubscription::where('notified', false)
            ->with('product')
            ->get()
            ->filter(fn ($sub) => $sub->product && $sub->product->isInStock());

        if ($subscriptions->isEmpty()) {
            $this->info('No back-in-stock notifications to send.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($subscriptions as $sub) {
            Mail::to($sub->email)->send(new BackInStockNotification($sub->product));
            $sub->update(['notified' => true, 'notified_at' => now()]);
            $count++;
        }

        $this->info("Sent {$count} back-in-stock notification(s).");
        return self::SUCCESS;
    }
}
