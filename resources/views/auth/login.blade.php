<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .form-panel { transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
        .form-enter { animation: formIn 0.45s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        .form-leave { animation: formOut 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        @keyframes formIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes formOut {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(-12px); }
        }
        @media (max-width: 1023px) {
            body { background: linear-gradient(180deg, #ffffff 0%, #f0f7f8 50%, #d9ecee 100%); }
        }
    </style>
</head>
<body class="font-sans antialiased bg-white" x-data>
    <div class="h-screen flex overflow-hidden">

        <!-- ==========================================
             LEFT SIDE - Login / Register Forms
             ========================================== -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center px-6 sm:px-12 lg:px-16 xl:px-24 py-6 lg:py-8 relative overflow-y-auto"
             x-data="{
                mode: '{{ $errors->has('full_name') || $errors->has('phone') || $errors->has('terms') || old('_register') || request()->get('mode') === 'register' ? 'register' : 'login' }}',
                switching: false,
                switchTo(newMode) {
                    if (this.mode === newMode) return;
                    this.switching = true;
                    setTimeout(() => {
                        this.mode = newMode;
                        this.switching = false;
                    }, 300);
                }
             }">

            <div class="w-full max-w-md mx-auto">
                <!-- Back to home link -->
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-sm text-neutral-600 hover:text-[#3A6166] transition-colors group mb-6 mt-2">
                    <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to store
                </a>

                <!-- Logo -->
                <div class="mb-6">
                    <a href="{{ url('/') }}" class="inline-block">
                        <img src="{{ asset('images/colorlogo.png') }}" alt="{{ config('app.name') }}" class="h-12 lg:h-14 object-contain">
                    </a>
                </div>

                <!-- Form Container -->
                <div class="relative"
                     :class="switching ? 'form-leave' : 'form-enter'">

                    <!-- ============================
                         LOGIN FORM
                         ============================ -->
                    <div x-show="mode === 'login'" x-cloak:remove>

                        @if(session('success'))
                            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ session('success') }}
                            </div>
                        @endif

                        <!-- Welcome Text -->
                        <div class="mb-5">
                            <h1 class="text-2xl font-bold text-neutral-900 mb-1">Welcome Back</h1>
                            <p class="text-neutral-600 text-sm">Sign in to access your kids' fashion collection</p>
                        </div>

                        <form method="POST" action="{{ route('login') }}" class="space-y-4">
                            @csrf

                            <!-- Email -->
                            <div>
                                <label for="login_email" class="block text-sm font-medium text-neutral-700 mb-1">Email Address</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <input type="email" name="email" id="login_email" value="{{ old('_register') ? '' : old('email') }}" required
                                           class="w-full pl-12 pr-4 py-2.5 bg-neutral-50 border border-neutral-400 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all @error('email') border-red-300 bg-red-50 @enderror"
                                           placeholder="you@example.com">
                                </div>
                                @if(!old('_register'))
                                    @error('email')
                                        <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                @endif
                            </div>

                            <!-- Password -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="login_password" class="block text-sm font-medium text-neutral-700">Password</label>
                                    <a href="{{ route('password.request') }}" class="text-sm text-[#3A6166] hover:text-[#2A494D] font-medium transition-colors">
                                        Forgot password?
                                    </a>
                                </div>
                                <div class="relative" x-data="{ show: false }">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </div>
                                    <input :type="show ? 'text' : 'password'" name="password" id="login_password" required
                                           class="w-full pl-12 pr-12 py-2.5 bg-neutral-50 border border-neutral-400 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all @error('password') border-red-300 bg-red-50 @enderror"
                                           placeholder="Enter your password">
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
                                @if(!old('_register'))
                                    @error('password')
                                        <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                @endif
                            </div>

                            <!-- Remember Me -->
                            <div class="flex items-center">
                                <label class="relative flex items-center cursor-pointer">
                                    <input type="checkbox" name="remember" id="remember" class="peer sr-only" {{ old('remember') ? 'checked' : '' }}>
                                    <div class="w-5 h-5 border-2 border-neutral-500 rounded-md peer-checked:bg-[#F8931D] peer-checked:border-[#3A6166] transition-all flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <span class="ml-3 text-sm text-neutral-600">Keep me signed in</span>
                                </label>
                            </div>

                            <!-- Submit -->
                            <button type="submit"
                                    class="w-full py-3 px-6 bg-gradient-to-r from-[#F8931D] via-[#F8931D] to-[#E07E0A] hover:from-[#E07E0A] hover:via-[#E07E0A] hover:to-[#D47200] text-white font-semibold rounded-xl shadow-lg shadow-[#F8931D]/25 hover:shadow-[#F8931D]/40 transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/50 focus:ring-offset-2">
                                Sign In
                            </button>
                        </form>

                        <!-- Switch to Register -->
                        <p class="mt-5 text-center text-sm text-neutral-600">
                            New to {{ config('app.name') }}?
                            <button @click="switchTo('register')" class="font-semibold text-[#3A6166] hover:text-[#2A494D] transition-colors">
                                Create an account
                            </button>
                        </p>
                    </div>

                    <!-- ============================
                         REGISTER FORM
                         ============================ -->
                    <div x-show="mode === 'register'" x-cloak>

                        <!-- Welcome Text -->
                        <div class="mb-7">
                            <h1 class="text-2xl font-bold text-neutral-900 mb-1">Create Account</h1>
                            <p class="text-neutral-600 text-sm">Join the {{ config('app.name') }} family</p>
                        </div>

                        <form method="POST" action="{{ route('register') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="_register" value="1">

                            <!-- Full Name -->
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-neutral-700 mb-1.5">Full Name</label>
                                <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" required
                                       class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-400 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all @error('full_name') border-red-300 bg-red-50 @enderror"
                                       placeholder="Enter your full name">
                                @error('full_name')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email + Phone -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="reg_email" class="block text-sm font-medium text-neutral-700 mb-1.5">Email Address</label>
                                    <input type="email" name="email" id="reg_email" value="{{ old('_register') ? old('email') : '' }}" required
                                           class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-400 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all @if(old('_register')) @error('email') border-red-300 bg-red-50 @enderror @endif"
                                           placeholder="you@example.com">
                                    @if(old('_register'))
                                        @error('email')
                                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    @endif
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-neutral-700 mb-1.5">
                                        Phone <span class="text-neutral-600 font-normal">(optional)</span>
                                    </label>
                                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                                           class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-400 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all @error('phone') border-red-300 bg-red-50 @enderror"
                                           placeholder="+91 98765 43210">
                                    @error('phone')
                                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Password + Confirm -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="reg_password" class="block text-sm font-medium text-neutral-700 mb-1.5">Password</label>
                                    <div class="relative" x-data="{ show: false }">
                                        <input :type="show ? 'text' : 'password'" name="password" id="reg_password" required
                                               class="w-full px-4 pr-11 py-2.5 bg-neutral-50 border border-neutral-400 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all @if(old('_register')) @error('password') border-red-300 bg-red-50 @enderror @endif"
                                               placeholder="Min 8 characters">
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-neutral-600 hover:text-neutral-600 transition-colors">
                                            <svg x-show="!show" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <svg x-show="show" x-cloak class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                            </svg>
                                        </button>
                                    </div>
                                    @if(old('_register'))
                                        @error('password')
                                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    @endif
                                </div>
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 mb-1.5">Confirm Password</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation" required
                                           class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-400 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all"
                                           placeholder="Repeat password">
                                </div>
                            </div>

                            <!-- Terms -->
                            <div class="flex items-start pt-1">
                                <label class="relative flex items-start cursor-pointer">
                                    <input type="checkbox" name="terms" id="terms" required class="peer sr-only">
                                    <div class="w-4.5 h-4.5 mt-0.5 border-2 border-neutral-500 rounded peer-checked:bg-[#F8931D] peer-checked:border-[#3A6166] transition-all flex items-center justify-center shrink-0">
                                        <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <span class="ml-2.5 text-[13px] text-neutral-600 leading-snug">
                                        I agree to the
                                        <a href="{{ route('terms') }}" class="text-[#3A6166] hover:text-[#2A494D] font-medium">Terms</a>
                                        and
                                        <a href="{{ route('privacy') }}" class="text-[#3A6166] hover:text-[#2A494D] font-medium">Privacy Policy</a>
                                    </span>
                                </label>
                            </div>

                            <!-- Submit -->
                            <button type="submit"
                                    class="w-full py-3 px-6 bg-gradient-to-r from-[#F8931D] via-[#F8931D] to-[#E07E0A] hover:from-[#E07E0A] hover:via-[#E07E0A] hover:to-[#D47200] text-white font-semibold rounded-xl shadow-lg shadow-[#F8931D]/25 hover:shadow-[#F8931D]/40 transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/50 focus:ring-offset-2">
                                Create Account
                            </button>
                        </form>

                        <!-- Switch to Login -->
                        <p class="mt-6 text-center text-sm text-neutral-600">
                            Already have an account?
                            <button @click="switchTo('login')" class="font-semibold text-[#3A6166] hover:text-[#2A494D] transition-colors">
                                Sign in
                            </button>
                        </p>
                    </div>

                </div>

                <!-- Footer -->
                <div class="mt-6 pt-4 border-t border-neutral-100 text-center">
                    <p class="text-xs text-neutral-600">
                        By continuing, you agree to our
                        <a href="{{ route('terms') }}" class="text-neutral-600 hover:text-[#3A6166] underline transition-colors">Terms</a>
                        &
                        <a href="{{ route('privacy') }}" class="text-neutral-600 hover:text-[#3A6166] underline transition-colors">Privacy Policy</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- ==========================================
             RIGHT SIDE - Image Carousel
             ========================================== -->
        <div class="hidden lg:block lg:w-1/2 relative overflow-hidden"
             x-data="{
                current: 0,
                slides: [
                    {
                        bg: 'linear-gradient(135deg, #1A3133 0%, #2A494D 40%, #4A7A80 100%)',
                        tagline: 'Little Styles, Big Smiles',
                        subtitle: 'Discover adorable outfits that let your kids express themselves with comfort and joy',
                        icon: 'sparkles'
                    },
                    {
                        bg: 'linear-gradient(135deg, #2A494D 0%, #3A6166 40%, #6F9CA2 100%)',
                        tagline: 'Premium Kids Collection',
                        subtitle: 'Explore our curated range of trendy and comfortable clothing designed for growing kids',
                        icon: 'gem'
                    },
                    {
                        bg: 'linear-gradient(135deg, #1A3133 0%, #3A6166 40%, #5B878D 100%)',
                        tagline: 'Fashion for Every Adventure',
                        subtitle: 'From playtime to party time, dress your little ones in style with our vibrant collection',
                        icon: 'palette'
                    }
                ],
                total: 3
             }"
             x-init="setInterval(() => current = (current + 1) % total, 5000)">

            <!-- Slides -->
            <template x-for="(slide, index) in slides" :key="index">
                <div x-show="current === index"
                     x-transition:enter="transition ease-out duration-1000"
                     x-transition:enter-start="opacity-0 scale-105"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-700"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute inset-0 flex items-center justify-center"
                     :style="'background: ' + slide.bg">

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

                    <!-- Slide Content -->
                    <div class="relative z-10 text-center px-12 xl:px-20 max-w-lg">
                        <div class="mb-8 flex justify-center">
                            <div class="w-20 h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/20">
                                <template x-if="slide.icon === 'sparkles'">
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                                    </svg>
                                </template>
                                <template x-if="slide.icon === 'gem'">
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2L2 7l10 15L22 7l-10-5zM2 7h20M12 22V7M7 4.5L12 7l5-2.5"/>
                                    </svg>
                                </template>
                                <template x-if="slide.icon === 'palette'">
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008z"/>
                                    </svg>
                                </template>
                            </div>
                        </div>
                        <h2 class="text-4xl xl:text-5xl font-bold text-white mb-4 leading-tight" x-text="slide.tagline"></h2>
                        <p class="text-base xl:text-lg leading-relaxed" style="color: rgba(255,255,255,0.6);" x-text="slide.subtitle"></p>
                        <div class="mt-8 flex justify-center">
                            <div class="w-16 h-0.5 bg-white/30 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Slide indicators -->
            <div class="absolute bottom-10 left-1/2 -translate-x-1/2 flex items-center gap-3 z-20">
                <template x-for="(slide, index) in slides" :key="'dot-' + index">
                    <button @click="current = index"
                            :class="current === index ? 'bg-white w-8' : 'bg-white/40 w-2.5 hover:bg-white/60'"
                            class="h-2.5 rounded-full transition-all duration-500"></button>
                </template>
            </div>

            <!-- Bottom brand text -->
            <div class="absolute bottom-10 right-10 z-20">
                <p class="text-xs tracking-widest uppercase" style="color: rgba(255,255,255,0.7);">{{ config('app.name') }}</p>
            </div>
        </div>

    </div>
</body>
</html>
