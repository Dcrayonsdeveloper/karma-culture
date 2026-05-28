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
    <meta name="theme-color" content="#3E2A1F">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $styles ?? '' }}
    @stack('styles')

    <style>
    /* Admin table defaults — consistent spacing across all pages */
    .layout-admin table { width: 100%; border-collapse: collapse; }
    .layout-admin table th {
        padding: 0.5rem 0.75rem;
        text-align: left;
        font-size: 12px;
        font-weight: 500;
        color: #6d7175;
        border-bottom: 1px solid #e3e3e3;
        background: #f6f6f7;
        white-space: nowrap;
    }
    .layout-admin table td {
        padding: 0.625rem 0.75rem;
        font-size: 13px;
        color: #303030;
        border-bottom: 1px solid #f1f1f1;
        vertical-align: middle;
    }
    .layout-admin table tbody tr:last-child td { border-bottom: none; }
    .layout-admin table tbody tr:hover { background: #fafafa; }

    /* Admin responsive overrides */
    @media (max-width: 768px) {
        /* Stack all inline grid layouts on mobile */
        .layout-admin main [style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
        }
        /* Tables: enforce min-width so they scroll, not squish */
        .layout-admin table { min-width: 600px; }
        .layout-admin .card { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        /* Page header: wrap title + button */
        .layout-admin .page-header { flex-wrap: wrap; }
        /* Save bars: wrap */
        .layout-admin main [style*="justify-content: space-between"] {
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        /* Tab filters: allow horizontal scroll */
        .layout-admin .card > [style*="border-bottom"][style*="display: flex"] {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            white-space: nowrap;
        }
        /* Search bars: stack vertically */
        .layout-admin .card > [style*="border-bottom"][style*="gap: 0.5rem"] {
            flex-wrap: wrap;
        }
        .layout-admin .card > [style*="border-bottom"][style*="gap: 0.5rem"] form {
            flex-wrap: wrap;
        }
    }
    </style>
</head>
<body class="font-sans antialiased layout-admin" style="background: #efeded;" x-data="{ sidebarOpen: false }">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        @include('admin.partials.sidebar')

        <!-- Content area -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Admin Header -->
            @include('admin.partials.header')

            <!-- Main content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                <div>
                    @isset($header)
                        <div class="mb-5">{{ $header }}</div>
                    @endisset

                    {{ $slot }}
                </div>
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
