<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\IndianMobile;
use App\Rules\ValidationRules as V;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            // V::name() brings the Unicode letters and separators real names
            // use, so "रवि कुमार" and "O'Connor" pass while "dev123" and a
            // pasted URL do not.
            //
            // max:50, not the old max:255: both columns are varchar(50), so the
            // previous rule waved through names twice the width of the column
            // they were about to be written into.
            'first_name' => V::name(max: 50),

            // Optional, exactly as at sign-up. RegisterController splits one
            // "full name" field on the first space, so entering "dev" creates
            // an account whose last_name is ''. Requiring one here left every
            // such account unable to save this form at all - including to
            // correct their phone number.
            'last_name' => V::name(required: false, max: 50),

            // email:strict, matching registration. Plain 'email' is
            // RFC-permissive and accepts "dev@gmail" with no TLD, so the old
            // rule let people change an address to one they could never have
            // signed up with.
            'email' => [
                ...V::email(),
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'phone' => [
                ...V::mobile(required: false),
                function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    $normalized = IndianMobile::normalize(is_scalar($value) ? (string) $value : null);

                    // Uniqueness on the canonical form, as at registration.
                    // Nothing checked it here at all, so this form was a way
                    // around the check sign-up performs: two accounts could end
                    // up on one number, and an OTP or a delivery call then has
                    // two accounts to choose between.
                    if ($normalized !== null
                        && User::where('phone', $normalized)->whereKeyNot($user->id)->exists()) {
                        $fail('An account with this mobile number already exists.');
                    }
                },
            ],
        ], [
            'first_name.required' => 'Please enter your first name.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address, like you@example.com.',
            'email.unique' => 'An account already exists for this email address.',
        ]);

        // Built field by field rather than mass-assigning $validated, so the
        // phone lands in the column canonicalised.
        $user->update([
            'first_name' => $validated['first_name'],
            // The column is NOT NULL, and sign-up writes '' for a single-word
            // name; keep that shape rather than introducing nulls.
            'last_name' => $validated['last_name'] ?? '',
            'email' => $validated['email'],
            // Store the bare ten digits, not whatever spacing was typed.
            'phone' => IndianMobile::normalize($validated['phone'] ?? null),
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            // Unchanged policy - V::password() is the same Password::defaults()
            // + confirmed this already used. max:255 is an input bound, and
            // matches RegisterController and ResetPasswordController.
            'password' => [...V::password(), 'max:255'],
        ], [
            'current_password.current_password' => 'That is not your current password.',
            'password.confirmed' => 'The two passwords do not match.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
