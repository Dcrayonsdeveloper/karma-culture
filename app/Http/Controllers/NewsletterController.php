<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:100',
            // Mobile: optional in general, but the offer popup enforces it client-side.
            // Accept 10-15 digit numbers (allow +, spaces, hyphens which we strip).
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
        ]);

        $phone = isset($validated['phone']) ? preg_replace('/[^0-9+]/', '', $validated['phone']) : null;

        $existing = NewsletterSubscriber::where('email', $validated['email'])->first();

        if ($existing) {
            // Always capture the latest name/phone even for existing subscribers.
            $existing->fill(array_filter([
                'name' => $validated['name'] ?? null,
                'phone' => $phone,
            ]));

            if ($existing->is_active) {
                $existing->save();

                return response()->json([
                    'success' => true,
                    'message' => 'This email is already subscribed!',
                ]);
            }

            // Re-subscribe
            $existing->fill([
                'is_active' => true,
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
                'source' => $request->input('source', 'homepage'),
                'ip_address' => $request->ip(),
            ])->save();

            return response()->json([
                'success' => true,
                'message' => 'Welcome back! You have been re-subscribed.',
            ]);
        }

        NewsletterSubscriber::create([
            'email' => $validated['email'],
            'name' => $validated['name'] ?? null,
            'phone' => $phone,
            'source' => $request->input('source', 'homepage'),
            'is_active' => true,
            'subscribed_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'You\'re subscribed! Thanks for joining us.',
        ]);
    }
}
