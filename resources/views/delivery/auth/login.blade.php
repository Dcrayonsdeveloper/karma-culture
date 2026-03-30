<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Delivery Partner Login - {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css'])

    <style>
        body { font-family: 'Manrope', sans-serif; }
    </style>
</head>
<body class="antialiased bg-white min-h-screen flex">
    {{-- Left side - Branding --}}
    <div class="hidden lg:flex lg:w-1/2 bg-linear-to-br from-primary-900 via-primary-800 to-primary-950 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 400 400" fill="none">
                <circle cx="200" cy="200" r="150" stroke="white" stroke-width="0.5"/>
                <circle cx="200" cy="200" r="200" stroke="white" stroke-width="0.5"/>
                <circle cx="200" cy="200" r="250" stroke="white" stroke-width="0.5"/>
                <circle cx="200" cy="200" r="100" stroke="white" stroke-width="0.5"/>
            </svg>
        </div>
        <div class="relative z-10 flex flex-col justify-center px-12 xl:px-16">
            <img src="{{ asset('images/white_logo.webp') }}" alt="{{ config('app.name') }}" class="h-12 object-contain object-left mb-8" onerror="this.style.display='none'">
            <h1 class="text-3xl xl:text-4xl font-bold text-white leading-tight mb-4">
                Welcome to<br>Delivery Panel
            </h1>
            <p class="text-primary-200 text-base max-w-sm leading-relaxed">
                View your assigned orders, update delivery status, and manage your deliveries efficiently.
            </p>
            <div class="mt-10 flex items-center gap-6 text-primary-300 text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Track Orders
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Quick Updates
                </div>
            </div>
        </div>
    </div>

    {{-- Right side - Login form --}}
    <div class="flex-1 flex flex-col justify-center items-center px-6 sm:px-12 lg:px-16">
        <div class="w-full max-w-sm">
            {{-- Logo + Header --}}
            <div class="mb-8">
                <img src="{{ asset('images/colorlogo.png') }}" alt="{{ config('app.name') }}" class="h-10 object-contain mb-4">
                <h2 class="text-2xl font-bold text-neutral-900 mb-1">Sign in</h2>
                <p class="text-neutral-600 text-sm">Enter your credentials to access the delivery panel</p>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('delivery.login') }}" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-neutral-700 mb-1.5">Email address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-4 py-3 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-primary-500 focus:ring-0 transition-colors"
                           placeholder="partner@example.com">
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-neutral-700 mb-1.5">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                               class="w-full px-4 py-3 pr-11 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-primary-500 focus:ring-0 transition-colors"
                               placeholder="Enter your password">
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-neutral-600 hover:text-neutral-600 transition-colors">
                            <svg id="eye-off" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg id="eye-on" class="w-4.5 h-4.5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="flex items-center">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-neutral-300 bg-white text-primary-600 focus:ring-0">
                        <span class="text-sm text-neutral-600">Remember me</span>
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg text-sm transition-colors focus:outline-none focus:ring-0">
                    Sign in
                </button>
            </form>

            {{-- Back link --}}
            <p class="mt-8 text-center">
                <a href="{{ url('/') }}" class="text-sm text-neutral-600 hover:text-primary-600 transition-colors inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to store
                </a>
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const eyeOff = document.getElementById('eye-off');
            const eyeOn = document.getElementById('eye-on');
            if (input.type === 'password') {
                input.type = 'text';
                eyeOff.classList.add('hidden');
                eyeOn.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeOff.classList.remove('hidden');
                eyeOn.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
