<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            // The web sign-up deliberately overrides these four rules with
            // sentences written for a customer; this endpoint passed no messages
            // at all, so the same duplicate address came back as "The email has
            // already been taken." through the app and "An account already exists
            // for this email address. Try signing in instead." through the
            // website - one fact, two verdicts, and only one of them says what to
            // do about it. The duplicate-phone rule was worse: the framework
            // default names the column ("The phone has already been taken.")
            // where the web sign-up names the thing the customer has ("An account
            // with this mobile number already exists.").
            //
            // Word for word Auth\RegisterController's array. The two have no
            // shared home to live in yet - hoisting them into lang keys means
            // editing the web and admin sign-ups as well - so they must be
            // changed together.
            'email.email' => 'Enter a valid email address, like you@example.com.',
            'email.unique' => 'An account already exists for this email address. Try signing in instead.',
            'phone.unique' => 'An account with this mobile number already exists.',
            'password.confirmed' => 'The two passwords do not match.',
        ]);

        $user = User::create([
            'uuid' => Str::uuid(),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }
}
