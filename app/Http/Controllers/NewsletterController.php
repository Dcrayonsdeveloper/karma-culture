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
            'name'  => 'nullable|string|max:100',
        ]);

        $existing = NewsletterSubscriber::where('email', $validated['email'])->first();

        if ($existing) {
            if ($existing->is_active) {
                return response()->json([
                    'success' => true,
                    'message' => 'This email is already subscribed!',
                ]);
            }

            // Re-subscribe
            $existing->update([
                'is_active'        => true,
                'subscribed_at'    => now(),
                'unsubscribed_at'  => null,
                'source'           => $request->input('source', 'homepage'),
                'ip_address'       => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Welcome back! You have been re-subscribed.',
            ]);
        }

        NewsletterSubscriber::create([
            'email'         => $validated['email'],
            'name'          => $validated['name'] ?? null,
            'source'        => $request->input('source', 'homepage'),
            'is_active'     => true,
            'subscribed_at' => now(),
            'ip_address'    => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'You\'re subscribed! Thanks for joining us.',
        ]);
    }
}
