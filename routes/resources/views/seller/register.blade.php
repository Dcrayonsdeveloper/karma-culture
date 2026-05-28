<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Become a Seller - {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-primary-600 to-primary-800">
    <div class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <!-- Logo -->
        <div class="mb-8 text-center">
            <x-application-logo class="w-16 h-16 mx-auto mb-4" />
            <h1 class="text-2xl font-bold text-white">Become a Seller</h1>
            <p class="text-primary-200">Start selling on {{ config('app.name') }}</p>
        </div>

        <!-- Registration Form -->
        <div class="w-full max-w-lg">
            <div class="card p-8">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-success-50 border border-success-200 rounded-lg text-success-700 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('seller.register.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- First Name -->
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-neutral-700 mb-1">First name</label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required autofocus
                                   class="form-input w-full @error('first_name') border-error-300 @enderror"
                                   placeholder="John">
                            @error('first_name')
                                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-neutral-700 mb-1">Last name</label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required
                                   class="form-input w-full @error('last_name') border-error-300 @enderror"
                                   placeholder="Doe">
                            @error('last_name')
                                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-neutral-700 mb-1">Email address</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                               class="form-input w-full @error('email') border-error-300 @enderror"
                               placeholder="seller@example.com">
                        @error('email')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-neutral-700 mb-1">Phone number <span class="text-neutral-600">(optional)</span></label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                               class="form-input w-full @error('phone') border-error-300 @enderror"
                               placeholder="+1 (555) 000-0000">
                        @error('phone')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Business Name -->
                    <div>
                        <label for="business_name" class="block text-sm font-medium text-neutral-700 mb-1">Business name</label>
                        <input type="text" name="business_name" id="business_name" value="{{ old('business_name') }}" required
                               class="form-input w-full @error('business_name') border-error-300 @enderror"
                               placeholder="Your Store Name">
                        @error('business_name')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-neutral-700 mb-1">Business description <span class="text-neutral-600">(optional)</span></label>
                        <textarea name="description" id="description" rows="3"
                                  class="form-input w-full @error('description') border-error-300 @enderror"
                                  placeholder="Tell us about your business...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- GST Number -->
                    <div>
                        <label for="gst_number" class="block text-sm font-medium text-neutral-700 mb-1">GST number <span class="text-neutral-600">(optional)</span></label>
                        <input type="text" name="gst_number" id="gst_number" value="{{ old('gst_number') }}"
                               class="form-input w-full @error('gst_number') border-error-300 @enderror"
                               placeholder="GST number">
                        @error('gst_number')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">Password</label>
                            <input type="password" name="password" id="password" required
                                   class="form-input w-full @error('password') border-error-300 @enderror"
                                   placeholder="Min 8 characters">
                            @error('password')
                                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 mb-1">Confirm password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                   class="form-input w-full"
                                   placeholder="Confirm password">
                        </div>
                    </div>

                    <!-- Terms -->
                    <div>
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="terms" value="1" required
                                   class="mt-1 rounded border-neutral-300 text-primary-600 focus:ring-primary-500 @error('terms') border-error-300 @enderror">
                            <span class="text-sm text-neutral-600">
                                I agree to the <a href="{{ route('terms') }}" class="text-primary-600 hover:text-primary-700 underline" target="_blank">Terms of Service</a>
                                and <a href="{{ route('privacy') }}" class="text-primary-600 hover:text-primary-700 underline" target="_blank">Privacy Policy</a>
                            </span>
                        </label>
                        @error('terms')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-primary w-full">
                        Submit Application
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-neutral-200 text-center">
                    <p class="text-sm text-neutral-600">
                        Already have a seller account?
                        <a href="{{ route('seller.login') }}" class="font-medium text-primary-600 hover:text-primary-700">
                            Sign in
                        </a>
                    </p>
                </div>
            </div>

            <p class="mt-8 text-center text-sm text-primary-200">
                <a href="{{ url('/') }}" class="hover:text-white">
                    &larr; Back to store
                </a>
            </p>
        </div>
    </div>
</body>
</html>
