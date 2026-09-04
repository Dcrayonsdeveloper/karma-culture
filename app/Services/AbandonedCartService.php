<?php

namespace App\Services;

use App\Mail\AbandonedCartReminder;
use App\Models\AbandonedCart;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Detects abandoned carts, tracks recovery, and sends recovery mail.
 *
 * Three rules govern every line in this class, and breaking any of them
 * silently corrupts the feature's own data:
 *
 * 1. NEVER WRITE TO `carts`. `carts.updated_at` is the abandonment clock. The
 *    old cron stored its bookkeeping in `carts.metadata`; the write bumped
 *    `updated_at`, which pushed the cart back inside the idle window, so the
 *    same customer was mailed every three days forever. All state lives in
 *    `abandoned_carts` instead.
 *
 * 2. NEVER CALL Cart::recalculate(). It is not a read. It re-prices lines from
 *    the product, can auto-attach a coupon the customer never chose, and writes
 *    the cart row - and an idle cart is exactly the case where a flash sale or
 *    an expired coupon makes it dirty.
 *
 * 3. NEVER TRUST `carts.subtotal` / `carts.total`. Checkout empties a cart with
 *    a query-builder mass delete, which fires no model events, so those columns
 *    keep the figures of the order that emptied them. Value is always summed
 *    from `cart_items`, or read from this table's snapshot.
 */
class AbandonedCartService
{
    /** Cache key guarding the auto-sync that runs when an admin opens the list. */
    private const SYNC_LOCK = 'abandoned_carts.last_sync';

    /**
     * How long a cart must sit untouched before it counts as abandoned.
     *
     * Defaults to 2 hours because that is the window the pre-existing
     * `cart:send-abandoned-reminders` command has always used - changing the
     * default would silently redefine abandonment for a live store.
     */
    public function thresholdHours(): int
    {
        return $this->intSetting('abandoned_cart_threshold_hours', 2, 1, 720);
    }

    /** After this long an unrecovered episode is written off. */
    public function expiryDays(): int
    {
        return $this->intSetting('abandoned_cart_expiry_days', 30, 1, 365);
    }

    /** Minimum gap between two reminders for the same episode. */
    public function reminderCooldownHours(): int
    {
        return $this->intSetting('abandoned_cart_reminder_cooldown_hours', 72, 1, 8760);
    }

    /** Hard cap on reminders per episode, so nobody is nagged indefinitely. */
    public function maxReminders(): int
    {
        return $this->intSetting('abandoned_cart_max_reminders', 3, 1, 10);
    }

    /** How long a recovery link keeps working after the cart was abandoned. */
    public function recoveryLinkDays(): int
    {
        return $this->intSetting('abandoned_cart_recovery_link_days', 30, 1, 365);
    }

    /** Window behind the "Recently abandoned" filter. */
    public function recentHours(): int
    {
        return $this->intSetting('abandoned_cart_recent_hours', 24, 1, 720);
    }

    /**
     * Open episodes for carts that have gone quiet, and close the ones that
     * converted or aged out. Safe to call repeatedly - it is idempotent.
     *
     * @return array{detected:int,recovered:int,expired:int,refreshed:int}
     */
    public function sync(): array
    {
        $reconciled = $this->reconcile();

        return ['detected' => $this->detect()] + $reconciled;
    }

    /**
     * sync(), but at most once every few minutes.
     *
     * This exists because neither a queue worker nor `schedule:run` runs on the
     * production host - the scheduled command has never fired once. Without a
     * sync on page load the admin screen would simply always be empty, so the
     * listing triggers one, throttled so a burst of page loads cannot turn into
     * a burst of scans. A failure here must never break the page.
     */
    public function syncThrottled(int $everySeconds = 300): void
    {
        $cache = cache();

        if ($cache->get(self::SYNC_LOCK)) {
            return;
        }

        // Claim the window first. If the scan then throws, the lock still holds
        // and the next admin page load is not dragged into the same failure.
        $cache->put(self::SYNC_LOCK, now()->toDateTimeString(), $everySeconds);

        try {
            $this->sync();
        } catch (\Throwable $e) {
            Log::error('Abandoned cart auto-sync failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Open an episode for every cart that has gone quiet and does not already
     * have one.
     */
    public function detect(int $limit = 500): int
    {
        $threshold = now()->subHours($this->thresholdHours());
        $floor = now()->subDays($this->expiryDays());
        $currency = (string) Setting::get('currency', 'INR');

        $carts = Cart::query()
            ->whereHas('items')
            // A bare timestamp comparison keeps the ['user_id','updated_at']
            // index usable; wrapping updated_at in a function would not.
            ->where('carts.updated_at', '<=', $threshold)
            ->where('carts.updated_at', '>=', $floor)
            ->whereDoesntHave('abandonedCarts', function ($q) {
                // The nested where() is load-bearing. whereHas already added
                // `abandoned_carts.cart_id = carts.id`, and AND binds tighter
                // than OR: written flat, the second clause escapes the
                // correlation and reads "ANY episode anywhere is newer than
                // this cart", so one recent episode would stop every older
                // cart in the table from ever being detected.
                $q->where(function ($inner) {
                    // Two reasons to skip: an episode is already open for this
                    // cart, or some episode has already recorded this exact
                    // quiet period - so an archived or expired one is not
                    // immediately re-opened while the cart sits untouched.
                    $inner->whereIn('recovery_status', AbandonedCart::OPEN_STATUSES)
                        ->orWhereColumn('abandoned_carts.last_activity_at', '>=', 'carts.updated_at');
                });
            })
            ->with('items')
            ->limit($limit)
            ->get();

        $opened = 0;

        foreach ($carts as $cart) {
            $lastActivity = $cart->updated_at;

            $episode = AbandonedCart::firstOrNew([
                'cart_id' => $cart->id,
                'abandoned_at' => $lastActivity->copy()->addHours($this->thresholdHours()),
            ]);

            if ($episode->exists) {
                continue;
            }

            $episode->fill($this->snapshot($cart) + [
                'user_id' => $cart->user_id,
                'session_id' => $cart->session_id,
                'token' => $this->newToken(),
                'last_activity_at' => $lastActivity,
                'currency' => $currency,
                'recovery_status' => AbandonedCart::STATUS_PENDING,
            ]);

            try {
                $episode->save();
                $opened++;
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                // Another process detected the same cart at the same instant.
                // The unique key on (cart_id, abandoned_at) is what makes this
                // safe to ignore rather than something to guard with a lock.
                continue;
            }
        }

        return $opened;
    }

    /**
     * Close episodes that converted or aged out, and re-snapshot ones whose
     * basket has moved on.
     *
     * @return array{recovered:int,expired:int,refreshed:int}
     */
    public function reconcile(): array
    {
        $recovered = 0;
        $expired = 0;
        $refreshed = 0;
        $expiryCutoff = now()->subDays($this->expiryDays());
        $idleCutoff = now()->subHours($this->thresholdHours());

        AbandonedCart::query()
            ->open()
            ->with(['cart.items', 'recoveredOrder'])
            ->chunkById(200, function ($episodes) use (&$recovered, &$expired, &$refreshed, $expiryCutoff, $idleCutoff) {
                foreach ($episodes as $episode) {
                    $cart = $episode->cart;

                    if (! $cart) {
                        continue;
                    }

                    // An episode already linked to an order is waiting on that
                    // order to become a sale. Prepaid checkout hands the
                    // customer to the gateway with the order still unpaid, so
                    // this is where an abandoned PAYMENT finally settles one way
                    // or the other.
                    if ($episode->recovered_order_id) {
                        if ($episode->recoveredOrder && $this->orderCountsAsRecovery($episode->recoveredOrder)) {
                            $this->closeAsRecovered($episode, $episode->recoveredOrder);
                            $recovered++;
                        }

                        continue;
                    }

                    $liveItems = $cart->items->count();

                    if ($liveItems === 0) {
                        if ($order = $this->findConvertingOrder($episode)) {
                            $this->attachOrder($episode, $order);

                            if ($this->orderCountsAsRecovery($order)) {
                                $this->closeAsRecovered($episode, $order);
                                $recovered++;
                            }

                            continue;
                        }
                    } elseif ($cart->updated_at->gt($episode->last_activity_at)) {
                        // The customer came back and changed the basket. That is
                        // one continuing episode, not a new one - re-snapshot it
                        // so the clock and the figures describe the basket that
                        // is actually sitting there.
                        //
                        // Only once the NEW basket has itself gone quiet, though.
                        // Refreshing while they are still shopping would set
                        // abandoned_at in the future, and the screen would list -
                        // and the reminder job would email - a cart the customer
                        // has in front of them right now.
                        if ($cart->updated_at->lte($idleCutoff)) {
                            $this->refresh($episode, $cart);
                            $refreshed++;
                        }

                        continue;
                    }

                    if ($episode->abandoned_at->lt($expiryCutoff)) {
                        $episode->update(['recovery_status' => AbandonedCart::STATUS_EXPIRED]);
                        $expired++;
                    }
                }
            });

        return ['recovered' => $recovered, 'expired' => $expired, 'refreshed' => $refreshed];
    }

    /**
     * Is this order actually a recovery, or just an order that exists?
     *
     * Prepaid checkout creates the order BEFORE handing the customer to the
     * gateway, so an order row on its own proves nothing: the customer can walk
     * away at the payment page, which is the exact behaviour this feature is
     * meant to chase. Cancelled and returned orders are not recoveries either.
     * Order::applySaleFilter is the store-wide definition of a sale (it counts
     * COD, where the cash follows the parcel), so reuse it rather than writing
     * a second one that can drift.
     */
    private function orderCountsAsRecovery(Order $order): bool
    {
        return Order::query()->whereKey($order->getKey())->countsAsSale()->exists();
    }

    /**
     * Called from checkout the moment an order is created from a cart.
     *
     * This is the only exact attribution there is: `orders` carries no cart_id
     * and no session_id, so once the transaction ends nothing links the two
     * again. Everything else in this class is a heuristic; this is not.
     */
    public function markRecoveredFromCheckout(Cart $cart, Order $order): void
    {
        try {
            AbandonedCart::query()
                ->where('cart_id', $cart->id)
                ->open()
                ->get()
                ->each(function (AbandonedCart $episode) use ($order) {
                    // Record which order this basket became straight away - that
                    // link is only knowable here. Whether it COUNTS as a recovery
                    // waits on the money: a prepaid order is created before the
                    // customer reaches the gateway, so calling it recovered now
                    // would score every abandoned payment as a success. reconcile()
                    // promotes it once the order qualifies as a sale.
                    $this->attachOrder($episode, $order);

                    if ($this->orderCountsAsRecovery($order)) {
                        $this->closeAsRecovered($episode, $order);
                    }
                });
        } catch (\Throwable $e) {
            // Recovery bookkeeping must never be able to fail a customer's
            // order. Losing the attribution is a reporting gap; losing the
            // order is a lost sale.
            Log::error('Failed to mark abandoned cart recovered', [
                'cart_id' => $cart->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Why this episode cannot be reminded right now, or null if it can be.
     *
     * Returned as a sentence rather than a bool so the admin screen can say
     * what is wrong instead of just greying a button out.
     */
    public function reminderBlockedReason(AbandonedCart $episode): ?string
    {
        if (! $episode->isOpen()) {
            return 'This cart is '.AbandonedCart::statusLabel($episode->recovery_status).' and no longer needs a reminder.';
        }

        if ($episode->liveItemCount() === 0) {
            return 'This cart is now empty, so there is nothing to remind the customer about.';
        }

        // The customer is shopping right now. Reconcile only re-snapshots an
        // episode once the new basket has itself gone quiet, so an episode can
        // legitimately be open while its cart is live - and "You left something
        // in your cart!" to somebody looking at that cart is the worst email
        // this feature could send.
        $cart = $episode->cart;
        if ($cart && $cart->updated_at->gt(now()->subHours($this->thresholdHours()))) {
            return 'The customer is active in this cart right now, so a reminder would be premature.';
        }

        if ($episode->user && $episode->user->trashed()) {
            // The account is closed. Mailing it is at best pointless and at
            // worst a privacy problem, and the recovery link would not open.
            return 'This customer account has been deleted, so no reminder can be sent.';
        }

        if (! $episode->contactEmail()) {
            return 'No email address is on file for this cart, so a reminder cannot be sent.';
        }

        if ($episode->reminder_count >= $this->maxReminders()) {
            return 'This customer has already had the maximum of '.$this->maxReminders().' reminders for this cart.';
        }

        if ($episode->last_reminder_at && $episode->last_reminder_at->gt(now()->subHours($this->reminderCooldownHours()))) {
            $next = $episode->last_reminder_at->copy()->addHours($this->reminderCooldownHours());

            return 'A reminder was already sent '.$episode->last_reminder_at->diffForHumans().'. The next one can go out '.$next->diffForHumans().'.';
        }

        if ($this->recoveryLinkExpiresAt($episode)->isPast()) {
            return 'The recovery link for this cart has expired, so a reminder would lead nowhere.';
        }

        return null;
    }

    /**
     * Send the recovery email. Returns null on success, or the failure message.
     *
     * Deliberately synchronous. `Mail::queue()` writes a row to the `jobs`
     * table and no worker has ever run on this host - 51 mails have been
     * sitting there unsent since April. Every other mail call site in the app
     * uses ->send() for the same reason.
     */
    public function sendReminder(AbandonedCart $episode): ?string
    {
        if ($reason = $this->reminderBlockedReason($episode)) {
            return $reason;
        }

        $episode->loadMissing('cart.items.product', 'user');

        try {
            Mail::to($episode->contactEmail())->send(
                new AbandonedCartReminder($episode->cart, $episode->recoveryUrl())
            );
        } catch (\Throwable $e) {
            Log::error('Abandoned cart reminder failed to send', [
                'abandoned_cart_id' => $episode->id,
                'error' => $e->getMessage(),
            ]);

            // Recorded, not swallowed: the screen shows the failure so nobody
            // reads "0 reminders" as "the customer was never worth mailing".
            $episode->update(['last_reminder_error' => Str::limit($e->getMessage(), 250, '')]);

            return 'The reminder could not be sent: '.$e->getMessage();
        }

        $episode->update([
            'reminder_count' => $episode->reminder_count + 1,
            'last_reminder_at' => now(),
            'last_reminder_error' => null,
            'recovery_status' => $episode->recovery_status === AbandonedCart::STATUS_PENDING
                ? AbandonedCart::STATUS_REMINDER_SENT
                : $episode->recovery_status,
        ]);

        return null;
    }

    public function markContacted(AbandonedCart $episode): void
    {
        $episode->update([
            'recovery_status' => AbandonedCart::STATUS_CONTACTED,
            'last_contacted_at' => now(),
        ]);
    }

    /**
     * Mark an episode recovered by hand - for a sale that happened over the
     * phone or in a store and never went through this cart.
     */
    public function markRecoveredManually(AbandonedCart $episode, ?Order $order = null): void
    {
        $this->closeAsRecovered($episode, $order);
    }

    public function archive(AbandonedCart $episode): void
    {
        $episode->update(['recovery_status' => AbandonedCart::STATUS_ARCHIVED]);
    }

    public function recoveryLinkExpiresAt(AbandonedCart $episode): Carbon
    {
        return $episode->abandoned_at->copy()->addDays($this->recoveryLinkDays());
    }

    /**
     * Headline numbers for the listing page and the dashboard.
     *
     * Every figure comes from `abandoned_carts`, so "value" is the snapshot
     * taken when each basket was abandoned - which is the question being asked
     * ("how much walked out of the door"), and the only figure that survives a
     * cart converting.
     *
     * @return array<string,float|int>
     */
    public function stats(): array
    {
        $byStatus = AbandonedCart::query()
            ->selectRaw('recovery_status, COUNT(*) as total, COALESCE(SUM(total), 0) as value')
            ->groupBy('recovery_status')
            ->get()
            ->keyBy('recovery_status');

        $count = fn (array $statuses) => (int) collect($statuses)
            ->sum(fn ($s) => (int) ($byStatus[$s]->total ?? 0));
        $value = fn (array $statuses) => (float) collect($statuses)
            ->sum(fn ($s) => (float) ($byStatus[$s]->value ?? 0));

        $total = (int) $byStatus->sum('total');
        $recovered = $count([AbandonedCart::STATUS_RECOVERED]);

        // Archived episodes are ones an admin threw away, so counting them in
        // the denominator would make the recovery rate look worse every time
        // somebody tidied up a test basket.
        $considered = $total - $count([AbandonedCart::STATUS_ARCHIVED]);

        return [
            'total' => $total,
            'open' => $count(AbandonedCart::OPEN_STATUSES),
            'open_value' => $value(AbandonedCart::OPEN_STATUSES),
            'today' => (int) AbandonedCart::whereDate('abandoned_at', today())->count(),
            'this_week' => (int) AbandonedCart::where('abandoned_at', '>=', now()->startOfWeek())->count(),
            'recovered' => $recovered,
            'recovery_rate' => $considered > 0 ? round($recovered / $considered * 100, 1) : 0.0,
            'recovered_revenue' => (float) AbandonedCart::query()
                ->where('recovery_status', AbandonedCart::STATUS_RECOVERED)
                ->whereNotNull('recovered_order_id')
                ->join('orders', 'orders.id', '=', 'abandoned_carts.recovered_order_id')
                ->tap(fn ($q) => Order::applySaleFilter($q))
                ->sum('orders.total'),
        ];
    }

    /** @return array<string,int|float|string> */
    private function snapshot(Cart $cart): array
    {
        $items = $cart->relationLoaded('items') ? $cart->items : $cart->items()->get();

        $subtotal = (float) $items->sum('total');

        return [
            'item_count' => $items->count(),
            'quantity' => (int) $items->sum('quantity'),
            // Summed from the lines, never read from carts.subtotal - see the
            // class docblock for why that column cannot be trusted.
            'subtotal' => $subtotal,
            'discount' => (float) $cart->discount,
            'tax' => (float) $cart->tax,
            'shipping' => (float) $cart->shipping,
            'total' => max(0, $subtotal - (float) $cart->discount + (float) $cart->tax + (float) $cart->shipping),
        ];
    }

    private function refresh(AbandonedCart $episode, Cart $cart): void
    {
        try {
            $episode->update($this->snapshot($cart) + [
                'last_activity_at' => $cart->updated_at,
                'abandoned_at' => $cart->updated_at->copy()->addHours($this->thresholdHours()),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // (cart_id, abandoned_at) already exists - lowering the threshold
            // can move a refreshed episode onto a timestamp a closed one already
            // holds. Leaving the snapshot stale for one cycle is harmless;
            // letting it bubble would abort the whole reconcile pass, and
            // sync() reconciles before it detects, so it would stop detection too.
            Log::warning('Abandoned cart refresh collided with an existing episode', [
                'abandoned_cart_id' => $episode->id,
                'cart_id' => $cart->id,
            ]);
        }
    }

    /** Record which order a basket became, without yet calling it a recovery. */
    private function attachOrder(AbandonedCart $episode, Order $order): void
    {
        if ($episode->recovered_order_id === $order->id) {
            return;
        }

        $episode->update(['recovered_order_id' => $order->id]);
    }

    private function closeAsRecovered(AbandonedCart $episode, ?Order $order): void
    {
        $episode->update([
            'recovery_status' => AbandonedCart::STATUS_RECOVERED,
            'recovered_at' => now(),
            'recovered_order_id' => $order?->id ?? $episode->recovered_order_id,
        ]);
    }

    /**
     * Best-effort attribution for episodes the checkout hook did not catch -
     * carts converted through the API, or abandoned before this feature shipped.
     *
     * Guest episodes are never matched: `orders` carries no session_id, so
     * there is genuinely nothing to join on, and guessing would report carts as
     * recovered that were not. The same limitation is already documented in the
     * Analytics report, which refuses to publish a cart-to-order rate for
     * exactly this reason.
     */
    private function findConvertingOrder(AbandonedCart $episode): ?Order
    {
        if (! $episode->user_id) {
            return null;
        }

        return Order::query()
            ->where('user_id', $episode->user_id)
            ->where('created_at', '>=', $episode->last_activity_at)
            ->orderBy('created_at')
            ->first();
    }

    private function newToken(): string
    {
        do {
            $token = Str::random(64);
        } while (AbandonedCart::where('token', $token)->exists());

        return $token;
    }

    private function intSetting(string $key, int $default, int $min, int $max): int
    {
        $value = Setting::get($key, $default);

        if (! is_numeric($value)) {
            return $default;
        }

        return (int) max($min, min($max, (int) $value));
    }
}
