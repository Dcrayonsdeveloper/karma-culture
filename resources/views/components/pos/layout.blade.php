<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>{{ config('app.name') }} - POS</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset_v('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset_v('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset_v('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset_v('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset_v('site.webmanifest') }}">
    <meta name="theme-color" content="#6F9CA2">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700|jetbrains-mono:400,500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --pos-bg: #F5F5F7;
            --pos-sidebar: #1E293B;
            --pos-card: #FFFFFF;
            --pos-primary: #6F9CA2;
            --pos-primary-dark: #5B878D;
            --pos-success: #15803D;
            --pos-warning: #B45309;
            --pos-danger: #B91C1C;
            --pos-info: #1D4ED8;
            --pos-accent: #C2680A;
            --pos-text: #222222;
            --pos-text-muted: #374151;
            --pos-border: #E2E8F0;
            --pos-success-bg: #22C55E;
            --pos-danger-bg: #EF4444;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--pos-bg);
            color: var(--pos-text);
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100vh;
            -webkit-user-select: none;
            user-select: none;
        }

        .pos-mono { font-family: 'JetBrains Mono', monospace; }

        /* Smooth scrolling with momentum */
        .pos-scroll {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #CBD5E1 transparent;
        }
        .pos-scroll::-webkit-scrollbar { width: 4px; }
        .pos-scroll::-webkit-scrollbar-track { background: transparent; }
        .pos-scroll::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }

        /* Animations */
        @keyframes pos-shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-8px); }
            40%, 80% { transform: translateX(8px); }
        }
        @keyframes pos-item-added {
            0% { transform: translateX(-20px); opacity: 0; background-color: #DCFCE7; }
            30% { transform: translateX(0); opacity: 1; background-color: #DCFCE7; }
            100% { background-color: transparent; }
        }
        @keyframes pos-success-bounce {
            0% { transform: scale(0); }
            60% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        @keyframes pos-fade-in {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .pos-shake { animation: pos-shake 0.4s ease-in-out; }
        .pos-item-added { animation: pos-item-added 0.6s ease-out; }
        .pos-fade-in { animation: pos-fade-in 0.2s ease-out; }

        /* Touch targets minimum 44px */
        .pos-btn {
            min-height: 44px;
            min-width: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.15s ease;
            cursor: pointer;
            border: none;
            outline: none;
        }
        .pos-btn:focus-visible { outline: 2px solid var(--pos-primary); outline-offset: 2px; }
        .pos-btn:active { transform: scale(0.97); }
        .pos-btn-primary {
            background: var(--pos-primary);
            color: white;
        }
        .pos-btn-primary:hover { background: var(--pos-primary-dark); }
        .pos-btn-success {
            background: var(--pos-success);
            color: white;
        }
        .pos-btn-danger {
            background: var(--pos-danger);
            color: white;
        }
        .pos-btn-ghost {
            background: transparent;
            color: var(--pos-text-muted);
            border: 1px solid var(--pos-border);
        }
        .pos-btn-ghost:hover { background: #F1F5F9; }

        /* Cards */
        .pos-card {
            background: var(--pos-card);
            border-radius: 12px;
            border: 1px solid var(--pos-border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        /* Stock badges */
        .pos-badge-stock {
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .pos-badge-stock.in-stock { background: #DCFCE7; color: #14532D; }
        .pos-badge-stock.low-stock { background: #FEF3C7; color: #78350F; }
        .pos-badge-stock.out-of-stock { background: #FEE2E2; color: #7F1D1D; }

        /* Numpad */
        .pos-numpad-btn {
            width: 72px;
            height: 56px;
            font-size: 22px;
            font-weight: 500;
            border-radius: 10px;
            background: white;
            border: 1px solid var(--pos-border);
            cursor: pointer;
            transition: all 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pos-numpad-btn:hover { background: #F8FAFC; }
        .pos-numpad-btn:focus-visible { outline: 2px solid var(--pos-primary); outline-offset: 2px; }
        .pos-numpad-btn:active { background: #E2E8F0; transform: scale(0.96); }

        /* Full-screen POS container */
        .pos-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* No-select for POS interface */
        .pos-container input, .pos-container textarea {
            -webkit-user-select: text;
            user-select: text;
        }

        /* ====== AAA Contrast Fixes ====== */
        /* Muted text: #374151 on white = 7.3:1 (AAA) ✓ */
        /* Primary text: #222222 on white = 14.4:1 (AAA) ✓ */
        /* Focus indicators: 2px solid ring, visible on all backgrounds */

        /* ====== MOBILE RESPONSIVE ====== */
        @media (max-width: 768px) {
            body {
                overflow: auto;
                height: auto;
                min-height: 100vh;
                min-height: 100dvh;
            }
            .pos-container {
                height: auto;
                min-height: 100vh;
                min-height: 100dvh;
            }
            /* Numpad keys scale down */
            .pos-numpad-btn {
                width: 64px;
                height: 52px;
                font-size: 20px;
            }
            /* Mobile POS billing layout */
            .pos-main-flex {
                flex-direction: column;
                position: relative;
            }
            .pos-products-panel {
                width: 100% !important;
                border-right: none !important;
                flex: 1;
            }
            .pos-cart-panel {
                width: 100% !important;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 40;
                transform: translateY(100%);
                transition: transform 0.3s ease;
            }
            .pos-cart-panel.pos-cart-open {
                transform: translateY(0);
            }
            /* Mobile top bar */
            .pos-topbar-center { display: none; }
            .pos-topbar-center.pos-search-active { display: block; position: absolute; left: 8px; right: 8px; top: 6px; z-index: 10; }
            .pos-topbar-staff { display: none; }
            /* Mobile cart FAB */
            .pos-cart-fab {
                display: flex;
                position: fixed;
                bottom: 16px;
                right: 16px;
                z-index: 35;
                width: 56px;
                height: 56px;
                border-radius: 50%;
                background: var(--pos-primary);
                color: white;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 16px rgba(0,0,0,0.2);
                border: none;
                cursor: pointer;
            }
            .pos-cart-fab:active { transform: scale(0.93); }
        }
        @media (min-width: 769px) {
            .pos-cart-fab { display: none; }
            .pos-mobile-search-btn { display: none; }
            .pos-mobile-cart-close { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body>
    {{ $slot }}

    @stack('scripts')

    {{-- CSRF Token Auto-Refresh (POS sessions are long-lived) --}}
    <script>
        // Refresh CSRF token every 30 minutes to prevent 419 errors
        setInterval(async () => {
            try {
                const res = await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
                if (res.ok) {
                    // Also try refreshing from a simple endpoint
                    const tokenRes = await fetch('/csrf-token', { credentials: 'same-origin' });
                    if (tokenRes.ok) {
                        const data = await tokenRes.json();
                        if (data.token) {
                            document.querySelector('meta[name="csrf-token"]').content = data.token;
                            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = data.token;
                        }
                    }
                }
            } catch (e) {
                console.warn('[POS] CSRF refresh failed:', e);
            }
        }, 30 * 60 * 1000); // 30 minutes

        // Axios interceptor: auto-retry on 419 (CSRF mismatch)
        window.axios.interceptors.response.use(
            response => response,
            async error => {
                const originalRequest = error.config;
                if (error.response?.status === 419 && !originalRequest._csrfRetried) {
                    originalRequest._csrfRetried = true;
                    try {
                        const tokenRes = await fetch('/csrf-token', { credentials: 'same-origin' });
                        if (tokenRes.ok) {
                            const data = await tokenRes.json();
                            if (data.token) {
                                document.querySelector('meta[name="csrf-token"]').content = data.token;
                                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = data.token;
                                originalRequest.headers['X-CSRF-TOKEN'] = data.token;
                                return window.axios(originalRequest);
                            }
                        }
                    } catch (e) {
                        console.error('[POS] CSRF retry failed:', e);
                    }
                }
                return Promise.reject(error);
            }
        );
    </script>

    {{-- Service Worker for offline support --}}
    <script>
        if ('serviceWorker' in navigator) {
            // updateViaCache:'none' keeps the browser from serving the worker
            // script itself out of the HTTP cache. Without it a stale pos-sw.js
            // can keep answering from `pos-v1` long after a deploy, and the POS
            // shows yesterday's build with no way for the cashier to clear it.
            navigator.serviceWorker.register('/pos-sw.js', { scope: '/pos', updateViaCache: 'none' })
                .then(reg => {
                    console.log('[POS] Service Worker registered:', reg.scope);
                    // Pick up a new worker on every page load rather than
                    // waiting for the browser's own 24h update check.
                    reg.update();
                })
                .catch(err => console.warn('[POS] SW registration failed:', err));

            // Listen for offline queue messages from SW
            navigator.serviceWorker.addEventListener('message', (event) => {
                if (event.data?.type === 'QUEUE_OFFLINE_ACTION') {
                    const queue = JSON.parse(localStorage.getItem('pos_offline_queue') || '[]');
                    queue.push(event.data.data);
                    localStorage.setItem('pos_offline_queue', JSON.stringify(queue));
                }
                if (event.data?.type === 'SYNC_COMPLETE') {
                    localStorage.removeItem('pos_offline_queue');
                    console.log('[POS] Offline queue synced:', event.data.results);
                }
            });

            // Sync when back online
            window.addEventListener('online', () => {
                const queue = JSON.parse(localStorage.getItem('pos_offline_queue') || '[]');
                if (queue.length > 0 && navigator.serviceWorker.controller) {
                    navigator.serviceWorker.controller.postMessage({ type: 'SYNC_OFFLINE_QUEUE', queue });
                }
            });
        }

        // Offline/online indicator
        window.addEventListener('offline', () => {
            document.body.setAttribute('data-offline', 'true');
            const banner = document.createElement('div');
            banner.id = 'pos-offline-banner';
            banner.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:9999;background:#EF4444;color:white;text-align:center;padding:4px;font-size:12px;font-weight:600;';
            banner.textContent = 'You are offline - Some features may be limited';
            document.body.prepend(banner);
        });
        window.addEventListener('online', () => {
            document.body.removeAttribute('data-offline');
            document.getElementById('pos-offline-banner')?.remove();
        });
    </script>
</body>
</html>
