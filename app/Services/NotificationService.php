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
        $preferences = $this->getUserPreferences($user->id);

        // Always send in-app notification
        if ($preferences->get("in_app_{$type}", true)) {
            $this->notifyInApp($user, $type, $data['title'] ?? $type, $data['content'] ?? '', $data);
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

    public function notifyInApp(User $user, string $type, string $title, string $content, array $data = []): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'data' => $data,
            'channel' => 'database',
        ]);
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
}
