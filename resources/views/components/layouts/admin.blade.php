<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin' }} - {{ config('app.name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset_v('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset_v('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset_v('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset_v('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset_v('site.webmanifest') }}">
    <meta name="theme-color" content="#6F9CA2">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $styles ?? '' }}
    @stack('styles')

    <style>
    /* Admin table defaults - consistent spacing across all pages */
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

    /* Responsive behaviour (grid collapse, wrapping toolbars, scrolling
       tables) lives in resources/css/app.css under "Responsive: admin shell",
       so the rules are versioned with the rest of the stylesheet. */
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
    <style>
        /* Admin toast (toastr): sit below the 56px header, away from the bell/avatar, and render legibly */
        #toast-container { top: 70px !important; right: 16px !important; }
        #toast-container > div {
            position: relative;
            min-width: 300px; max-width: 380px;
            padding: 14px 42px 14px 50px !important;
            border-radius: 10px !important;
            box-shadow: 0 10px 28px rgba(0,0,0,.18) !important;
            opacity: 1 !important;
            background-position: 15px center !important;
            background-size: 22px !important;
            font-size: 13px; line-height: 1.45;
        }
        #toast-container > div .toast-close-button {
            position: absolute !important; top: 8px; right: 10px;
            font-size: 18px; font-weight: 700; line-height: 1;
            color: #fff; opacity: .85; text-shadow: none;
        }
        #toast-container > div .toast-close-button:hover { opacity: 1; }
        #toast-container .toast-progress { height: 3px !important; opacity: .35 !important; }
        @media (max-width: 640px) {
            #toast-container { top: 62px !important; right: 8px !important; left: 8px !important; }
            #toast-container > div { min-width: 0; max-width: none; }
        }
    </style>
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
