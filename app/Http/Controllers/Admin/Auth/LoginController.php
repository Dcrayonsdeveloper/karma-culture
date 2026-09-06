<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Header the sign-in credentials arrive in, by request.
     *
     * The panel's own form now sends the credentials here instead of in the
     * POST body. The value is `Basic <base64("email:password")>` - the shape
     * RFC 7617 defines for Authorization - because a header may only carry
     * ASCII, and an email or a password is free to contain neither. Base64 is
     * an ENCODING, not encryption: anything that can read this header can read
     * the password out of it as easily as it could have read the body. HTTPS is
     * what keeps it private, exactly as it did when it travelled in the body.
     *
     * A custom name rather than `Authorization` because that one is special-
     * cased along the way - PHP-FPM and various proxies consume or rewrite it -
     * and a sign-in that works only on some deployments is worse than a
     * non-standard header name.
     */
    private const AUTH_HEADER = 'X-Admin-Auth';

    /** Header carrying the "remember me" choice, since it left the body too. */
    private const REMEMBER_HEADER = 'X-Admin-Remember';

    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $this->credentials($request);

        $validator = Validator::make($credentials, [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->failed($request, $validator->errors()->first() ?: 'Enter your email address and password.');
        }

        if (Auth::guard('admin')->attempt($credentials, $this->remember($request))) {
            $user = Auth::guard('admin')->user();

            // Only admin/staff accounts may use the admin panel - reject others
            // instead of creating a session that hits a 403 on every page.
            if (! $user->isAdmin() && ! $user->isStaff()) {
                Auth::guard('admin')->logout();

                return $this->failed($request, 'This account does not have admin access.');
            }

            // A deactivated account must not be able to sign in to the panel
            // either. The admin guard shares the users provider with `web`, so
            // without this the deactivate toggle stopped nobody here.
            if (! $user->is_active) {
                Auth::guard('admin')->logout();

                return $this->failed($request, 'This account has been deactivated.');
            }

            $request->session()->regenerate();

            $target = redirect()->intended(route('admin.dashboard'))->getTargetUrl();

            // The form posts with fetch(), so it cannot follow a 302 into a page
            // the way a browser form submit would - hand it the address and let
            // it navigate. The session cookie is already on the response.
            if ($this->wantsJson($request)) {
                return response()->json([
                    'redirect' => $target,
                    'token' => csrf_token(),
                ]);
            }

            return redirect()->to($target);
        }

        return $this->failed($request, 'The provided credentials do not match our records.');
    }

    /**
     * The credentials, read from {@see self::AUTH_HEADER} where the panel's own
     * form puts them, and from the body where anything else still does.
     *
     * The body is still accepted deliberately. The header is set by JavaScript;
     * with none running - a blocked script, a failed asset, a password manager
     * submitting the form itself - the form degrades to an ordinary POST, and
     * signing in keeps working rather than locking the panel's own administrator
     * out of it.
     *
     * @return array{email: string, password: string}
     */
    private function credentials(Request $request): array
    {
        $header = (string) $request->header(self::AUTH_HEADER, '');

        if (stripos($header, 'Basic ') === 0) {
            $decoded = base64_decode(substr($header, 6), true);

            if (is_string($decoded) && str_contains($decoded, ':')) {
                [$email, $password] = explode(':', $decoded, 2);

                return ['email' => trim($email), 'password' => $password];
            }
        }

        return [
            'email' => (string) $request->input('email', ''),
            'password' => (string) $request->input('password', ''),
        ];
    }

    /** "Remember me", from its header, falling back to the body checkbox. */
    private function remember(Request $request): bool
    {
        if ($request->hasHeader(self::REMEMBER_HEADER)) {
            return filter_var($request->header(self::REMEMBER_HEADER), FILTER_VALIDATE_BOOLEAN);
        }

        return $request->boolean('remember');
    }

    /** Does this request want its answer as JSON rather than a redirect? */
    private function wantsJson(Request $request): bool
    {
        return $request->ajax() || $request->wantsJson() || $request->hasHeader(self::AUTH_HEADER);
    }

    /**
     * One rejected sign-in, in whichever shape the caller can read.
     *
     * The message is the same either way, and says the same thing for a bad
     * password as for an address that has no account: which of the two it was
     * is not something an unauthenticated caller gets told.
     */
    private function failed(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => $message,
                'errors' => ['email' => [$message]],
                'token' => csrf_token(),
            ], 422);
        }

        return back()->withErrors(['email' => $message])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $this->endSessionKeeping($request, 'web');

        return redirect()->route('admin.login');
    }

    /**
     * Sign this guard out without taking the other one with it.
     *
     * One session cookie carries both guards: config/auth.php gives `admin` and
     * `web` the same users provider, and Laravel keeps each guard's login state
     * under its own `login_<guard>_<hash>` key in the one session. Throwing the
     * whole session away therefore signed out whoever was working in the other
     * tab as well - a shopper logging out took the admin panel down with them,
     * notification polling and all, and an admin logging out emptied a
     * shopper's session mid-checkout.
     *
     * Everything else still goes. This is invalidate() - flush plus a new
     * session id, so nothing the departing side left behind can be read by
     * whoever uses the browser next - with only the other guard's own login
     * keys carried across into the fresh session.
     */
    private function endSessionKeeping(Request $request, string $guard): void
    {
        $prefix = 'login_'.$guard.'_';

        $keep = [];
        foreach ($request->session()->all() as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $keep[$key] = $value;
            }
        }

        $request->session()->invalidate();

        foreach ($keep as $key => $value) {
            $request->session()->put($key, $value);
        }

        $request->session()->regenerateToken();
    }

}
