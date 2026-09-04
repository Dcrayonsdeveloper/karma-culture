<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\DuplicateAccountException;
use App\Http\Controllers\Controller;
use App\Models\SignupEmailVerification;
use App\Models\User;
use App\Rules\IndianMobile;
use App\Rules\ValidationRules as V;
use Closure;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /** users.first_name and users.last_name are both varchar(50). */
    private const NAME_PART_LIMIT = 50;

    /** What the form asks for, and what the browser reports live. */
    private const NAME_LIMIT = 30;

    /**
     * The two sentences a customer is shown when the thing they typed belongs
     * to somebody else.
     *
     * Held as constants because each is said from three places - validation,
     * the re-check inside the transaction, and the duplicate-key catch that
     * backstops both - and they have to be the same words every time or the
     * form appears to change its mind under load.
     */
    public const EMAIL_TAKEN = 'Email address already exists. Please log in or use another email.';

    public const MOBILE_TAKEN = 'Mobile number already exists. Please log in or use another number.';

    /** Said when the address has not been proved, or the proof has run out. */
    private const EMAIL_UNVERIFIED = 'Please verify your email address before creating your account.';

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
            // 30 characters, matching the contact form: a full name that runs
            // longer is a paste, not a name, and the field now says so on the
            // keystroke that crosses the line rather than after a round trip.
            //
            // The extra closure is about the database, not the charset: this
            // one field is split on the first space and written into two
            // varchar(50) columns, so "max:101" (the old rule, read as
            // 50 + space + 50) let a 101-character unbroken string through to
            // a column half its size. Check the halves, not the whole. The
            // 30-character cap now sits well inside it; the guard stays because
            // it is the column that has the last word, not the cap.
            'full_name' => [
                ...V::name(max: self::NAME_LIMIT),
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

            // email:strict, and the tighter EmailAddress shape on top of it,
            // are safe to impose here in a way they are not on the sign-in
            // form: this is where addresses are created, so nothing already
            // stored can be shut out by them. strictShape is what refuses
            // "_asha@example.com" and "asha..menon@example.com", which the RFC
            // (and therefore email:strict) is perfectly happy with.
            'email' => [...V::email(strictShape: true), Rule::unique('users', 'email')],

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
                    // withTrashed(), and this is not decoration: users are
                    // soft-deleted but the unique index on the column is not,
                    // so a closed account still owns its number as far as an
                    // INSERT is concerned. Without it this check passed, the
                    // insert hit a 1062 and the customer got a 500 instead of a
                    // sentence.
                    if ($normalized !== null && self::mobileIsRegistered($normalized)) {
                        $fail(self::MOBILE_TAKEN);
                    }
                },
            ],

            // Password::defaults() — the site-wide policy, defined once in
            // AppServiceProvider (ten characters, mixed case, a number and a
            // symbol). max:255 is an input bound, not a policy change.
            'password' => [...V::password(), 'max:255'],

            // The form has always marked this required, but nothing enforced it
            // server-side — and the view already renders an $errors->has('terms')
            // branch that could never fire.
            'terms' => V::accepted(),
        ], [
            'full_name.required' => 'Please enter your full name.',
            'full_name.min' => 'Please enter your full name.',
            'full_name.max' => 'Please keep your name to 30 characters or fewer.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address, like you@example.com.',
            'email.max' => 'That email address is too long.',
            'email.unique' => self::EMAIL_TAKEN,
            'phone.required' => 'Please enter your mobile number.',
            'phone.max' => 'That phone number is too long.',
            'password.required' => 'Please choose a password.',
            'password.confirmed' => 'The two passwords do not match.',
            'password.max' => 'Your password must be 255 characters or fewer.',
            // Password::defaults() reports each unmet requirement separately;
            // V::passwordMessages() replaces the framework wording with the
            // sentences the box itself uses while the password is being typed.
            // It is shared with every other form that mints a password, which
            // is why it is not spelled out here.
            ...V::passwordMessages(),
            'terms.accepted' => 'Please accept the Terms and Privacy Policy to continue.',
        ]);

        [$firstName, $lastName] = self::splitName($validated['full_name']);

        $email = SignupEmailVerification::normalizeEmail($validated['email']);
        $phone = IndianMobile::normalize($validated['phone']);

        // The address has to have been PROVED, and the server is the only thing
        // that gets a say in whether it was.
        //
        // Nothing in the request is consulted here. The form knows whether it
        // showed a tick, and a request can carry any field it likes saying so -
        // `emailVerified: true` costs nothing to type - so the only question
        // asked is whether THIS shop posted a link to THIS address and somebody
        // opened it. That fact lives in one row, keyed on the normalised
        // address, and it is read fresh on every attempt.
        //
        // Checked after validate() rather than as a rule inside it, so an
        // address that is malformed or already taken says only that. A rule
        // would print "verify your email" underneath, about an address the
        // customer is being told to change anyway.
        if ($email === null || ! self::ownershipProved($email)) {
            throw ValidationException::withMessages(['email' => self::EMAIL_UNVERIFIED]);
        }

        try {
            // One transaction around the whole thing. A signup that got as far
            // as a users row and then failed to spend its verification would
            // leave the proof lying around for a second account to use.
            $user = DB::transaction(function () use ($firstName, $lastName, $email, $phone, $validated) {
                // Re-checked here, inside the transaction, and not because the
                // rules above were wrong: they were right when they ran. Between
                // then and now is a window - a slow password hash is enough -
                // and the second signup in a race arrives to find the address or
                // the number taken by the first. Cheaper to ask than to unpick.
                if (self::addressIsRegistered($email)) {
                    throw ValidationException::withMessages(['email' => self::EMAIL_TAKEN]);
                }

                if ($phone !== null && self::mobileIsRegistered($phone)) {
                    throw ValidationException::withMessages(['phone' => self::MOBILE_TAKEN]);
                }

                // Locked for the rest of the transaction, so two requests
                // holding one verified address cannot both read it as unspent.
                $attempt = SignupEmailVerification::where('email', $email)
                    ->lockForUpdate()
                    ->first();

                if ($attempt === null || ! $attempt->provesOwnership()) {
                    throw ValidationException::withMessages(['email' => self::EMAIL_UNVERIFIED]);
                }

                $user = self::createAccount([
                    'uuid' => Str::uuid(),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    // Stored as typed, which is what every other row in this
                    // column already is. The uniqueness that matters is the
                    // database's, and the connection collation is
                    // utf8mb4_unicode_ci, so it does not care about the case
                    // either. Lower-casing on write here would make this the
                    // only row in the table that had been normalised.
                    'email' => $validated['email'],
                    // Store the canonical ten digits, which is what the column already
                    // holds elsewhere, rather than whatever spacing the visitor typed.
                    'phone' => $phone,
                    'password' => Hash::make($validated['password']),
                    // The address was proved before this row existed, which is
                    // the whole point of the flow - so the account starts
                    // verified rather than being sent a second link asking it to
                    // do again what it has just done. Registered still fires
                    // below; the framework's listener checks hasVerifiedEmail()
                    // and stands itself down.
                    'email_verified_at' => now(),
                    // Hard-coded, never taken from the request: `role` and `is_active`
                    // are the two columns a mass-assigned signup would want to reach.
                    'role' => 'customer',
                    'is_active' => true,
                ], $email, $phone);

                // Spent, and spent conditionally: the update only lands if the
                // row is still unconsumed, so a proof can back exactly one
                // account. The token hash goes with it, which is what stops the
                // link in the inbox from ever opening again.
                if (! $attempt->consume()) {
                    throw ValidationException::withMessages(['email' => self::EMAIL_UNVERIFIED]);
                }

                return $user;
            });
        } catch (DuplicateAccountException $e) {
            // The backstop, and the only guard that cannot be raced past: two
            // requests can both pass every check above and both reach the
            // INSERT, and exactly one of them comes back here. It used to come
            // back as a 500 with a MySQL error in it.
            return self::duplicateResponse($request, $e);
        }

        event(new Registered($user));

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('login')->with('success', 'Account created successfully! Please login to continue.');
    }

    /**
     * Has this shop posted a link to this address, and did somebody open it?
     *
     * The only source of truth for "verified" anywhere in the signup path.
     * Reads the row every time - a proof that expired a second ago is not a
     * proof, and neither is one that has already been spent on an account.
     */
    private static function ownershipProved(string $normalizedEmail): bool
    {
        return SignupEmailVerification::where('email', $normalizedEmail)
            ->first()
            ?->provesOwnership() === true;
    }

    /**
     * LOWER(TRIM(...)) rather than a plain where, and withTrashed().
     *
     * The production collation compares case-insensitively on its own, but the
     * app never lower-cases on write and GuestReviewController already settled
     * on saying so explicitly rather than leaning on it. withTrashed() because
     * users are soft-deleted and the unique index is not.
     */
    private static function addressIsRegistered(string $normalizedEmail): bool
    {
        return User::withTrashed()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
            ->exists();
    }

    /** The canonical ten digits are what the column holds, so that is what is compared. */
    private static function mobileIsRegistered(string $normalizedPhone): bool
    {
        return User::withTrashed()->where('phone', $normalizedPhone)->exists();
    }

    /**
     * Write the row, and name the collision if there is one.
     *
     * The catch has to be HERE, around the statement itself, rather than around
     * the transaction. InnoDB rolls back only the failing statement on a
     * duplicate key, so at this point the row that beat us is still readable
     * and can be named exactly. After the transaction has unwound, the same two
     * queries run on a new snapshot, and whether they see the winner is a
     * question about the isolation level rather than about this code - which is
     * how a lost race ends up reported as "please try again" instead of as the
     * one sentence that would tell the customer what to do about it.
     *
     * @param  array<string, mixed>  $attributes
     */
    private static function createAccount(array $attributes, string $email, ?string $phone): User
    {
        try {
            return User::create($attributes);
        } catch (UniqueConstraintViolationException) {
            $errors = [];

            // Read back from the table rather than parsed out of the driver's
            // message: that message is a vendor string, and the answer is one
            // query away.
            if (self::addressIsRegistered($email)) {
                $errors['email'] = [self::EMAIL_TAKEN];
            }

            if ($phone !== null && self::mobileIsRegistered($phone)) {
                $errors['phone'] = [self::MOBILE_TAKEN];
            }

            // Neither reads as taken, so the collision was on something else
            // this controller writes - in practice the uuid, which is a v4 and
            // will not repeat. Say the honest thing rather than naming a field.
            if ($errors === []) {
                $errors['email'] = ['We could not create your account just now. Please try again.'];
            }

            throw new DuplicateAccountException($errors);
        }
    }

    /**
     * What a lost race looks like to the customer.
     *
     * 409 for a caller that wanted JSON - the request was well-formed and
     * understood, and what stopped it was a row that already exists. The
     * `errors` envelope travels with it so the signup form, which reads
     * `.errors` off a rejected submit and has done since long before this, puts
     * the sentence under the right box without knowing the status code changed.
     * A normal form post keeps the redirect-back it has always had: a browser
     * following a 409 renders it as a page, and the modal that posted it would
     * be gone.
     */
    private static function duplicateResponse(Request $request, DuplicateAccountException $e): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'message' => $e->firstMessage(),
                'errors' => $e->errors,
            ], 409);
        }

        return back()
            ->withErrors($e->errors)
            ->withInput($request->except(['password', 'password_confirmation']));
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
