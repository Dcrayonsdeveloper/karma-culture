<x-layouts.guest>
    <x-slot name="title">Create Account - {{ config('app.name') }}</x-slot>

    <h1 class="text-2xl font-bold text-neutral-900 text-center mb-2">Create your account</h1>
    <p class="text-neutral-600 text-center mb-8">Join thousands of happy shoppers</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="_register" value="1">

        <!-- Full Name -->
        <div>
            <label for="full_name" class="block text-sm font-medium text-neutral-700 mb-1">Full Name</label>
            {{-- No `pattern` here on purpose. The obvious one, [A-Za-z\s]{2,50},
                 is Latin-only: it rejects "रवि कुमार" and "山田太郎" outright, and it also
                 rejects "O'Connor" and "Mary-Anne". The App\Rules\PersonName rule
                 behind this field allows any script plus those four separators,
                 and a client pattern must never be stricter than the server.

                 maxlength is the 30 the server asks for (RegisterController::NAME_LIMIT),
                 not a looser hard bound above it, so the box stops at the 30th
                 character instead of taking a name it is about to hand back. --}}
            <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}"
                   required autofocus autocomplete="name" minlength="2" maxlength="30"
                   class="form-input w-full @error('full_name') border-error-300 @enderror"
                   placeholder="e.g. Priya Sharma">
            @error('full_name')
                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-neutral-700 mb-1">Email address</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}"
                   required autocomplete="email" inputmode="email" maxlength="255"
                   class="form-input w-full @error('email') border-error-300 @enderror"
                   placeholder="you@example.com">
            @error('email')
                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Mobile number -->
        <div>
            <label for="phone" class="block text-sm font-medium text-neutral-700 mb-1">Mobile number</label>
            {{-- type="tel", not type="number": a number input shows a spinner,
                 accepts "e", "+" and "-", and silently drops a leading zero -
                 none of which a phone field wants. inputmode="numeric" is what
                 actually raises the digit keypad on a phone.

                 The pattern mirrors App\Rules\IndianMobile, which strips the
                 +91/0 prefix and any spacing before checking the ten digits, so
                 it has to tolerate the shapes people paste in rather than
                 demanding a bare ^[6-9]\d{9}$. --}}
            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                   required autocomplete="tel" inputmode="numeric" maxlength="20"
                   pattern="(\+?91[\s\-]?)?0?[6-9][0-9\s\-]{9,}"
                   title="Enter a 10-digit Indian mobile number starting with 6, 7, 8 or 9."
                   class="form-input w-full @error('phone') border-error-300 @enderror"
                   placeholder="98765 43210">
            @error('phone')
                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">Password</label>
            <input type="password" name="password" id="password"
                   required autocomplete="new-password" minlength="10" maxlength="255"
                   class="form-input w-full @error('password') border-error-300 @enderror"
                   placeholder="Create a strong password">
            @error('password')
                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-neutral-600">
                At least 10 characters, including an uppercase and a lowercase letter,
                a number and a special character.
            </p>
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 mb-1">Confirm password</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   required autocomplete="new-password" maxlength="255"
                   class="form-input w-full"
                   placeholder="Confirm your password">
        </div>

        <!-- Terms -->
        <div class="flex items-start">
            <input type="checkbox" name="terms" id="terms" required
                   class="w-4 h-4 mt-0.5 rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
            <label for="terms" class="ml-2 text-sm text-neutral-600">
                I agree to the
                <a href="{{ route('terms') }}" class="text-primary-600 hover:text-primary-700">Terms of Service</a>
                and
                <a href="{{ route('privacy') }}" class="text-primary-600 hover:text-primary-700">Privacy Policy</a>
            </label>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-primary w-full">
            Create account
        </button>
    </form>

    <!-- Login Link -->
    <p class="mt-8 text-center text-sm text-neutral-600">
        Already have an account?
        <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-700">
            Sign in
        </a>
    </p>
</x-layouts.guest>
