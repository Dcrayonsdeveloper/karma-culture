<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password - {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .form-enter { animation: formIn 0.45s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        @keyframes formIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 1023px) {
            body { background: linear-gradient(180deg, #ffffff 0%, #fdf5ff 50%, #fae6ff 100%); }
        }
    </style>
</head>
<body class="font-sans antialiased bg-white" x-data>
    <div class="h-screen flex overflow-hidden">

        <!-- LEFT SIDE - Form -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center px-6 sm:px-12 lg:px-16 xl:px-24 py-6 lg:py-8 relative overflow-y-auto">

            <!-- Back to login link -->
            <a href="{{ route('login') }}" class="absolute top-6 left-6 sm:left-12 lg:left-16 xl:left-24 flex items-center gap-2 text-sm text-neutral-600 hover:text-[#6F9CA2] transition-colors group">
                <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to login
            </a>

            <div class="w-full max-w-md mx-auto form-enter">
                <!-- Logo -->
                <div class="mb-6">
                    <a href="{{ url('/') }}" class="inline-block">
                        <img src="{{ asset('images/colorlogo.png') }}" alt="{{ config('app.name') }}" class="h-12 lg:h-14 object-contain">
                    </a>
                </div>

                <!-- Header -->
                <div class="mb-5">
                    <div class="w-12 h-12 bg-[#6F9CA2]/10 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-neutral-900 mb-1">Set new password</h1>
                    <p class="text-neutral-600 text-sm">Your new password must be different from previously used passwords.</p>
                </div>

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 mb-5">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                            </svg>
                            <div class="text-sm">
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-neutral-700 mb-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input type="email" name="email" id="email" value="{{ $email ?? old('email') }}" required
                                   class="w-full pl-12 pr-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all"
                                   placeholder="you@example.com">
                        </div>
                    </div>

                    <!-- New Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">New Password</label>
                        <div class="relative" x-data="{ show: false }">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input :type="show ? 'text' : 'password'" name="password" id="password" required
                                   class="w-full pl-12 pr-12 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all"
                                   placeholder="Min 8 characters">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 flex items-center text-neutral-600 hover:text-neutral-600 transition-colors">
                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 mb-1">Confirm Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                                </svg>
                            </div>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                   class="w-full pl-12 pr-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/20 focus:border-[#6F9CA2] transition-all"
                                   placeholder="Repeat new password">
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit"
                            class="w-full py-3 px-6 bg-gradient-to-r from-[#F8931D] via-[#F8931D] to-[#E07E0A] hover:from-[#E07E0A] hover:via-[#E07E0A] hover:to-[#D47200] text-white font-semibold rounded-xl shadow-lg shadow-[#F8931D]/25 hover:shadow-[#F8931D]/40 transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/50 focus:ring-offset-2">
                        Reset Password
                    </button>
                </form>

                <!-- Back to Login -->
                <p class="mt-5 text-center text-sm text-neutral-600">
                    Remember your password?
                    <a href="{{ route('login') }}" class="font-semibold text-[#6F9CA2] hover:text-[#5B878D] transition-colors">Sign in</a>
                </p>

                <!-- Footer -->
                <div class="mt-6 pt-4 border-t border-neutral-100 text-center">
                    <p class="text-xs text-neutral-600">
                        <a href="{{ route('terms') }}" class="text-neutral-600 hover:text-[#6F9CA2] underline transition-colors">Terms</a>
                        &
                        <a href="{{ route('privacy') }}" class="text-neutral-600 hover:text-[#6F9CA2] underline transition-colors">Privacy Policy</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE - Visual -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden items-center justify-center"
             style="background: linear-gradient(135deg, #5d0069 0%, #9c00ad 40%, #de6cff 100%);">

            <!-- Decorative Elements -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -top-32 -right-32 w-96 h-96 bg-white/5 rounded-full"></div>
                <div class="absolute -bottom-48 -left-24 w-[500px] h-[500px] bg-white/5 rounded-full"></div>
                <div class="absolute top-1/4 left-1/4 w-2 h-2 bg-white/20 rounded-full"></div>
                <div class="absolute top-1/3 right-1/3 w-3 h-3 bg-white/10 rounded-full"></div>
                <div class="absolute bottom-1/4 left-1/3 w-2 h-2 bg-white/15 rounded-full"></div>
                <div class="absolute top-2/3 right-1/4 w-4 h-4 bg-white/10 rounded-full"></div>
                <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 40px 40px;"></div>
            </div>

            <!-- Content -->
            <div class="relative z-10 text-center px-12 xl:px-20 max-w-lg">
                <div class="mb-8 flex justify-center">
                    <div class="w-20 h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/20">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                    </div>
                </div>
                <h2 class="text-4xl xl:text-5xl font-bold text-white mb-4 leading-tight">Almost There</h2>
                <p class="text-[#6F9CA2]/40 text-base xl:text-lg leading-relaxed">Choose a strong password to keep your account safe and secure</p>
                <div class="mt-8 flex justify-center">
                    <div class="w-16 h-0.5 bg-white/30 rounded-full"></div>
                </div>
            </div>

            <!-- Bottom brand text -->
            <div class="absolute bottom-10 right-10 z-20">
                <p class="text-white/40 text-xs tracking-widest uppercase">{{ config('app.name') }}</p>
            </div>
        </div>

    </div>
</body>
</html>
