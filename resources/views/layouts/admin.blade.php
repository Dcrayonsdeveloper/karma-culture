<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin' }} - {{ config('app.name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#6F9CA2">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="font-sans antialiased bg-neutral-100" x-data="{ sidebarOpen: false }">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        @include('admin.partials.sidebar')

        <!-- Content area -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Admin Header -->
            @include('admin.partials.header')

            <!-- Main content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Breadcrumb -->
                @isset($breadcrumbs)
                    <nav class="mb-4 text-sm" aria-label="Breadcrumb">
                        <ol class="flex items-center gap-2">
                            @foreach($breadcrumbs as $breadcrumb)
                                <li class="flex items-center gap-2">
                                    @if(!$loop->first)
                                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    @endif
                                    @if($loop->last)
                                        <span class="text-neutral-600">{{ $breadcrumb['label'] }}</span>
                                    @else
                                        <a href="{{ $breadcrumb['url'] }}" class="text-primary-600 hover:text-primary-700">
                                            {{ $breadcrumb['label'] }}
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endisset

                <!-- Page header -->
                @isset($header)
                    <div class="mb-6">
                        {{ $header }}
                    </div>
                @endisset

                <!-- Flash messages -->
                @if(session('success'))
                    <div class="mb-4 p-4 bg-success-50 border border-success-200 text-success-800 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 p-4 bg-error-50 border border-error-200 text-error-800 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Main content slot -->
                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
