<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\NewsletterSubscriber;
use App\Rules\ValidationRules as V;
use App\Services\NotificationService;
use App\Support\OfferClaims;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    /**
     * The signup forms that also hand out the exit-popup discount.
     *
     * The exit popup's "Claim Offer" button has always posted here, because
     * capturing the subscriber is the primary value and stays worth doing even
     * when there is no coupon behind the code. What it never did was record the
     * claim, so the code it displayed was decorative text the customer had to
     * retype at checkout - see App\Support\OfferClaims. Bound to the source
     * rather than applied to every signup for the same reason the name rule is:
     * only the form that shows the offer may claim it.
     */
    private const SOURCES_CLAIMING_OFFER = [
        'exit_intent',
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
                    'offer' => $this->claimOffer($request, $validated['email'], $source),
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
                'offer' => $this->claimOffer($request, $validated['email'], $source),
            ]);
        }

        $subscriber = NewsletterSubscriber::create([
            'email' => $validated['email'],
            'name' => $validated['name'] ?? null,
            'phone' => $phone,
            'source' => $source,
            'is_active' => true,
            'subscribed_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        // Only on this path, deliberately. The two branches above return before
        // reaching here, so an address that is already on the list - and a
        // re-subscribe - stays silent: the popups post on every visit, and an
        // alert per repeat would bury the genuinely new signups. That also
        // keeps the cost of this public, throttled endpoint to one extra write
        // per address ever, not per request.
        $this->notifyAdminsOfSubscriber($subscriber, $source);

        return response()->json([
            'success' => true,
            'message' => 'You\'re subscribed! Thanks for joining us.',
            'offer' => $this->claimOffer($request, $validated['email'], $source),
        ]);
    }

    /**
     * Record the exit-popup claim and, when we can prove the address belongs to
     * whoever is signed in, put the coupon on their cart there and then.
     *
     * The signed-in shortcut is a CONVENIENCE, not the authorisation. It only
     * saves a page load: OfferClaims::applyTo() re-resolves the claim from the
     * account email on every cart and checkout view anyway, which is what makes
     * the guest journey - claim now, sign in later, maybe on another device -
     * work at all.
     *
     * The envelope has three states and the popup renders all three. 'saved'
     * deliberately covers a guest, a signed-in customer who typed somebody
     * else's address, an empty cart AND a cart that does not qualify: they are
     * indistinguishable so the response cannot be used to test whether an
     * address has an account here.
     */
    private function claimOffer(Request $request, string $email, string $source): array
    {
        if (! in_array($source, self::SOURCES_CLAIMING_OFFER, true)) {
            return ['state' => 'none', 'discount' => 0.0];
        }

        $user = $request->user();

        // Null when the popup is switched off or its code has been blanked -
        // there is no offer to claim, so say so rather than promising one.
        if (! OfferClaims::record($email, $source, $request->ip(), $user)) {
            return ['state' => 'none', 'discount' => 0.0];
        }

        $saved = ['state' => 'saved', 'discount' => 0.0];

        if (! $user || V::normalizeEmail($email) !== V::normalizeEmail($user->email)) {
            return $saved;
        }

        $cart = Cart::where('user_id', $user->id)->first();
        $result = OfferClaims::applyTo($cart, $user);

        return $result['coupon']
            ? ['state' => 'applied', 'discount' => $result['discount']]
            : $saved;
    }

    /**
     * Tell the admins a new address joined the list.
     *
     * Logged and swallowed on failure: the subscriber row is already written by
     * the time this runs, and a signup form open to the public has to answer
     * "you're subscribed" rather than 500 because a notification could not be
     * recorded.
     */
    private function notifyAdminsOfSubscriber(NewsletterSubscriber $subscriber, string $source): void
    {
        try {
            app(NotificationService::class)->notifyAdmins(
                'new_newsletter_subscriber',
                'New Newsletter Subscriber',
                "{$subscriber->email} subscribed via {$source}",
                [
                    'subscriber_id' => $subscriber->id,
                    'email' => $subscriber->email,
                    'source' => $source,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Failed to notify admins of a newsletter subscriber', [
                'subscriber_id' => $subscriber->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
