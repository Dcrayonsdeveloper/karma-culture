<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Rules\ValidationRules as V;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    /**
     * Where a signup may claim to have come from.
     *
     * `source` is written straight into a varchar(255) that admins read in the
     * subscriber list, and nothing on the page constrains it - it is a JSON
     * body field the client chooses. An unknown value is not an error worth
     * rejecting a real subscriber over, so it is bounded here and anything
     * outside the list falls back to 'homepage'.
     */
    private const SOURCES = [
        'homepage',
        'footer',
        'offer_popup',
        'exit_intent',
        'blog',
        'checkout',
        'product',
    ];

    /**
     * The signup forms that actually render a name input, and so may require it.
     *
     * This one endpoint serves both popups: the offer popup asks for a name,
     * the exit-intent popup has no name field at all. Requiring `name` for
     * every source would reject every exit-intent claim outright, so the
     * requirement is bound to the form that shows the input. `source` is
     * client-controlled, which is fine here - this decides whether a blank name
     * is an error, not whether a bad one is. The charset guard below applies to
     * every source whenever a name is present.
     */
    private const SOURCES_REQUIRING_NAME = [
        'offer_popup',
    ];

    public function subscribe(Request $request): JsonResponse
    {
        $nameRequired = in_array($request->input('source'), self::SOURCES_REQUIRING_NAME, true);

        $validated = $request->validate([
            'email' => V::email(),
            'name' => V::name(required: $nameRequired),
            // Mobile: optional for plain newsletter signups, required by the offer popup
            // client-side. Validated on the DIGIT count rather than with the Indian
            // mobile rule on purpose - a newsletter list is the one place a genuine
            // overseas subscriber turns up, and IndianMobile would reject them. The
            // charset guard is what stops symbol-only junk creating leads via direct POST.
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/', function ($attribute, $value, $fail) {
                $digits = preg_replace('/\D/', '', (string) $value);
                if (strlen($digits) < 10 || strlen($digits) > 15) {
                    $fail('Please enter a valid mobile number (10-15 digits).');
                }
            }],
            'source' => ['nullable', 'string', 'max:50'],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address, like you@example.com.',
            'name.required' => 'Please enter your name.',
            'name.min' => 'Please enter your full name.',
            'name.max' => 'Please keep your name under 100 characters.',
            'phone.regex' => 'Please enter a valid mobile number (10-15 digits).',
        ]);

        $phone = isset($validated['phone']) ? preg_replace('/[^0-9+]/', '', $validated['phone']) : null;

        // Never the raw request value: this lands in a column an admin reads.
        $source = in_array($validated['source'] ?? null, self::SOURCES, true)
            ? $validated['source']
            : 'homepage';

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
                'source' => $source,
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
            'source' => $source,
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
