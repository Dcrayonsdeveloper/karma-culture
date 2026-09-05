<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate reviews from delivered orders daily at 2am
Schedule::command('reviews:generate')->dailyAt('02:00');

// Check low stock products and alert admin daily at 8am
Schedule::command('stock:check-low')->dailyAt('08:00');

// Spot newly abandoned carts, and close the ones that converted or expired.
//
// NOTE: nothing on the production host runs `schedule:run` - there is no cron
// binary on the account and ~/logs/cron.log has been stale since January - so
// this entry is for a host that does. The admin section does not depend on it:
// it re-scans when the page is opened, and has a "Scan now" button.
Schedule::command('carts:detect-abandoned')->hourly();

// Send abandoned cart reminders daily at 10am
Schedule::command('cart:send-abandoned-reminders')->dailyAt('10:00');

// Notify subscribers when products are back in stock (every 2 hours)
Schedule::command('stock:notify-back-in-stock')->everyTwoHours();

// Refresh the About Us reel strip from Instagram, daily at 4am.
//
// Same caveat as the abandoned-cart entry above: nothing on this host runs
// `schedule:run`, so this is for a host that does. The Sync button on
// Homepage > About Reels is what fires here, and the admin screen says so.
Schedule::command('instagram:sync-reels')->dailyAt('04:00');
