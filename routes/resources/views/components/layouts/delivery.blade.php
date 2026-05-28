<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} - Delivery Panel | {{ config('app.name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#3E2A1F">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $styles ?? '' }}
    @stack('styles')
</head>
<body class="font-sans antialiased bg-neutral-100 layout-admin" x-data="{ sidebarOpen: false }">
    @php
        $deliveryUser = auth('delivery')->user();
        $deliveryPartner = $deliveryUser?->deliveryPartner;
    @endphp

    <div class="flex h-screen overflow-hidden">
        <!-- Mobile sidebar backdrop -->
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/50 z-20 lg:hidden"></div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-30 w-64 bg-neutral-900 text-white transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-0">

            <!-- Logo -->
            <div class="flex items-center justify-center h-16 px-6 border-b border-neutral-800">
                <a href="{{ route('delivery.dashboard') }}">
                    <x-application-logo class="h-9 w-auto brightness-0 invert" />
                </a>
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-1 overflow-y-auto h-[calc(100vh-4rem)] scrollbar-dark text-[13px]">
                <!-- Dashboard -->
                <a href="{{ route('delivery.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-[5px] {{ request()->routeIs('delivery.dashboard') && !request()->has('tab') ? 'bg-primary-500 text-white' : 'text-neutral-300 hover:bg-neutral-800' }}">
                    <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    Dashboard
                </a>

                <!-- Deliveries Section -->
                <div class="pt-4">
                    <p class="px-3 text-xs font-semibold text-neutral-600 uppercase tracking-wider mb-2">Deliveries</p>
                    <a href="{{ route('delivery.dashboard', ['tab' => 'active']) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-[5px] {{ request()->get('tab') === 'active' ? 'bg-primary-500 text-white' : 'text-neutral-300 hover:bg-neutral-800' }}">
                        <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                        Active Orders
                    </a>
                    <a href="{{ route('delivery.dashboard', ['tab' => 'delivered']) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-[5px] {{ request()->get('tab') === 'delivered' ? 'bg-primary-500 text-white' : 'text-neutral-300 hover:bg-neutral-800' }}">
                        <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Delivered
                    </a>
                    <a href="{{ route('delivery.dashboard', ['tab' => 'all']) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-[5px] {{ request()->get('tab') === 'all' ? 'bg-primary-500 text-white' : 'text-neutral-300 hover:bg-neutral-800' }}">
                        <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        All Orders
                    </a>
                </div>

                <!-- Returns Section -->
                <div class="pt-4">
                    <p class="px-3 text-xs font-semibold text-neutral-600 uppercase tracking-wider mb-2">Returns</p>
                    <a href="{{ route('delivery.returns.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-[5px] {{ request()->routeIs('delivery.returns.*') ? 'bg-primary-500 text-white' : 'text-neutral-300 hover:bg-neutral-800' }}">
                        <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        Return Pickups
                    </a>
                </div>

                <!-- Account Section -->
                <div class="pt-4">
                    <p class="px-3 text-xs font-semibold text-neutral-600 uppercase tracking-wider mb-2">Account</p>
                    <a href="{{ route('delivery.documents') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-[5px] {{ request()->routeIs('delivery.documents') ? 'bg-primary-500 text-white' : 'text-neutral-300 hover:bg-neutral-800' }}">
                        <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        My Documents
                        @if(!$deliveryPartner?->hasDocuments() || $deliveryPartner?->verification_status === 'rejected')
                            <span class="ml-auto w-2 h-2 rounded-full bg-error-500"></span>
                        @elseif($deliveryPartner?->verification_status === 'pending')
                            <span class="ml-auto w-2 h-2 rounded-full bg-warning-500"></span>
                        @endif
                    </a>
                    <form action="{{ route('delivery.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-[5px] text-neutral-300 hover:bg-neutral-800 text-left">
                            <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        <!-- Content area -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Header -->
            <header class="h-16 bg-white border-b border-neutral-200 flex items-center justify-between px-6">
                <!-- Left side -->
                <div class="flex items-center gap-4">
                    <!-- Mobile menu toggle -->
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 -ml-2 text-neutral-600 hover:text-neutral-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Page title -->
                    <h1 class="text-lg font-semibold text-neutral-900">{{ $title ?? 'Dashboard' }}</h1>
                </div>

                <!-- Right side -->
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-sm font-medium text-neutral-900">{{ $deliveryUser?->full_name }}</p>
                            <p class="text-xs text-neutral-600">{{ $deliveryPartner?->partner_id }}</p>
                        </div>
                        <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-primary-600">
                                {{ substr($deliveryUser?->full_name ?? 'D', 0, 1) }}
                            </span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main content -->
            <main class="flex-1 overflow-y-auto p-6">
                @isset($header)
                    <div class="mb-6">{{ $header }}</div>
                @endisset

                {{ $slot }}
            </main>
        </div>
    </div>

    {{ $scripts ?? '' }}
    @stack('scripts')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 4000,
            extendedTimeOut: 2000,
            showEasing: 'swing',
            hideEasing: 'linear',
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut',
        };

        @if(session('success'))
            toastr.success(@json(session('success')));
        @endif

        @if(session('error'))
            toastr.error(@json(session('error')));
        @endif

        @if(session('warning'))
            toastr.warning(@json(session('warning')));
        @endif

        @if(session('info'))
            toastr.info(@json(session('info')));
        @endif
    </script>
</body>
</html>
