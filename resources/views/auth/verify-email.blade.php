{{--
    The "check your inbox" page.

    VerificationController::show() has always rendered `auth.verify-email`, and
    the file did not exist - so every unverified customer who reached
    /email/verify, which is where the auth middleware sends them, got a 500
    instead of an explanation and a way to ask for the link again.
--}}
<x-layouts.app :title="'Verify your email'">
    <div class="container mx-auto px-4 py-16">
        <div class="mx-auto max-w-md text-center">
            <h1 class="text-2xl font-semibold text-kk-brown">Confirm your email address</h1>

            <p class="mt-4 text-sm leading-relaxed text-gray-600">
                We sent a verification link to
                <span class="font-medium text-kk-brown">{{ auth()->user()?->email }}</span>.
                Open it to finish setting up your account.
            </p>

            @if (session('status') === 'verification-link-sent' || session('success'))
                <p class="mt-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">
                    A new link is on its way. It can take a minute to arrive.
                </p>
            @endif

            @if (session('error'))
                <p class="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </p>
            @endif

            <p class="mt-6 text-sm text-gray-500">
                Nothing in your inbox? Check the spam folder, then send it again.
            </p>

            <form method="POST" action="{{ route('verification.resend') }}" class="mt-6">
                @csrf
                <button type="submit"
                        class="w-full rounded-lg bg-kk-brown px-6 py-3 text-[13px] font-semibold uppercase tracking-[0.12em] text-white transition hover:opacity-90">
                    Send the link again
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="text-sm text-gray-500 underline underline-offset-4 hover:text-kk-brown">
                    Sign in with a different account
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
