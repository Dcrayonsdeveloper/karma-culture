<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class NotificationService
{
    public function notify(User $user, string $type, array $data = [], ?\Illuminate\Mail\Mailable $mailable = null): void
    {
        // Read the preference row once and consult it for both channels.
        // Delegating the in-app half to notifyInApp() would look tidier but
        // would send this method back to the database for the same row again.
        $preferences = $this->getUserPreferences($user->id);

        if ($preferences->get("in_app_{$type}", true)) {
            $this->storeInApp(
                $user,
                $type,
                $data['title'] ?? $type,
                $data['content'] ?? '',
                $data,
                Notification::AUDIENCE_CUSTOMER
            );
        }

        // Send email if enabled and mailable provided
        if ($mailable && $preferences->get("email_{$type}", true)) {
            $this->notifyByEmail($user, $mailable);
        }
    }

    /**
     * Put one transactional email in front of a customer.
     *
     * Sent, not queued. This line was `Mail::to()->queue()`, and production runs
     * QUEUE_CONNECTION=database with no worker and no cron to start one - so
     * every order confirmation, shipping, delivery, return-approved and refund
     * mail this method has ever produced went into the `jobs` table and stopped
     * there. 52 were still sitting unsent when this was found, the oldest from
     * April, each one owed to a customer the checkout page had already told a
     * confirmation was on its way.
     *
     * The rest of the app already sends synchronously - the enquiry reply, the
     * low-stock alert, the back-in-stock notice and the abandoned-cart reminder
     * are all `Mail::to()->send()`, and the password-reset notification was
     * deliberately left unqueued for this same reason. This method was the only
     * caller that queued, which is why it was the only one that never arrived.
     *
     * Queuing is the right shape once a worker exists; that is a server change,
     * not something this method can decide. Until then, delivering beats
     * deferring.
     *
     * Every caller reaches here after its database work has committed - both
     * checkout controllers dispatch OrderPlaced outside the transaction - so the
     * SMTP round trip holds no locks. It does cost the request about three
     * seconds against Gmail, which is the price of the mail arriving at all.
     *
     * The failure is logged and swallowed on purpose: the order, shipment or
     * refund behind it has already happened, and turning an undeliverable
     * address into a 500 would cost the customer the page confirming it.
     * `Throwable` rather than `Exception` because a template referencing a
     * missing variable surfaces as an `Error` out of the compiled view - and
     * these templates have never once been rendered for delivery, so that is a
     * live possibility rather than a theoretical one.
     */
    public function notifyByEmail(User $user, \Illuminate\Mail\Mailable $mailable): void
    {
        // Every one of these templates links back to the shop with route(), and
        // route() resolves against the incoming request's host. That did not
        // matter while the mail was queued: a worker renders with no request, so
        // the links fell back to APP_URL. Sending inside the request would have
        // handed that host to the caller instead - and this app trusts proxies
        // with `at: '*'`, accepts X-Forwarded-Host and registers no trusted-host
        // list, so a checkout carrying `X-Forwarded-Host: example.invalid` would
        // produce a genuine, deliverable order confirmation from us whose "View
        // Your Order" button points at somebody else's domain.
        //
        // Pinning the root for the duration of the render keeps the links the
        // queue used to produce. The scheme has to be pinned with it, because
        // forceRootUrl fixes the host but the generator still reads the scheme
        // off the current request - a link built during an http request would
        // otherwise reach the customer as http on an https-only shop. Both are
        // restored in a finally: the response for this same request is built
        // after we return and has no business being rewritten.
        $root = (string) config('app.url');
        URL::forceRootUrl($root);
        URL::forceScheme(parse_url($root, PHP_URL_SCHEME) ?: 'https');

        try {
            Mail::to($user->email)->send($mailable);
        } catch (\Throwable $e) {
            // Which mailable and which exception class: that is what tells a
            // rejected recipient apart from a failed handshake, a refused
            // password and a broken template. The old line recorded neither, so
            // a silent mail failure left nothing to work from but the words
            // "Failed to send email notification".
            Log::error('Failed to send email notification', [
                'user_id' => $user->id,
                'mailable' => $mailable::class,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        } finally {
            URL::forceRootUrl(null);
            URL::forceScheme(null);
        }
    }

    /**
     * Write a customer-facing in-app notification, honouring the toggle.
     *
     * This used to insert the row unconditionally, and app/Listeners/
     * SendOrderNotification.php calls it directly for order_cancelled and for
     * return_<status> rather than going through notify(). Those two types
     * therefore ignored the switch the customer had turned off on the
     * notification preferences screen and kept arriving anyway. The check
     * belongs here so that every caller gets it, however it arrives.
     */
    public function notifyInApp(User $user, string $type, string $title, string $content, array $data = []): void
    {
        if (! $this->getUserPreferences($user->id)->get("in_app_{$type}", true)) {
            return;
        }

        $this->storeInApp($user, $type, $title, $content, $data, Notification::AUDIENCE_CUSTOMER);
    }

    /**
     * Notify every admin, one row each, on the admin side of the bell.
     *
     * These rows carry audience = admin, which is what keeps "Your order has
     * been confirmed" out of the admin bell when the admin also shops: an admin
     * is a users row with role = 'admin', so user_id alone cannot tell the two
     * kinds of notification apart.
     *
     * Customer notification preferences are deliberately not consulted. They
     * describe a shopper's own order updates and there is no admin-facing UI
     * for these types, so a shopper who muted order_placed would otherwise
     * silence the store's new-order alerts as well.
     *
     * Returns how many admins were notified; a store with no admin rows simply
     * gets 0.
     */
    public function notifyAdmins(string $type, string $title, string $content, array $data = []): int
    {
        $notified = 0;

        // Only the id is ever used, so there is no reason to hydrate whole
        // user rows just to write a notification against each of them.
        foreach (User::where('role', 'admin')->select('id')->get() as $admin) {
            $this->storeInApp($admin, $type, $title, $content, $data, Notification::AUDIENCE_ADMIN);
            $notified++;
        }

        return $notified;
    }

    public function getUserPreferences(int $userId): \Illuminate\Support\Collection
    {
        $prefs = NotificationPreference::where('user_id', $userId)->first();

        if (! $prefs) {
            return collect($this->getDefaultPreferences());
        }

        return collect($prefs->preferences);
    }

    public function updatePreferences(int $userId, array $preferences): NotificationPreference
    {
        return NotificationPreference::updateOrCreate(
            ['user_id' => $userId],
            ['preferences' => $preferences]
        );
    }

    public function bulkNotify(array $userIds, string $type, array $data = []): int
    {
        $sent = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $this->notify($user, $type, $data);
                $sent++;
            }
        }

        return $sent;
    }

    public function getDefaultPreferences(): array
    {
        return [
            'email_order_placed' => true,
            'email_order_shipped' => true,
            'email_order_delivered' => true,
            'email_order_cancelled' => true,
            'email_return_approved' => true,
            'email_refund_processed' => true,
            'email_price_drop' => true,
            'email_back_in_stock' => true,
            'in_app_order_placed' => true,
            'in_app_order_shipped' => true,
            'in_app_order_delivered' => true,
            'in_app_order_cancelled' => true,
            'in_app_return_approved' => true,
            'in_app_refund_processed' => true,
            'in_app_price_drop' => true,
            'in_app_back_in_stock' => true,
        ];
    }

    /**
     * The single place a notification row is written. Callers decide whether a
     * preference allows it; this just records what they asked for.
     */
    private function storeInApp(User $user, string $type, string $title, string $content, array $data, string $audience): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'audience' => $audience,
            'title' => $title,
            'content' => $content,
            'data' => $data,
            'channel' => 'database',
        ]);
    }
}
