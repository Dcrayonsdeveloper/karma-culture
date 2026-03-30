<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationPreferenceController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function edit(): View
    {
        $preferences = $this->notificationService->getUserPreferences(auth()->id());

        return view('account.notification-preferences', compact('preferences'));
    }

    public function update(Request $request): RedirectResponse
    {
        $defaults = $this->notificationService->getDefaultPreferences();
        $preferences = [];

        foreach (array_keys($defaults) as $key) {
            $preferences[$key] = $request->boolean($key);
        }

        $this->notificationService->updatePreferences(auth()->id(), $preferences);

        return back()->with('success', 'Notification preferences updated.');
    }
}
