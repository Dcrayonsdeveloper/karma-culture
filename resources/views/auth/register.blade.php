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
            <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" required autofocus
                   class="form-input w-full @error('full_name') border-error-300 @enderror"
                   placeholder="John Doe">
            @error('full_name')
                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-neutral-700 mb-1">Email address</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                   class="form-input w-full @error('email') border-error-300 @enderror"
                   placeholder="you@example.com">
            @error('email')
                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Phone (Optional) -->
        <div>
            <label for="phone" class="block text-sm font-medium text-neutral-700 mb-1">
                Phone number <span class="text-neutral-600">(optional)</span>
            </label>
            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                   class="form-input w-full @error('phone') border-error-300 @enderror"
                   placeholder="+91 98765 43210">
            @error('phone')
                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">Password</label>
            <input type="password" name="password" id="password" required
                   class="form-input w-full @error('password') border-error-300 @enderror"
                   placeholder="Create a strong password">
            @error('password')
                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-neutral-600">Must be at least 8 characters</p>
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 mb-1">Confirm password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required
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
        <button type="submit" class="btn-primary w-full">
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
