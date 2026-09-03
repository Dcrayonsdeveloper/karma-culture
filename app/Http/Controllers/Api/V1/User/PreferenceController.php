<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

        // Every key is optional - the point of this endpoint is that a client
        // can flip a single toggle - but a key nobody defines is rejected
        // rather than dropped in silence, so a misspelled preference fails
        // loudly instead of looking like it saved.
        $rules = [];

        foreach (array_keys($defaults) as $key) {
            $rules[$key] = ['sometimes', 'boolean'];
        }

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request, $defaults) {
            foreach (array_keys($request->except(array_keys($defaults))) as $unknown) {
                $validator->errors()->add((string) $unknown, 'Unknown notification preference.');
            }
        });

        $validator->validate();

        // updatePreferences() replaces the whole preferences JSON, and only the
        // submitted keys were being handed to it. Every key the request left
        // out therefore disappeared from storage, and a key that is not stored
        // falls back to its default of true - so a client saving one switch
        // quietly turned every notification the customer had muted back on.
        // Start from what is stored and let the request win key by key.
        $stored = $this->notificationService->getUserPreferences(auth()->id());

        $preferences = [];

        foreach ($defaults as $key => $default) {
            $preferences[$key] = $request->has($key)
                ? $request->boolean($key)
                : (bool) $stored->get($key, $default);
        }

        $this->notificationService->updatePreferences(auth()->id(), $preferences);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated.',
        ]);
    }
}
