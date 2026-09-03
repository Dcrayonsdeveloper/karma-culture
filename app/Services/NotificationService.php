<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

    public function notifyByEmail(User $user, \Illuminate\Mail\Mailable $mailable): void
    {
        try {
            Mail::to($user->email)->queue($mailable);
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
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
