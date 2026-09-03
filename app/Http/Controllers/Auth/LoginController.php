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

    /**
     * ?next= brings a customer back to the page they were sent here from.
     *
     * The cart and wishlist buttons are asynchronous, so the `auth` middleware
     * never sees a page request to record - it sees a POST from a script, and
     * an intended URL of /cart/add would send the customer to a route that
     * does not answer GET. The button therefore names the page it was pressed
     * on, and it lands in the same session key the middleware uses.
     *
     * Only a root-relative path is accepted, and safeIntendedUrl() checks the
     * value again on the way out - an open redirect off the login page is the
     * exact shape a credential phishing chain needs, so it is refused at both
     * ends rather than either.
     */
    public function showLoginForm(Request $request): View
    {
        $next = $request->query('next');

        if (is_string($next) && str_starts_with($next, '/') && ! str_starts_with(str_replace('\\', '/', $next), '//')) {
            $request->session()->put('url.intended', $next);
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse|JsonResponse
    {
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
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address, like you@example.com.',
            'email.max' => 'That email address is too long.',
            'password.required' => 'Please enter your password.',
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

            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()->to($this->safeIntendedUrl($request, route('account.dashboard')));
        }

        if ($request->wantsJson()) {
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
