<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Rules\ValidationRules as V;
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
        // Keyed the way the form names its toggles, so a saved preference is
        // what the switch shows. Previously the view asked for
        // $preferences['order_placed_email'] while the service stored
        // 'email_order_placed', so every lookup missed and every switch
        // rendered on regardless of what the customer had saved.
        $stored = $this->notificationService->getUserPreferences(auth()->id());

        $preferences = [];

        foreach ($this->keyMap() as $field => $storageKey) {
            $preferences[$field] = (bool) $stored->get($storageKey, true);
        }

        return view('account.notification-preferences', compact('preferences'));
    }

    public function update(Request $request): RedirectResponse
    {
        $map = $this->keyMap();

        // Each toggle posts a hidden "0" followed by a checkbox "1", so the
        // value is always present and always one of those two. Validating it
        // means a hand-rolled post cannot put an arbitrary value into the
        // preferences JSON.
        $request->validate(
            array_fill_keys(array_keys($map), V::boolean())
        );

        $preferences = [];

        foreach ($map as $field => $storageKey) {
            $preferences[$storageKey] = $request->boolean($field);
        }

        $this->notificationService->updatePreferences(auth()->id(), $preferences);

        return back()->with('success', 'Notification preferences updated.');
    }

    /**
     * Form field name => storage key.
     *
     * The form names its toggles "{event}_email" and "{event}_inapp"; the
     * service stores and reads them as "email_{event}" and "in_app_{event}"
     * (see NotificationService::notify()). Nothing bridged the two, so the
     * update loop read field names that were never posted: every save wrote
     * false for all sixteen preferences and switched the customer's
     * notifications off wholesale.
     *
     * Derived from the service's own defaults rather than hardcoded, so a
     * preference added there is carried across automatically.
     *
     * @return array<string, string>
     */
    private function keyMap(): array
    {
        $map = [];

        foreach (array_keys($this->notificationService->getDefaultPreferences()) as $storageKey) {
            if (str_starts_with($storageKey, 'email_')) {
                $map[substr($storageKey, 6).'_email'] = $storageKey;
            } elseif (str_starts_with($storageKey, 'in_app_')) {
                $map[substr($storageKey, 7).'_inapp'] = $storageKey;
            } else {
                $map[$storageKey] = $storageKey;
            }
        }

        return $map;
    }
}
