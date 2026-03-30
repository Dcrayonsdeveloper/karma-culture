<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    <!-- SEO Meta Tags -->
    @stack('meta')

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#6F9CA2">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|poppins:300,400,500,600,700|inter:300,400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Critical CSS first to prevent flash -->
    @vite(['resources/css/critical.css'])

    <!-- Main Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="font-sans antialiased bg-white text-[#222222]" style="font-family: 'Poppins', sans-serif;" x-data>
    <!-- Toast Notifications -->
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2">
        <template x-for="toast in $store.toast.items" :key="toast.id">
            <div x-show="true"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-8"
                 class="card px-4 py-3 flex items-center gap-3 shadow-lg border"
                 :class="{
                     'bg-success-50 border-success-200 text-success-800': toast.type === 'success',
                     'bg-error-50 border-error-200 text-error-800': toast.type === 'error',
                     'bg-warning-50 border-warning-200 text-warning-800': toast.type === 'warning',
                     'bg-info-50 border-info-200 text-info-800': toast.type === 'info'
                 }">
                <span x-text="toast.message"></span>
                <button @click="$store.toast.remove(toast.id)" class="text-current opacity-60 hover:opacity-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    <!-- Skip to main content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-primary-500 text-white px-4 py-2 rounded-md z-50">
        Skip to main content
    </a>

    <!-- Header -->
    @include('partials.header')

    <!-- Mobile Navigation -->
    @include('partials.mobile-nav')

    <!-- Main Content -->
    <main id="main-content" class="min-h-screen">
        {{ $slot }}
    </main>

    <!-- Footer -->
    @include('partials.footer')

    <!-- Mobile Bottom Navigation -->
    @include('partials.mobile-bottom-nav')

    @stack('scripts')
</body>
</html>
