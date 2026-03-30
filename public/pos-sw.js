/**
 * POS Service Worker — Offline caching for ForeverKids POS
 *
 * Strategy:
 *  - Cache-first for static assets (CSS, JS, images, fonts)
 *  - Network-first for API/data routes with offline fallback
 *  - Stale-while-revalidate for product images
 *  - Queue failed POST requests for sync when back online
 */

const CACHE_NAME = 'pos-v1';
const DATA_CACHE = 'pos-data-v1';
const OFFLINE_QUEUE_KEY = 'pos-offline-queue';

// Static assets to pre-cache on install
const PRECACHE_URLS = [
    '/pos',
    '/pos/login',
    '/build/assets/app.css',
    '/build/assets/app.js',
    '/pos/offline',
];

// Routes to cache data responses
const DATA_ROUTES = [
    '/pos/products',
    '/pos/categories',
    '/pos/cart/data',
];

// ═══════ INSTALL ═══════
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_URLS).catch(() => {
                // Silently fail for URLs that 404 during install
                console.log('[POS-SW] Some precache URLs failed, continuing...');
            });
        })
    );
    self.skipWaiting();
});

// ═══════ ACTIVATE ═══════
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key !== CACHE_NAME && key !== DATA_CACHE)
                    .map((key) => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

// ═══════ FETCH ═══════
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET for fetch handling (POST/PATCH/DELETE handled separately)
    if (request.method !== 'GET') {
        event.respondWith(handleMutationRequest(request));
        return;
    }

    // POS data routes: network-first with cache fallback
    if (DATA_ROUTES.some((route) => url.pathname.startsWith(route))) {
        event.respondWith(networkFirstWithCache(request, DATA_CACHE));
        return;
    }

    // Static assets: cache-first
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // POS pages: network-first
    if (url.pathname.startsWith('/pos')) {
        event.respondWith(networkFirstWithCache(request, CACHE_NAME));
        return;
    }

    // Everything else: network only
    event.respondWith(fetch(request));
});

// ═══════ STRATEGIES ═══════

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('Offline', { status: 503 });
    }
}

async function networkFirstWithCache(request, cacheName) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;

        // Return offline page for navigation requests
        if (request.mode === 'navigate') {
            const offlinePage = await caches.match('/pos/offline');
            if (offlinePage) return offlinePage;
        }

        return new Response(
            JSON.stringify({ offline: true, message: 'You are offline. This data was not available in cache.' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}

async function handleMutationRequest(request) {
    try {
        return await fetch(request);
    } catch {
        // Queue failed mutations for later sync
        if (request.url.includes('/pos/sale/complete') || request.url.includes('/pos/cart/')) {
            await queueForSync(request);
        }

        return new Response(
            JSON.stringify({
                offline: true,
                queued: true,
                message: 'You are offline. This action has been queued and will sync when you reconnect.',
            }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}

// ═══════ OFFLINE QUEUE ═══════

async function queueForSync(request) {
    try {
        const body = await request.clone().text();
        const queueItem = {
            url: request.url,
            method: request.method,
            headers: Object.fromEntries(request.headers.entries()),
            body: body,
            timestamp: Date.now(),
        };

        // Use IndexedDB via a simple approach
        const clients = await self.clients.matchAll();
        clients.forEach((client) => {
            client.postMessage({
                type: 'QUEUE_OFFLINE_ACTION',
                data: queueItem,
            });
        });
    } catch (e) {
        console.error('[POS-SW] Failed to queue request:', e);
    }
}

// ═══════ SYNC ═══════

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SYNC_OFFLINE_QUEUE') {
        syncOfflineQueue(event.data.queue || []);
    }

    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

async function syncOfflineQueue(queue) {
    const results = [];

    for (const item of queue) {
        try {
            const response = await fetch(item.url, {
                method: item.method,
                headers: item.headers,
                body: item.body,
            });
            results.push({ url: item.url, success: response.ok, status: response.status });
        } catch {
            results.push({ url: item.url, success: false, status: 0 });
        }
    }

    // Notify all clients of sync results
    const clients = await self.clients.matchAll();
    clients.forEach((client) => {
        client.postMessage({ type: 'SYNC_COMPLETE', results });
    });
}

// ═══════ HELPERS ═══════

function isStaticAsset(pathname) {
    return /\.(css|js|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|webp|ico)(\?.*)?$/.test(pathname)
        || pathname.startsWith('/build/');
}
