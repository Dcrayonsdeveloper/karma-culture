<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\DuplicateAccountException;
use App\Http\Controllers\Auth\RegisterController as WebRegisterController;
use App\Http\Controllers\Controller;
use App\Models\SignupEmailVerification;
use App\Models\User;
use App\Rules\IndianMobile;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * The other door into signup.
 *
 * Not a second product surface that happens to resemble registration - the same
 * act, and so held to the same conditions. Sanctum's
 * EnsureFrontendRequestsAreStateful is prepended to the whole api group
 * (bootstrap/app.php) and config/sanctum.php's stateful list includes the
 * application's own URL, so the very browser with the signup page open can post
 * here carrying its session cookie. A verification requirement enforced only on
 * POST /register would therefore be enforced nowhere at all: skipping the
 * emailed link would be a matter of changing the URL you post to.
 *
 * Three changes, every one of them making this path agree with the web one
 * rather than inventing anything:
 *
 *   - the address must carry a live, unspent proof of ownership, read from the
 *     server's own row and never from the request;
 *   - the mobile number is normalised before it is compared and before it is
 *     stored, so "+91 98765 43210" and "9876543210" stop being two accounts;
 *   - a lost race comes back as 409 instead of a 500 with a driver message in it.
 */
class RegisterController extends Controller
{
    private const UNVERIFIED = 'Please verify your email address before creating your account.';

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            // Was ['nullable','string','max:20','unique:users'], which compared
            // the raw input - so one subscriber could hold as many accounts as
            // they could think of spacings for their own number. IndianMobile is
            // what the web form has used since that bug was found there.
            'phone' => [
                'nullable',
                'string',
                'max:20',
                new IndianMobile,
                function (string $attribute, mixed $value, Closure $fail): void {
                    $normalized = IndianMobile::normalize(is_scalar($value) ? (string) $value : null);

                    if ($normalized !== null && self::mobileIsRegistered($normalized)) {
                        $fail(WebRegisterController::MOBILE_TAKEN);
                    }
                },
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
<<<<<<< HEAD
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
=======
            'email.unique' => WebRegisterController::EMAIL_TAKEN,
>>>>>>> e3a8ce0550d8732347a02aa9589f2867ee5b491f
        ]);

        $email = SignupEmailVerification::normalizeEmail($validated['email']);
        $phone = IndianMobile::normalize($validated['phone'] ?? null);

        // Server state, never a request field - and there is deliberately no
        // parameter here through which a caller could offer an opinion.
        //
        // The claim is required here as well as on the web route, and for the
        // same reason: this endpoint is session-stateful in a browser, so
        // leaving it out would leave the whole hole open through a different
        // URL. A caller with no session holds no claims and can spend no proof,
        // which is correct - there is no way to ask for one over this API
        // either.
        if ($email === null || SignupEmailVerification::claimedProofFor($email, $request) === null) {
            throw ValidationException::withMessages(['email' => self::UNVERIFIED]);
        }

        try {
            $user = DB::transaction(function () use ($request, $validated, $email, $phone) {
                // Re-read inside the transaction, and the proof locked, for the
                // reason the web controller gives: everything checked above was
                // true when it was checked and need not still be.
                if (self::addressIsRegistered($email)) {
                    throw ValidationException::withMessages(['email' => WebRegisterController::EMAIL_TAKEN]);
                }

                if ($phone !== null && self::mobileIsRegistered($phone)) {
                    throw ValidationException::withMessages(['phone' => WebRegisterController::MOBILE_TAKEN]);
                }

                $attempt = SignupEmailVerification::claimedProofFor($email, $request, locking: true);

                if ($attempt === null) {
                    throw ValidationException::withMessages(['email' => self::UNVERIFIED]);
                }

                $user = self::createAccount([
                    'uuid' => Str::uuid(),
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'phone' => $phone,
                    'password' => Hash::make($validated['password']),
                    // Proved before the row existed - so the Sanctum token this
                    // hands back is no longer a live session for an address
                    // nobody has confirmed reaching.
                    'email_verified_at' => now(),
                    'role' => 'customer',
                    'is_active' => true,
                ], $email, $phone);

                if (! $attempt->consume()) {
                    throw ValidationException::withMessages(['email' => self::UNVERIFIED]);
                }

                return $user;
            });
        } catch (DuplicateAccountException $e) {
            // 409 is already this API's word for "that exists" - the review and
            // wishlist controllers both answer that way - so this is the
            // existing convention rather than a new one.
            return response()->json([
                'message' => $e->firstMessage(),
                'errors' => $e->errors,
            ], 409);
        }

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

    /**
     * Write the row, and name the collision if there is one.
     *
     * Caught around the statement rather than around the transaction, for the
     * reason set out on the web controller's copy: InnoDB rolls back only the
     * failing statement, so the row that beat us is still readable here and
     * nowhere afterwards.
     *
     * @param  array<string, mixed>  $attributes
     */
    private static function createAccount(array $attributes, string $email, ?string $phone): User
    {
        try {
            return User::create($attributes);
        } catch (UniqueConstraintViolationException) {
            $errors = [];

            // Locking reads: see the note on the web controller's copy. A plain
            // SELECT here would answer from a snapshot taken before the request
            // that beat us committed, and report that nothing owns the address.
            if (self::addressIsRegistered($email, locking: true)) {
                $errors['email'] = [WebRegisterController::EMAIL_TAKEN];
            }

            if ($phone !== null && self::mobileIsRegistered($phone, locking: true)) {
                $errors['phone'] = [WebRegisterController::MOBILE_TAKEN];
            }

            if ($errors === []) {
                $errors['email'] = ['We could not create your account just now. Please try again.'];
            }

            throw new DuplicateAccountException($errors);
        }
    }

    /** withTrashed(): users are soft-deleted, the unique index is not. */
    private static function addressIsRegistered(string $normalizedEmail, bool $locking = false): bool
    {
        $query = User::withTrashed()->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail]);

        return ($locking ? $query->lockForUpdate() : $query)->exists();
    }

    private static function mobileIsRegistered(string $normalizedPhone, bool $locking = false): bool
    {
        $query = User::withTrashed()->where('phone', $normalizedPhone);

        return ($locking ? $query->lockForUpdate() : $query)->exists();
    }
}
