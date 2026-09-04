<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('partials.scroll-top-on-reload')
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
        /* 100vh on a phone is the tallest viewport (browser bar hidden), and this
           page never scrolls the document, so the bar never collapses: the last
           inch of the form sat below the screen. dvh tracks the visible area. */
        @supports (height: 100dvh) {
            .kk-auth-shell { height: 100dvh; }
        }
    </style>
</head>
<body class="font-sans antialiased bg-white" x-data>
    <div class="kk-auth-shell h-screen flex overflow-hidden">

        <!-- ==========================================
             LEFT SIDE - Login / Register Forms
             ========================================== -->
        <div class="w-full lg:w-1/2 flex flex-col px-6 sm:px-12 lg:px-16 xl:px-24 py-6 lg:py-8 relative overflow-y-auto"
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

            <div class="w-full max-w-md mx-auto my-auto">
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
                        @php $siteLogo = \App\Models\Setting::get('site_logo', ''); @endphp
                        @if($siteLogo)
                            <img src="{{ asset_v('storage/' . $siteLogo) }}" alt="{{ config('app.name', 'Karmaa Kulture') }}" class="h-16 lg:h-20 object-contain">
                        @else
                            <img src="{{ asset_v('images/karmaa-kulture-logo.png') }}" alt="Karmaa Kulture" class="h-16 lg:h-20 object-contain">
                        @endif
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

                        @if(session('error'))
                            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ session('error') }}
                            </div>
                        @endif

                        <!-- Welcome Text -->
                        <div class="mb-5">
                            <h1 class="text-2xl font-bold text-neutral-900 mb-1">Welcome Back</h1>
                            <p class="text-neutral-600 text-sm">Sign in to access your fashion collection</p>
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
                                    {{-- current-password, and it matters beyond autofill: it is what
                                         keeps the new-password policy off this box. An account made
                                         under the old eight-character minimum still has to be able
                                         to sign in - refusing its password here would lock the
                                         customer out of the one screen they could change it from. --}}
                                    <input :type="show ? 'text' : 'password'" name="password" id="login_password" required
                                           autocomplete="current-password"
                                           class="w-full pl-12 pr-12 py-2.5 bg-neutral-50 border border-neutral-400 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all @error('password') border-red-300 bg-red-50 @enderror"
                                           placeholder="Enter your password">
                                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pl-3 pr-4 flex items-center text-neutral-600 hover:text-neutral-600 transition-colors">
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
                                {{-- Same shape as the Terms box: the input carries its own
                                     styling so it stays focusable, and the tick is a sibling so
                                     peer-checked: can reach it. As a descendant of the old
                                     indicator div it never matched, and the tick never appeared. --}}
                                <label class="relative flex items-center cursor-pointer">
                                    <input type="checkbox" name="remember" id="remember" value="1" @checked(old('remember'))
                                           class="peer appearance-none w-5 h-5 shrink-0 rounded-md border-2 border-neutral-500 bg-white
                                                  checked:bg-[#2D1810] checked:border-[#3A6166]
                                                  focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:ring-offset-1
                                                  transition-all cursor-pointer">
                                    <svg class="pointer-events-none absolute left-0 w-5 h-5 p-1 text-white opacity-0 peer-checked:opacity-100 transition-opacity"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="ml-3 text-sm text-neutral-600">Keep me signed in</span>
                                </label>
                            </div>

                            <!-- Submit -->
                            <button type="submit"
                                    class="w-full py-3 px-6 bg-gradient-to-r from-[#2D1810] via-[#2D1810] to-[#1F1109] hover:from-[#1F1109] hover:via-[#1F1109] hover:to-[#1F1109] text-white font-semibold rounded-xl shadow-lg shadow-[#2D1810]/25 hover:shadow-[#2D1810]/40 transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/50 focus:ring-offset-2">
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

                        {{-- A rejected signup used to come back with only per-field messages,
                             several of which are gated behind old('_register') — so a failure
                             could land with nothing on screen explaining it. --}}
                        @if(old('_register') && $errors->any())
                            <div class="mb-5 p-3 bg-red-50 border border-red-200 rounded-xl">
                                <p class="text-sm font-medium text-red-800">We couldn't create your account.</p>
                                <ul class="mt-1 list-disc list-inside text-xs text-red-700 space-y-0.5">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @php
                            // Was this address already proved, before the submit that just
                            // bounced?
                            //
                            // A signup rejected for something ELSE - a mobile number that
                            // turns out to be taken is the likely one - comes back with the
                            // address still in the box and the browser's memory of having
                            // verified it gone. Without this the customer has to press
                            // Validate Email again to get a tick for an address this shop
                            // already knows they own.
                            //
                            // Read from the server's own row, never from the request, and
                            // only for the address the customer themself just submitted
                            // (old() is their session). An address that is not proved, or
                            // whose proof has expired or been spent, comes back empty.
                            $provedSignupEmail = '';

                            if (old('_register') && is_string(old('email'))) {
                                $normalized = \App\Models\SignupEmailVerification::normalizeEmail(old('email'));

                                if ($normalized !== null
                                    && \App\Models\SignupEmailVerification::where('email', $normalized)
                                        ->first()?->provesOwnership()) {
                                    $provedSignupEmail = $normalized;
                                }
                            }
                        @endphp

                        {{-- novalidate, with the checks moved into kkRegisterForm(): the browser's
                             own bubble names no field, shows one problem at a time and disappears
                             on the next click. The component reports every field at once, inline,
                             in the same words the server would have used - and it runs as each
                             field is left, rather than only when Create Account is pressed.

                             The form still posts normally: with JS off nothing here runs and the
                             server stays the only thing that has to be right. The summary box
                             above is what a no-JS shopper reads, which is why the per-field
                             messages below can be x-cloaked.

                             The seed carries whatever the server just said into the same message
                             slots the live checks write to, so one field can never end up showing
                             two contradictory messages. email and password stay gated on
                             old('_register') because the sign-in form on this page shares the
                             error bag. --}}
                        <form method="POST" action="{{ route('register') }}" class="space-y-4" novalidate
                              x-data="kkRegisterForm(@js([
                                  'full_name' => $errors->first('full_name'),
                                  'email' => old('_register') ? $errors->first('email') : '',
                                  'phone' => $errors->first('phone'),
                                  'password' => old('_register') ? $errors->first('password') : '',
                                  'terms' => $errors->first('terms'),
                              ]), @js($provedSignupEmail), @js([
                                  // The two verification endpoints, named here rather than
                                  // spelled out in app.js: the bundle is shared by every page
                                  // and has no business knowing this shop's URL shapes. The
                                  // status route is a template - the attempt's id does not
                                  // exist until the server has issued one. The placeholder is
                                  // __ID__ rather than :id because route() rawurlencodes its
                                  // parameters and a colon would arrive as %3A, which the
                                  // replace on the other side would never find.
                                  'create' => route('signup.email-verifications.store'),
                                  'status' => route('signup.email-verifications.show', ['uuid' => '__ID__']),
                              ]))"
                              @submit="onSubmit($event)">
                            @csrf
                            <input type="hidden" name="_register" value="1">

                            <!-- Full Name -->
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-neutral-700 mb-1.5">Full Name</label>
                                {{-- No `pattern`: the obvious [A-Za-z\s]{2,50} is Latin-only and would
                                     reject "रवि कुमार", "O'Connor" and "Mary-Anne" before the request is
                                     even sent. App\Rules\PersonName does the charset work server-side,
                                     in every script.

                                     maxlength IS the limit being asked for: 30, the same number the
                                     server holds as RegisterController::NAME_LIMIT. The box stops at
                                     the 30th character rather than letting a longer name be typed out
                                     in full and handed back on submit. _instantError()'s >30 message
                                     stays as the backstop - maxlength does not police a value set from
                                     script, and autofill arrives that way. --}}
                                <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}"
                                       required autocomplete="name" minlength="2" maxlength="30"
                                       x-ref="full_name" @blur="blur('full_name')" @input="input('full_name')"
                                       class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-400 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all"
                                       :class="errors.full_name && 'border-red-300 bg-red-50'"
                                       placeholder="e.g. Priya Sharma">
                                <p class="mt-1.5 text-xs text-red-600" x-show="errors.full_name" x-text="errors.full_name" x-cloak></p>
                            </div>

                            <!-- Email + Phone -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="reg_email" class="block text-sm font-medium text-neutral-700 mb-1.5">Email Address</label>
                                    <input type="email" name="email" id="reg_email" value="{{ old('_register') ? old('email') : '' }}"
                                           required autocomplete="email" inputmode="email" maxlength="255"
                                           x-ref="email" @blur="blur('email')" @input="input('email')"
                                           class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-400 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all"
                                           :class="errors.email && 'border-red-300 bg-red-50'"
                                           placeholder="you@example.com">
                                    {{-- Whether the address is already registered needs the database,
                                         so it is answered by the Validate Email request below rather
                                         than here - and it lands in this same slot, along with the
                                         message a rejected submit puts there. One box, one sentence,
                                         wherever it came from. --}}
                                    <p class="mt-1.5 text-xs text-red-600" x-show="errors.email" x-text="errors.email" x-cloak></p>

                                    {{-- Proving the address.

                                         Below the field rather than beside it: this column is half a
                                         two-column grid on sm and up, and a control on the same line
                                         as a 255-character email box has nowhere to be. Stacked, it
                                         costs one line of height and the modal keeps its width - the
                                         phone box beside it simply stays where it is.

                                         The button is only rendered once the address is a valid
                                         address, judged by the same rule the server uses
                                         (App\Rules\EmailAddress, mirrored as _emailError). Offering
                                         it on "asha@" would send a message nowhere and then ask the
                                         customer to wait for it. --}}
                                    <div class="mt-2" x-cloak>
                                        <p x-show="emailVerified"
                                           class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#3A6166]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Email Validated
                                        </p>

                                        <button type="button" x-show="showVerifyButton"
                                                @click="requestVerification()"
                                                :disabled="verifyButtonDisabled"
                                                x-text="verifyButtonLabel"
                                                class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold rounded-xl border border-[#3A6166]/40 text-[#3A6166] bg-white hover:bg-[#3A6166]/5 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-white"></button>

                                        {{-- The waiting state. Never says "validated": only the
                                             server's answer to the status poll writes that. --}}
                                        <p x-show="verifyNotice" x-text="verifyNotice" x-cloak
                                           class="mt-1.5 text-xs text-neutral-600 leading-snug"></p>
                                        <p x-show="verifyError" x-text="verifyError" x-cloak
                                           class="mt-1.5 text-xs text-amber-700 leading-snug"></p>
                                    </div>
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-neutral-700 mb-1.5">Mobile Number</label>
                                    {{-- type="tel", not type="number": a number input adds a spinner, accepts
                                         "e"/"+"/"-" and silently drops a leading zero. inputmode="numeric" is
                                         what actually raises the digit keypad. The pattern mirrors
                                         App\Rules\IndianMobile, which strips the +91/0 prefix and any spacing
                                         before testing the ten digits, so it deliberately tolerates more than
                                         a bare ^[6-9]\d{9}$ would — a client pattern stricter than the server
                                         just blocks valid input. --}}
                                    {{-- The pattern is kept for semantics but no longer gates submit
                                         (novalidate): _normalizeMobile() in app.js mirrors
                                         IndianMobile::normalize() step for step, including the
                                         +91/0 stripping and the repdigit refusal, which a regex in
                                         an attribute cannot express.

                                         data-kk-mobile="10" is what actually holds the box to ten
                                         digits: _capMobile() strips the decoration and the +91/0
                                         prefix on every keystroke and cuts what is left at ten, so
                                         an eleventh digit simply never lands. maxlength stays at 20
                                         on purpose - the browser applies it to a paste before any
                                         script runs, and "+91 98765 43210" is 15 characters that
                                         have to survive long enough to be normalised. --}}
                                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                                           required autocomplete="tel" inputmode="numeric" maxlength="20"
                                           data-kk-mobile="10"
                                           pattern="(\+?91[\s\-]?)?0?[6-9][0-9\s\-]{9,}"
                                           title="Enter a 10-digit Indian mobile number starting with 6, 7, 8 or 9."
                                           x-ref="phone" @blur="blur('phone')" @input="input('phone')"
                                           class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-400 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all"
                                           :class="errors.phone && 'border-red-300 bg-red-50'"
                                           placeholder="9876543210">
                                    <p class="mt-1.5 text-xs text-red-600" x-show="errors.phone" x-text="errors.phone" x-cloak></p>
                                </div>
                            </div>

                            <!-- Password + Confirm -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="reg_password" class="block text-sm font-medium text-neutral-700 mb-1.5">Password</label>
                                    {{-- The eye toggle's state is held by kkRegisterForm, NOT by a nested
                                         x-data on this wrapper.

                                         `x-ref` registers on the closest x-data root, and `$refs`
                                         only ever walks UP - so with `x-data="{ show: false }"` here
                                         the ref landed on this <div> and kkRegisterForm, which is the
                                         <form>, could not see it. messageFor('password') therefore
                                         read '' no matter what had been typed and answered "Please
                                         choose a password." for every password in the world; the
                                         confirmation check, which compares against that same ref,
                                         silently compared against nothing. onSubmit() then found an
                                         error it could not clear and called preventDefault(), so
                                         Create Account did nothing at all on this form.

                                         One flag per box, still: revealing the password does not
                                         reveal the confirmation, which is the point of typing it
                                         twice. --}}
                                    <div class="relative">
                                        {{-- data-kk-password="off": kkRegisterForm already judges this
                                             box on every keystroke and prints the message in the slot
                                             below. Without the opt-out the site-wide password module
                                             in app.js would print the same sentence a second time,
                                             under the same field. --}}
                                        <input :type="showPassword ? 'text' : 'password'" name="password" id="reg_password"
                                               required autocomplete="new-password" minlength="10" maxlength="255"
                                               data-kk-password="off"
                                               x-ref="password" @blur="blur('password')" @input="input('password')"
                                               class="w-full px-4 pr-11 py-2.5 bg-neutral-50 border border-neutral-400 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all"
                                               :class="errors.password && 'border-red-300 bg-red-50'"
                                               placeholder="Min 10 characters">
                                        <button type="button" @click="showPassword = !showPassword"
                                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                                class="absolute inset-y-0 right-0 pl-2.5 pr-3.5 flex items-center text-neutral-600 hover:text-neutral-600 transition-colors">
                                            <svg x-show="!showPassword" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <svg x-show="showPassword" x-cloak class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="mt-1.5 text-xs text-red-600" x-show="errors.password" x-text="errors.password" x-cloak></p>
                                    <p class="mt-1.5 text-[11px] text-neutral-600 leading-snug">
                                        10+ characters with an uppercase and a lowercase letter, a number
                                        and a special character.
                                    </p>
                                </div>
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 mb-1.5">Confirm Password</label>
                                    {{-- Its own `show`, not the one above: revealing a password the
                                         shopper can already see is not the point of this box - checking
                                         what they typed the second time is, and that is a different
                                         decision from the first field. --}}
                                    <div class="relative">
                                        <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" id="password_confirmation"
                                               required autocomplete="new-password" maxlength="255"
                                               data-kk-password="off"
                                               x-ref="password_confirmation"
                                               @blur="blur('password_confirmation')" @input="input('password_confirmation')"
                                               class="w-full px-4 pr-11 py-2.5 bg-neutral-50 border border-neutral-400 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all"
                                               :class="errors.password_confirmation && 'border-red-300 bg-red-50'"
                                               placeholder="Repeat password">
                                        <button type="button" @click="showConfirm = !showConfirm"
                                                :aria-label="showConfirm ? 'Hide password' : 'Show password'"
                                                class="absolute inset-y-0 right-0 pl-2.5 pr-3.5 flex items-center text-neutral-600 hover:text-neutral-600 transition-colors">
                                            <svg x-show="!showConfirm" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <svg x-show="showConfirm" x-cloak class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                            </svg>
                                        </button>
                                    </div>
                                    {{-- This field had no message slot at all: a mismatch is reported
                                         by the server against `password`, so the sentence appeared
                                         under the box the shopper had already got right. --}}
                                    <p class="mt-1.5 text-xs text-red-600" x-show="errors.password_confirmation" x-text="errors.password_confirmation" x-cloak></p>
                                </div>
                            </div>

                            <!-- Terms -->
                            {{-- The input must stay a real, visible, focusable control. It was
                                 `required` on an `sr-only` element, and Chrome refuses to focus a
                                 clipped control to show its validation bubble — so submitting with
                                 the box unticked aborted silently and the button looked dead.
                                 `appearance-none` keeps the custom look on the input itself, and
                                 the tick is now a sibling so `peer-checked:` can actually reach it. --}}
                            <div class="pt-1">
                                <label class="relative flex items-start cursor-pointer">
                                    <input type="checkbox" name="terms" id="terms" value="1" required
                                           @checked(old('terms'))
                                           class="peer appearance-none w-4.5 h-4.5 mt-0.5 shrink-0 rounded border-2 border-neutral-500 bg-white
                                                  checked:bg-[#2D1810] checked:border-[#3A6166]
                                                  focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:ring-offset-1
                                                  transition-all cursor-pointer"
                                           x-ref="terms" @change="blur('terms')"
                                           :class="errors.terms && 'border-red-400'">
                                    <svg class="pointer-events-none absolute left-0 top-0.5 w-4.5 h-4.5 p-0.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="ml-2.5 text-[13px] text-neutral-600 leading-snug">
                                        I agree to the
                                        <a href="{{ route('terms') }}" class="text-[#3A6166] hover:text-[#2A494D] font-medium">Terms</a>
                                        and
                                        <a href="{{ route('privacy') }}" class="text-[#3A6166] hover:text-[#2A494D] font-medium">Privacy Policy</a>
                                    </span>
                                </label>
                                <p class="mt-1.5 text-xs text-red-600" x-show="errors.terms" x-text="errors.terms" x-cloak></p>
                            </div>

                            <!-- Submit -->
                            {{-- Disabled until the address has been proved and every other field
                                 reads clean. It is a courtesy and nothing more: onSubmit() blocks
                                 an unverified post that arrives some other way (Enter in a field,
                                 or the attribute removed from a console), and RegisterController
                                 reads the verification row for itself and refuses it there. Three
                                 layers, and only the last one is load-bearing.

                                 x-cloak on the class binding, not the button: an unstyled flash is
                                 preferable to a Create Account button that is missing from the form
                                 for the moment before Alpine starts. --}}
                            <button type="submit"
                                    :disabled="!canSubmit"
                                    class="w-full py-3 px-6 bg-gradient-to-r from-[#2D1810] via-[#2D1810] to-[#1F1109] hover:from-[#1F1109] hover:via-[#1F1109] hover:to-[#1F1109] text-white font-semibold rounded-xl shadow-lg shadow-[#2D1810]/25 hover:shadow-[#2D1810]/40 transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/50 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none disabled:transform-none disabled:hover:translate-y-0">
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
                        tagline: 'Style That Speaks',
                        subtitle: 'Discover outfits that let you express yourself with comfort and confidence',
                        icon: 'sparkles'
                    },
                    {
                        bg: 'linear-gradient(135deg, #2A494D 0%, #3A6166 40%, #6F9CA2 100%)',
                        tagline: 'Premium Fashion Collection',
                        subtitle: 'Explore our curated range of trendy and comfortable clothing for every occasion',
                        icon: 'gem'
                    },
                    {
                        bg: 'linear-gradient(135deg, #1A3133 0%, #3A6166 40%, #5B878D 100%)',
                        tagline: 'Fashion for Every Adventure',
                        subtitle: 'From work to weekend, dress in style with our vibrant collection',
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

