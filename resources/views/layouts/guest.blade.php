<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#6F9CA2">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-neutral-100" x-data>
    <div class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <!-- Logo -->
        <div class="mb-8">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <x-application-logo class="w-12 h-12" />
                <span class="text-xl font-bold text-neutral-900">{{ config('app.name') }}</span>
            </a>
        </div>

        <!-- Content Card -->
        <div class="w-full max-w-md">
            <div class="card p-8">
                {{ $slot }}
            </div>
        </div>

        <!-- Footer Links -->
        <div class="mt-8 text-center text-sm text-neutral-600">
            <a href="{{ url('/') }}" class="hover:text-primary-500">Home</a>
            <span class="mx-2">&middot;</span>
            <a href="{{ route('privacy') }}" class="hover:text-primary-500">Privacy Policy</a>
            <span class="mx-2">&middot;</span>
            <a href="{{ route('terms') }}" class="hover:text-primary-500">Terms of Service</a>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
