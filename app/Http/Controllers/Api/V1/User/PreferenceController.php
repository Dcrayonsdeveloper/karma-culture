<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function show(): JsonResponse
    {
        $preferences = $this->notificationService->getUserPreferences(auth()->id());

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $defaults = $this->notificationService->getDefaultPreferences();
        $preferences = [];

        foreach (array_keys($defaults) as $key) {
            if ($request->has($key)) {
                $preferences[$key] = (bool) $request->input($key);
            }
        }

        $this->notificationService->updatePreferences(auth()->id(), $preferences);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated.',
        ]);
    }
}
