<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\IndianMobile;
use App\Rules\ValidationRules as V;
use Closure;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    /** users.first_name and users.last_name are both varchar(50). */
    private const NAME_PART_LIMIT = 50;

    public function showRegistrationForm(): RedirectResponse
    {
        return redirect()->route('login', ['mode' => 'register']);
    }

    public function register(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            // V::name() brings Unicode letters, marks and the four separators
            // real names use, so "रवि कुमार" and "O'Connor" are accepted while
            // "Ravi123" and a pasted URL are not.
            //
            // The extra closure is about the database, not the charset: this
            // one field is split on the first space and written into two
            // varchar(50) columns, so "max:101" (the old rule, read as
            // 50 + space + 50) let a 101-character unbroken string through to
            // a column half its size. Check the halves, not the whole.
            'full_name' => [
                ...V::name(max: 100),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    [$first, $last] = self::splitName($value);

                    if (mb_strlen($first) > self::NAME_PART_LIMIT || mb_strlen($last) > self::NAME_PART_LIMIT) {
                        $fail('Please enter your first and last name, each 50 characters or fewer.');
                    }
                },
            ],

            // email:strict is safe to impose here in a way it is not on the
            // sign-in form: this is where addresses are created, so nothing
            // already stored can be shut out by it.
            'email' => [...V::email(), Rule::unique('users', 'email')],

            // Required at signup: the store calls and SMSes on every delivery, so
            // an account without a reachable number costs a support cycle later.
            // V::mobile() accepts the shapes people actually type (+91, leading
            // 0, spaces, hyphens) and IndianMobile::normalize() below reduces
            // them to the bare ten digits the column holds.
            'phone' => [
                ...V::mobile(),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $normalized = IndianMobile::normalize(is_scalar($value) ? (string) $value : null);

                    // Uniqueness has to be checked on the canonical form. The
                    // old `unique:users` compared raw input, so "+91 98765
                    // 43210" and "9876543210" were two different rows for one
                    // subscriber — and an OTP or a delivery call then had two
                    // accounts to choose between.
                    if ($normalized !== null && User::where('phone', $normalized)->exists()) {
                        $fail('An account with this mobile number already exists.');
                    }
                },
            ],

            // Password::defaults() — the app's existing policy, unchanged
            // (8 characters). max:255 is an input bound, not a policy change.
            'password' => [...V::password(), 'max:255'],

            // The form has always marked this required, but nothing enforced it
            // server-side — and the view already renders an $errors->has('terms')
            // branch that could never fire.
            'terms' => V::accepted(),
        ], [
            'full_name.required' => 'Please enter your full name.',
            'full_name.min' => 'Please enter your full name.',
            'full_name.max' => 'Your name must be 100 characters or fewer.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address, like you@example.com.',
            'email.max' => 'That email address is too long.',
            'email.unique' => 'An account already exists for this email address. Try signing in instead.',
            'phone.required' => 'Please enter your mobile number.',
            'phone.max' => 'That phone number is too long.',
            'password.required' => 'Please choose a password.',
            'password.confirmed' => 'The two passwords do not match.',
            'password.max' => 'Your password must be 255 characters or fewer.',
            // Password::defaults() (AppServiceProvider) reports each unmet
            // requirement separately; these replace the framework wording with
            // one consistent sentence per rule.
            //
            // The doubled 'password.password.*' keys are not a typo. The Password
            // rule reports its own failures via addFailure($attribute, 'password.mixed'),
            // so the lookup key is "{attribute}.{rule}" = password.password.mixed.
            // Only 'min' comes from an ordinary rule and takes the short key.
            'password.min' => 'Your password must be at least 8 characters long.',
            'password.password.mixed' => 'Your password must include both an uppercase and a lowercase letter.',
            'password.password.numbers' => 'Your password must include at least one number.',
            'password.password.symbols' => 'Your password must include at least one special character, such as @ # ! or ?.',
            'terms.accepted' => 'Please accept the Terms and Privacy Policy to continue.',
        ]);

        [$firstName, $lastName] = self::splitName($validated['full_name']);

        $user = User::create([
            'uuid' => Str::uuid(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $validated['email'],
            // Store the canonical ten digits, which is what the column already
            // holds elsewhere, rather than whatever spacing the visitor typed.
            'phone' => IndianMobile::normalize($validated['phone']),
            'password' => Hash::make($validated['password']),
            // Hard-coded, never taken from the request: `role` and `is_active`
            // are the two columns a mass-assigned signup would want to reach.
            'role' => 'customer',
            'is_active' => true,
        ]);

        event(new Registered($user));

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('login')->with('success', 'Account created successfully! Please login to continue.');
    }

    /**
     * Split a single "Full Name" field into the two columns behind it.
     *
     * @return array{0: string, 1: string}
     */
    private static function splitName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);

        return [$parts[0], trim($parts[1] ?? '')];
    }
}
