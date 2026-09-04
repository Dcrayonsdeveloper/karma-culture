<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\RedirectsToSafeUrl;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    use RedirectsToSafeUrl;

    /**
     * One message for every way a sign-in can fail.
     *
     * "No account with that email" and "wrong password" are two different
     * answers to the same question, and the difference is enough to confirm an
     * address is registered here — which is the first step of a credential
     * stuffing run against it. Both cases say this instead.
     */
    private const CREDENTIALS_FAILED = 'The provided credentials do not match our records.';

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse|JsonResponse
    {
        // Who is asking decides two things that have to agree with each other:
        // the shape of every answer this action gives, and - see below - the
        // wording of the two "you left this empty" messages. Settle it once,
        // rather than asking again at each branch and leaving the two free to
        // disagree about which client they are talking to.
        $wantsJson = $request->wantsJson();

        // Deliberately NOT `email:strict` here, unlike registration. Sign-in
        // has to accept whatever address is already stored, and accounts
        // created before the strict rule existed may hold one it rejects —
        // tightening this end locks those people out of their own account for
        // no gain, since the password hash is what actually authenticates.
        // `max` is a bound on the input, not a policy: bcrypt only reads the
        // first 72 bytes, so 1024 cannot lock anyone out while still refusing
        // a megabyte-long field.
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
            'remember' => ['nullable', 'boolean'],
        ], [
            // The two "required" sentences are not a phrasing of our own: each
            // is the one the client that asked would have used had it caught the
            // empty box itself. They follow the caller because this action is
            // posted from two kinds of client that name an empty field
            // differently, and both render the 422 map straight back into the
            // field it keys.
            //
            // The page form at /login carries no `novalidate`, so the site-wide
            // validator in app.js owns those two boxes and names an empty one
            // after its <label>: "Email Address is required.", "Password is
            // required." The two sign-in modals - kkAuthModal in the header
            // partial, and the Alpine `authModal` store rendered on every
            // storefront page - are hand-written and say "Please enter your
            // email address." / "Please enter your password." instead.
            //
            // A client-side check and this rule are not alternatives, which is
            // what makes the wording matter: both can speak about the same box
            // on the same attempt, because a value the browser accepts is not
            // always one Laravel does. A password of three spaces is truthy to
            // every one of these clients and satisfies the native `required`, so
            // it is sent; `required` here trims before it looks, and rejects it.
            // With one fixed sentence, one of the two surfaces would then
            // complain about one empty box in two different voices depending on
            // which side caught it - the client's about the attempt being made,
            // the server's differently worded one about the attempt just made,
            // same rule, same field. Answering in the voice of whoever asked
            // keeps it to one wording per field per screen, whichever side
            // speaks.
            //
            // `email.email` and `email.max` need no such split: the mirrors in
            // app.js - the sentence it gives a type="email" typeMismatch, and
            // the length check in _emailShapeError - already word them exactly
            // this way.
            'email.required' => $wantsJson
                ? 'Please enter your email address.'
                : 'Email Address is required.',
            'email.email' => 'Enter a valid email address, like you@example.com.',
            'email.max' => 'That email address is too long.',
            'password.required' => $wantsJson
                ? 'Please enter your password.'
                : 'Password is required.',
        ]);

        // Only these two go to the guard: anything else in the validated array
        // would be matched against a users column that does not exist.
        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        // Capture guest session ID before login (session regenerate will change it)
        $guestSessionId = $request->session()->getId();

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Merge guest cart into user cart
            $this->mergeGuestCart($guestSessionId, Auth::id());

            if ($wantsJson) {
                return response()->json(['success' => true]);
            }

            return redirect()->to($this->safeIntendedUrl($request, route('account.dashboard')));
        }

        if ($wantsJson) {
            return response()->json([
                'message' => self::CREDENTIALS_FAILED,
                'errors' => ['email' => [self::CREDENTIALS_FAILED]],
            ], 422);
        }

        // `remember` travels back with the email so the box the visitor ticked
        // is still ticked on the retry.
        return back()->withErrors([
            'email' => self::CREDENTIALS_FAILED,
        ])->onlyInput('email', 'remember');
    }

    /**
     * Merge guest session cart into the authenticated user's cart.
     */
    private function mergeGuestCart(string $guestSessionId, int $userId): void
    {
        $guestCart = Cart::where('session_id', $guestSessionId)->whereNull('user_id')->first();

        if (!$guestCart || $guestCart->items->isEmpty()) {
            return;
        }

        // Get or create user cart
        $userCart = Cart::firstOrCreate(
            ['user_id' => $userId],
            ['session_id' => null]
        );

        // Move guest items into user cart. A cart line is identified by product
        // + variant + size + colour everywhere else; matching on only the first
        // two collapsed "Blue / M" and "Red / L" of the same product into one
        // line and silently lost the guest's selection.
        foreach ($guestCart->items as $item) {
            $existing = $userCart->items()
                ->where('product_id', $item->product_id)
                ->where('variant_id', $item->variant_id)
                ->where('size', $item->size)
                ->where('colour', $item->colour)
                ->first();

            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $item->quantity]);
            } else {
                $item->update(['cart_id' => $userCart->id]);
            }
        }

        // Recalculate user cart totals
        $userCart->recalculate();

        // Delete the guest cart
        $guestCart->items()->delete();
        $guestCart->delete();
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
