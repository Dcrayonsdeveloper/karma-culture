<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Seller Login - {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-primary-600 to-primary-800">
    <div class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <!-- Logo -->
        <div class="mb-8 text-center">
            <x-application-logo class="w-16 h-16 mx-auto mb-4" />
            <h1 class="text-2xl font-bold text-white">Seller Center</h1>
            <p class="text-primary-200">Sign in to manage your store</p>
        </div>

        <!-- Login Form -->
        <div class="w-full max-w-md">
            <div class="card p-8">
                <form method="POST" action="{{ route('seller.login') }}" class="space-y-6">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-neutral-700 mb-1">Email address</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                               class="form-input w-full @error('email') border-error-300 @enderror"
                               placeholder="seller@example.com">
                        @error('email')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">Password</label>
                        <input type="password" name="password" id="password" required
                               class="form-input w-full @error('password') border-error-300 @enderror"
                               placeholder="Enter your password">
                        @error('password')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-neutral-600">Remember me</span>
                        </label>
                        <a href="#" class="text-sm text-primary-600 hover:text-primary-700">Forgot password?</a>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-primary w-full">
                        Sign in to Seller Center
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-neutral-200 text-center">
                    <p class="text-sm text-neutral-600">
                        Don't have a seller account?
                        <a href="{{ route('seller.register') }}" class="font-medium text-primary-600 hover:text-primary-700">
                            Register now
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
