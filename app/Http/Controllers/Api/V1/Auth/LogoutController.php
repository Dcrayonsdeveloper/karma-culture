<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // This was `$request->user()->currentAccessToken()->delete()`, which
        // assumed every caller had signed in with a bearer token. Sanctum also
        // authenticates a first-party caller by the session cookie, and for that
        // one currentAccessToken() hands back a TransientToken - a marker object
        // with `can()` and `cant()` on it and no delete() at all. So the endpoint
        // answered a perfectly ordinary sign-out with a 500 and an
        // "undefined method" in the body, and signed nobody out.
        //
        // Each kind of credential is now revoked the way it can be: the row is
        // deleted for a real token, and the session is invalidated for a cookie
        // caller (logging the guard out alone leaves the session id valid, which
        // is the half that matters for session fixation). Either way the 200 is
        // truthful.
        $this->revokeCurrentCredential($request);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        // Revoke all tokens
        $request->user()->tokens()->delete();

        // ...and the cookie session this request arrived on, if that is what it
        // arrived on. Deleting the token rows alone left a session-authenticated
        // caller still signed in on this very device while being told they had
        // been logged out of all of them.
        $this->revokeCurrentCredential($request);

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices',
        ]);
    }

    /**
     * End whichever credential authenticated THIS request.
     *
     * Sanctum answers with a PersonalAccessToken for a bearer caller and a
     * TransientToken for a first-party cookie caller; a guard that resolved the
     * user some other way answers with null. Only the first has a row to delete.
     */
    private function revokeCurrentCredential(Request $request): void
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();

            return;
        }

        // The session guards Sanctum consults for a first-party caller
        // (config('sanctum.guard')), so a cookie sign-in is actually ended
        // rather than merely reported as ended.
        foreach ((array) config('sanctum.guard', ['web']) as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::guard($guard)->logout();
            }
        }

        // A stateless request has no session to invalidate; a cookie one does,
        // and leaving that id alive is what would let the "logged out" session be
        // replayed.
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
