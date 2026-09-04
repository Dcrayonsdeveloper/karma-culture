<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * One message for every way a sign-in can fail.
     *
     * "No account with that email" and "wrong password" are two different
     * answers to the same question, and the difference is enough to confirm an
     * address is registered here - which is the first step of a credential
     * stuffing run against it. Both cases say this instead.
     *
     * Deliberately word for word Auth\LoginController::CREDENTIALS_FAILED. The
     * same account, the same wrong password, said "The provided credentials are
     * incorrect." through the app and "The provided credentials do not match our
     * records." through the website, which reads as two different verdicts on one
     * attempt. The two have no shared home to live in yet - hoisting them into a
     * lang key means editing the web and admin sign-ins as well - so they must be
     * changed together, and so must doc/api-documentation.md section 1.2.
     *
     * Where it appears in the response: in `errors.email[0]`, and only there.
     * `message` is the fixed 422 summary the handler in bootstrap/app.php writes
     * for every endpoint under the /api/ prefix, so a client reading `message`
     * for this sentence will not find it - which is the shape the documentation
     * now publishes.
     */
    private const CREDENTIALS_FAILED = 'The provided credentials do not match our records.';

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [self::CREDENTIALS_FAILED],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact support.'],
            ]);
        }

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $deviceName = $validated['device_name'] ?? ($request->userAgent() ?? 'unknown');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'avatar_url' => $user->avatar_url,
                    'is_verified' => $user->is_verified,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }
}
