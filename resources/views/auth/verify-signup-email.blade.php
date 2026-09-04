{{-- Where the link in the verification email lands.

     Four endings, one page. Every one of them is a rendered result with a
     status code rather than an error: an expired link and a link that has
     already done its job are both ordinary things for a customer to arrive
     with, and neither is a fault they can do anything about except ask for
     another one.

     Uses the existing guest shell, so this looks like the rest of the signed-out
     site without a line of new styling. --}}
@php
    $copy = match ($state) {
        'verified' => [
            'title' => 'Email verified successfully',
            'body' => 'You can return to the signup page and create your account.',
            'tone' => 'success',
        ],
        'already_verified' => [
            'title' => 'This email is already verified',
            'body' => 'Nothing more to do here - head back to the signup page and finish creating your account.',
            'tone' => 'success',
        ],
        'expired' => [
            'title' => 'This verification link has expired',
            'body' => 'Please request a new verification email from the signup form.',
            'tone' => 'warning',
        ],
        default => [
            'title' => 'This verification link is not valid',
            'body' => 'It may have already been used to create an account, or it may have been mistyped. Try signing in, or request a new verification email from the signup form.',
            'tone' => 'warning',
        ],
    };
@endphp

<x-layouts.guest>
    <x-slot name="title">{{ $copy['title'] }} - {{ config('app.name') }}</x-slot>

    <div class="text-center">
        @if($copy['tone'] === 'success')
            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-[#3A6166]/10">
                <svg class="h-7 w-7 text-[#3A6166]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        @else
            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
                <svg class="h-7 w-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>
        @endif

        <h1 class="text-xl font-bold text-neutral-900 mb-2">{{ $copy['title'] }}</h1>

        @if($email)
            <p class="text-sm text-neutral-600 mb-1">{{ $email }}</p>
        @endif

        <p class="text-sm text-neutral-600 mb-7">{{ $copy['body'] }}</p>

        <a href="{{ route('login', ['mode' => 'register']) }}"
           class="inline-flex w-full items-center justify-center py-3 px-6 bg-gradient-to-r from-[#2D1810] via-[#2D1810] to-[#1F1109] hover:from-[#1F1109] hover:via-[#1F1109] hover:to-[#1F1109] text-white font-semibold rounded-xl shadow-lg shadow-[#2D1810]/25 hover:shadow-[#2D1810]/40 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/50 focus:ring-offset-2">
            Back to signup
        </a>

        <p class="mt-6 text-sm text-neutral-600">
            Already have an account?
            <a href="{{ route('login') }}" class="font-semibold text-[#3A6166] hover:text-[#2A494D] transition-colors">Sign in</a>
        </p>
    </div>
</x-layouts.guest>
