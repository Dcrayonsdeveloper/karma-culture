{{--
    The admin shell's one notification poller.

    Everything the bell shows used to be computed in a @php block in
    admin.partials.header, so a notification that arrived after the page was
    drawn stayed invisible until the admin loaded another page. This asks
    admin.notifications.updates what has arrived since it last looked, every ten
    seconds, and patches the three places the answer shows: the bell's count, the
    bell's list, and the notifications page's list. Nothing here reloads the
    page, so open forms, filters, modals, scroll position and unsaved work are
    left exactly as they were.

    It is included once, by components.layouts.admin, so there is one timer for
    the admin session however many pages the admin walks through - and it sits
    after the toastr <script> in that layout, which is what guarantees toastr is
    defined by the time this runs.

    data-since is the server's clock, not the browser's. A machine whose clock is
    minutes out of step would otherwise ask for a window that has already passed.
    It is set a few seconds BEHIND this moment on purpose: the bell's query ran
    at the top of the page and this renders at the bottom, so a notification
    committed in between is drawn by neither and would fall in front of a cursor
    set to now - never reaching this page at all, and only turning up on the
    next load. The wider window costs nothing, because the poller has already
    read the uuid of every row this page did draw and will not announce one
    twice.
--}}
@if(auth('admin')->check())
    <div id="admin-notification-poller"
         hidden
         data-endpoint="{{ route('admin.notifications.updates') }}"
         data-all-url="{{ route('admin.notifications', ['filter' => 'unread']) }}"
         data-admin-path="{{ parse_url(url('/admin'), PHP_URL_PATH) ?: '/admin' }}"
         data-admin-id="{{ auth('admin')->id() }}"
         data-since="{{ now()->subSeconds(10)->toIso8601String() }}"></div>

    <style>
        /* The toast is the whole click target - big enough to hit on a phone -
           so it has to look like one. #toast-container's position, width and
           mobile behaviour are already set in components.layouts.admin; this
           only adds what a notification toast needs on top. */
        #toast-container > div.admin-notification-toast { cursor: pointer; }
        .admin-notification-toast-action {
            display: block;
            margin-top: 6px;
            font-weight: 700;
            text-decoration: underline;
        }
        .admin-notification-toast-meta {
            display: block;
            margin-top: 4px;
            font-size: 11px;
            opacity: .85;
        }
    </style>

    <script>
    (function () {
        'use strict';

        var root = document.getElementById('admin-notification-poller');
        if (!root) {
            return;
        }

        // One loop per document, whatever else on the page decides to run this
        // file. Route changes are full page loads here, so "one timer" means one
        // per document, re-created on each load - not one that survives them.
        if (window.__kkAdminNotificationPoller) {
            return;
        }
        window.__kkAdminNotificationPoller = true;

        var ENDPOINT = root.dataset.endpoint;
        var ALL_URL = root.dataset.allUrl;
        // Taken off the rendered page rather than assumed to be "/admin": the
        // app can be served from a sub-directory, and the browser's origin need
        // not match APP_URL's behind the proxy.
        var ADMIN_PATH = root.dataset.adminPath || '/admin';

        var POLL_MS = 10000;
        // A request still running after this is abandoned, so a stalled
        // connection cannot hold the single in-flight slot forever.
        var REQUEST_TIMEOUT_MS = 20000;
        // Roughly a minute of silence before the admin is told anything. Below
        // this, a blip is simply retried on the next tick and never mentioned.
        var FAILURES_BEFORE_WARNING = 6;
        // Once it is clear the backend is down, stop asking every ten seconds.
        var BACKOFF_MS = 30000;
        // /admin/notifications?per_page=100 seeds a hundred-odd uuids off the
        // DOM before a single poll has run, so a couple of hundred leaves less
        // headroom than it looks like.
        var SEEN_LIMIT = 500;
        var BELL_ROWS = 5;
        // More than this at once and the screen gets one toast plus a summary
        // rather than a column of them.
        var TOAST_BURST = 3;

        // Keyed by admin. sessionStorage belongs to the tab, not to the login,
        // so one admin signing out and another signing in at the same desk would
        // otherwise hand the second one the first one's seen-set.
        var STORAGE_SCOPE = '.' + (root.dataset.adminId || '0');
        var SEEN_KEY = 'kk.admin.notifications.seen' + STORAGE_SCOPE;

        // Which icon in the templates stands for which notification type. The
        // bell's template carries three of these and the notifications page's
        // carries all of them; either way, a type with no icon of its own - or
        // one this map has not caught up with - falls back to the plain bell,
        // which is what the switch in each of those views does too.
        //
        // Blade compiles directives inside a script element as readily as
        // anywhere else, so an at-sign followed by a directive name must not
        // appear in this file outside a Blade comment - a JavaScript comment is
        // no protection, and the compile error it causes reads as a PHP syntax
        // error in a view with no PHP in it.
        var ICON_FOR_TYPE = {
            new_order: 'new_order',
            new_return_request: 'new_return_request',
            new_enquiry: 'new_enquiry',
            new_ticket: 'new_ticket',
            ticket_customer_reply: 'new_ticket',
            new_review: 'new_review',
            new_newsletter_subscriber: 'new_newsletter_subscriber'
        };

        var timerId = null;
        var inFlight = null;
        var stopped = false;
        var failures = 0;
        var lastSuccessAt = 0;
        var warningToast = null;

        // ------------------------------------------------------------------
        // Storage. sessionStorage is per tab and survives the full page loads
        // this admin navigates by, which is exactly the scope the seen-set
        // needs: without it every click would forget what had already been
        // announced and the next poll would announce it again. It throws
        // outright in some privacy modes, so every access is guarded and the
        // in-memory copy is the real state.
        // ------------------------------------------------------------------
        function readStored(key) {
            try {
                return window.sessionStorage.getItem(key);
            } catch (e) {
                return null;
            }
        }

        function writeStored(key, value) {
            try {
                window.sessionStorage.setItem(key, value);
            } catch (e) {
                /* Full, disabled, or private mode - the loop carries on. */
            }
        }

        var seen = Object.create(null);
        var seenOrder = [];

        function remember(uuid) {
            if (!uuid || seen[uuid]) {
                return;
            }
            seen[uuid] = true;
            seenOrder.push(uuid);
            while (seenOrder.length > SEEN_LIMIT) {
                delete seen[seenOrder.shift()];
            }
        }

        function persistSeen() {
            writeStored(SEEN_KEY, JSON.stringify(seenOrder));
        }

        (function seedSeen() {
            var stored = readStored(SEEN_KEY);
            if (stored) {
                try {
                    var list = JSON.parse(stored);
                    if (Object.prototype.toString.call(list) === '[object Array]') {
                        list.forEach(remember);
                    }
                } catch (e) {
                    /* Corrupt entry; the DOM below is enough of a baseline. */
                }
            }

            // The baseline. Everything this page has already drawn - the bell's
            // rows, and the notifications page's rows - is by definition not
            // new, so a first load can never announce the backlog, and neither
            // can a fresh tab, where the stored set starts empty.
            var drawn = document.querySelectorAll('[data-notification-uuid]');
            for (var i = 0; i < drawn.length; i++) {
                remember(drawn[i].getAttribute('data-notification-uuid'));
            }
            persistSeen();
        }());

        // Every page load re-baselines to the server's clock, and the cursor is
        // never carried across one in storage.
        //
        // Carrying it looked like continuity and was the opposite. The cursor
        // only advances on a successful poll and a hidden tab does not poll, so
        // a tab left in the background overnight keeps a cursor from last night
        // - and the next click, which redraws the bell with the current state,
        // would then ask for everything since then and announce a night's
        // notifications the admin is already looking at. The page itself is the
        // honest baseline: it has just shown them where things stand.
        //
        // Nothing is lost in the other direction. A notification arriving during
        // the page load is inside data-since's ten-second lookback, and the
        // seen-set - which IS carried across, and is what stops a second
        // announcement - covers everything this page drew.
        var since = root.dataset.since;

        // ------------------------------------------------------------------
        // Rendering helpers
        // ------------------------------------------------------------------

        // toastr writes its message as HTML, and a notification's title and body
        // carry text a customer typed - an enquiry subject, a ticket subject, a
        // shopper's name. Everything interpolated below goes through here first.
        function esc(value) {
            var box = document.createElement('div');
            box.textContent = value === null || value === undefined ? '' : String(value);
            return box.innerHTML;
        }

        // The destination comes from the server, built by route() from the
        // notification's own data - never from matching on its wording. This is
        // the belt to that braces: same origin, and inside the admin panel, so
        // a url that ever stopped being either cannot become a click-through to
        // somewhere else.
        function safeUrl(url) {
            if (!url) {
                return null;
            }

            try {
                var parsed = new URL(url, window.location.origin);

                if (parsed.origin !== window.location.origin) {
                    return null;
                }

                return parsed.pathname.indexOf(ADMIN_PATH) === 0 ? parsed.href : null;
            } catch (e) {
                return null;
            }
        }

        // Whether the app answered, or something in front of it did. Laravel
        // sends application/json for both the 401 and the 403 this endpoint can
        // produce; a proxy's own error page does not.
        function isJson(response) {
            var type = response.headers.get('content-type') || '';
            return type.indexOf('json') !== -1;
        }

        function findByUuid(container, uuid) {
            var rows = container.querySelectorAll('[data-notification-uuid]');
            for (var i = 0; i < rows.length; i++) {
                if (rows[i].getAttribute('data-notification-uuid') === uuid) {
                    return rows[i];
                }
            }
            return null;
        }

        function fillRow(row, notification) {
            var url = safeUrl(notification.url);
            if (url) {
                row.setAttribute('href', url);
            }
            row.setAttribute('data-notification-uuid', notification.uuid);

            // textContent, never innerHTML: this is the other half of the same
            // guard esc() gives the toast.
            var slots = {
                title: notification.title || '',
                content: notification.content || '',
                time: notification.created_at_for_humans || '',
                // Only the notifications page's template has these two.
                channel: notification.channel || '',
                type: notification.type || ''
            };
            Object.keys(slots).forEach(function (name) {
                var slot = row.querySelector('[data-slot="' + name + '"]');
                if (slot) {
                    slot.textContent = slots[name];
                }
            });

            // hasOwnProperty, not a bare lookup: a type of "constructor" or
            // "toString" would otherwise find something off Object.prototype and
            // build a selector out of it.
            var wanted = Object.prototype.hasOwnProperty.call(ICON_FOR_TYPE, notification.type)
                ? ICON_FOR_TYPE[notification.type]
                : 'default';
            var icon = row.querySelector('[data-icon="' + wanted + '"]')
                || row.querySelector('[data-icon="default"]');
            if (icon) {
                icon.hidden = false;
                var wrap = row.querySelector('[data-slot="icon-wrap"]');
                // The circle's tint travels with the icon in the Blade template
                // so the colours stay in the views with the rest of the palette.
                if (wrap && icon.dataset.bg) {
                    wrap.style.background = icon.dataset.bg;
                }
            }

            return row;
        }

        function cloneRow(templateSelector, notification) {
            var template = document.querySelector(templateSelector);
            if (!template || !template.content || !template.content.firstElementChild) {
                return null;
            }
            return fillRow(template.content.firstElementChild.cloneNode(true), notification);
        }

        // ------------------------------------------------------------------
        // The bell
        // ------------------------------------------------------------------
        function applyUnreadCount(count) {
            if (typeof count !== 'number' || count < 0 || count !== Math.floor(count)) {
                return;
            }

            var badge = document.querySelector('[data-admin-bell-badge]');
            if (badge) {
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.style.display = count > 0 ? '' : 'none';
            }

            var label = document.querySelector('[data-admin-bell-unread-label]');
            if (label) {
                label.textContent = count + ' new';
                label.style.display = count > 0 ? '' : 'none';
            }

            // The badge is aria-hidden - a bare number announces as a stray
            // digit - so the count has to reach a screen reader through the
            // button's own name, the way the server-rendered one does.
            var button = document.querySelector('[data-admin-bell-button]');
            if (button) {
                button.setAttribute(
                    'aria-label',
                    count > 0 ? 'Notifications, ' + count + ' unread' : 'Notifications, none unread'
                );
            }

            var tabCount = document.querySelector('[data-admin-unread-count]');
            if (tabCount) {
                tabCount.textContent = '(' + count + ')';
            }

            // "Mark all as read" only makes sense when something is unread, and
            // the count can cross that line without a page load in either
            // direction - a notification arriving here, or the last unread one
            // being opened in another tab.
            var readAll = document.querySelector('[data-admin-read-all]');
            if (readAll) {
                readAll.style.display = count > 0 ? '' : 'none';
            }
        }

        function insertIntoBell(notification) {
            var list = document.querySelector('[data-admin-bell-list]');
            if (!list || findByUuid(list, notification.uuid)) {
                return;
            }

            var row = cloneRow('[data-admin-bell-row-template]', notification);
            if (!row) {
                return;
            }

            var empty = list.querySelector('[data-admin-bell-empty]');
            if (empty) {
                empty.parentNode.removeChild(empty);
            }

            list.insertBefore(row, list.firstChild);

            // The bell shows the latest five. Trimming here keeps it showing
            // what a page load would show, rather than growing all day.
            var rows = list.querySelectorAll('[data-notification-uuid]');
            for (var i = BELL_ROWS; i < rows.length; i++) {
                rows[i].parentNode.removeChild(rows[i]);
            }
        }

        // ------------------------------------------------------------------
        // The notifications page
        // ------------------------------------------------------------------
        function insertIntoList(notification) {
            var list = document.querySelector('[data-admin-list]');
            if (!list) {
                return;
            }

            // Only the first page grows. Paging is a window onto rows ordered
            // newest-first, so a new row belongs at the top of page one and
            // nowhere else; pushing one into page three would show the admin a
            // row that is not on page three. The count still updates everywhere.
            if ((list.dataset.page || '1') !== '1') {
                return;
            }

            if (findByUuid(list, notification.uuid)) {
                return;
            }

            var row = cloneRow('[data-admin-list-row-template]', notification);
            if (!row) {
                return;
            }

            var empty = list.querySelector('[data-admin-list-empty]');
            if (empty) {
                empty.parentNode.removeChild(empty);
            }

            // Inserting above whatever the admin is reading would otherwise
            // scroll it out from under them.
            var scroller = document.querySelector('.layout-admin main');
            var before = scroller ? scroller.scrollTop : 0;
            var height = scroller ? scroller.scrollHeight : 0;

            list.insertBefore(row, list.firstChild);

            if (scroller && before > 0) {
                scroller.scrollTop = before + (scroller.scrollHeight - height);
            }

            // Keep the page the size the paginator says it is, so "Showing 1-10
            // of 41" stays true and does not drift by one on every arrival.
            var perPage = parseInt(list.dataset.perPage || '0', 10);
            var rows = list.querySelectorAll('[data-notification-uuid]');
            if (perPage > 0 && rows.length > perPage) {
                for (var i = perPage; i < rows.length; i++) {
                    rows[i].parentNode.removeChild(rows[i]);
                }
            }

            var total = document.querySelector('[data-admin-list-total]');
            if (total) {
                var current = parseInt(total.textContent, 10);
                if (!isNaN(current)) {
                    total.textContent = String(current + 1);
                }
            }

            var shown = list.querySelectorAll('[data-notification-uuid]').length;

            var last = document.querySelector('[data-admin-list-last-item]');
            if (last) {
                last.textContent = String(shown);
            }

            // On a page that had nothing on it, the summary bar is rendered
            // hidden and reading "0-0 of 0"; the first arrival is what brings it
            // into use.
            var first = document.querySelector('[data-admin-list-first-item]');
            if (first && first.textContent.trim() === '0') {
                first.textContent = '1';
            }

            var summary = document.querySelector('[data-admin-list-summary]');
            if (summary && shown > 0) {
                summary.style.display = '';
            }
        }

        // ------------------------------------------------------------------
        // Toasts
        // ------------------------------------------------------------------
        // toastr comes off a CDN in components.layouts.admin. If that request is
        // the one thing the network drops, every toast call below would throw
        // and take the whole loop down with it - so the bell and the lists would
        // stop updating over a missing stylesheet's script. Guarded, the panel
        // degrades to a silently self-updating bell, which is still most of the
        // point. Every admin blade that reaches for toastr guards it this way.
        function raise(kind, body, title, options) {
            if (!window.toastr || typeof window.toastr[kind] !== 'function') {
                return null;
            }

            try {
                return window.toastr[kind](body, title, options);
            } catch (e) {
                return null;
            }
        }

        function toastOptions(url) {
            var target = safeUrl(url);

            return {
                timeOut: 8000,
                extendedTimeOut: 4000,
                closeButton: true,
                progressBar: true,
                toastClass: 'toast admin-notification-toast',
                onclick: target
                    ? function () { window.location.href = target; }
                    : undefined
            };
        }

        // Title, message, when it happened, and the way in. The entity itself is
        // already named inside `content` - "Order #KK-000412 placed for ..." -
        // which is why the notification's `data` blob never crosses the wire:
        // it is the widest customer-supplied surface in the row and the toast
        // has no use for it.
        function toastFor(notification) {
            var body = esc(notification.content || '');

            if (notification.created_at_for_humans) {
                body += '<span class="admin-notification-toast-meta">'
                    + esc(notification.created_at_for_humans)
                    + '</span>';
            }

            body += '<span class="admin-notification-toast-action">View</span>';

            raise('info', body, esc(notification.title || 'New notification'), toastOptions(notification.url));
        }

        function announce(fresh, truncated) {
            if (fresh.length <= TOAST_BURST) {
                // Oldest first. toastr stacks newest-on-top, so raising them in
                // the order they happened leaves the most recent at the top of
                // the column, which is where the eye goes.
                for (var i = 0; i < fresh.length; i++) {
                    toastFor(fresh[i]);
                }
                return;
            }

            // A burst gets the newest one in full plus a count, rather than a
            // column of toasts down the whole side of the screen. The count
            // wears a "+" when the answer was capped: more arrived than one
            // poll carries, they are all on the notifications page, and naming
            // a precise number here would be naming the wrong one.
            toastFor(fresh[fresh.length - 1]);
            raise(
                'info',
                '<span class="admin-notification-toast-action">View all</span>',
                esc(fresh.length + (truncated ? '+' : '') + ' new notifications'),
                toastOptions(ALL_URL)
            );
        }

        // ------------------------------------------------------------------
        // The loop
        // ------------------------------------------------------------------
        function schedule() {
            if (stopped) {
                return;
            }
            window.clearTimeout(timerId);
            // Chained timeouts rather than setInterval: a tick can never be
            // queued behind the previous one, so there is exactly one loop no
            // matter how long a request took or how hard the tab was throttled.
            timerId = window.setTimeout(poll, failures >= FAILURES_BEFORE_WARNING ? BACKOFF_MS : POLL_MS);
        }

        function clearWarning() {
            if (warningToast) {
                if (window.toastr && typeof window.toastr.clear === 'function') {
                    window.toastr.clear(warningToast);
                }
                warningToast = null;
            }
        }

        function succeed(payload) {
            failures = 0;
            lastSuccessAt = Date.now();
            clearWarning();

            if (payload.next_since) {
                since = payload.next_since;
            }

            applyUnreadCount(payload.unread_count);

            var incoming = payload.notifications || [];
            var fresh = [];
            for (var i = 0; i < incoming.length; i++) {
                var notification = incoming[i];
                if (notification && notification.uuid && !seen[notification.uuid]) {
                    fresh.push(notification);
                }
            }

            if (!fresh.length) {
                return;
            }

            // Marked seen before anything is drawn, so a re-render, a retry or
            // an overlapping answer cannot raise the same toast twice.
            fresh.forEach(function (notification) { remember(notification.uuid); });
            persistSeen();

            fresh.forEach(insertIntoBell);
            fresh.forEach(insertIntoList);
            announce(fresh, payload.truncated === true);
        }

        function fail() {
            failures += 1;

            // Once, on the way past the threshold - never again while it lasts.
            // A backend down for an hour is one line on screen, not 360.
            if (failures === FAILURES_BEFORE_WARNING && !warningToast) {
                warningToast = raise(
                    'warning',
                    'New notifications will appear as soon as the connection is back.',
                    'Not checking for notifications',
                    { timeOut: 0, extendedTimeOut: 0, closeButton: true, tapToDismiss: false }
                );
            }
        }

        function stop() {
            stopped = true;
            window.clearTimeout(timerId);
            if (inFlight) {
                inFlight.abort();
                inFlight = null;
            }
            clearWarning();
        }

        function poll() {
            if (stopped) {
                return;
            }

            // A hidden tab is not worth a request: a toast raised behind another
            // window would time out unseen and be marked as announced. The
            // visibility handler below catches up the moment it comes back.
            if (document.hidden) {
                schedule();
                return;
            }

            // At most one request outstanding. A slow answer delays the next
            // question rather than stacking another on top of it. The request
            // already in the air will schedule the next tick when it settles;
            // scheduling here as well only replaces that timer with an
            // identical one, and means no path out of this function can leave
            // the loop without a next tick.
            if (inFlight) {
                schedule();
                return;
            }

            var controller = new AbortController();
            inFlight = controller;
            var timedOut = false;
            var abortTimer = window.setTimeout(function () {
                timedOut = true;
                controller.abort();
            }, REQUEST_TIMEOUT_MS);

            var url = ENDPOINT
                + (ENDPOINT.indexOf('?') === -1 ? '?' : '&')
                + 'since=' + encodeURIComponent(since);

            window.fetch(url, {
                method: 'GET',
                // The admin session cookie and nothing else. There is no bearer
                // token in play here, so a storefront customer's credentials
                // cannot be attached to an admin request even by accident.
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller.signal
            }).then(function (response) {
                // The admin is no longer signed in, or no longer an admin. Stand
                // down - quietly. Announcing "session expired" here would put
                // that in front of an admin who is still working, on nothing but
                // a background request; the next thing they click takes them to
                // the login page by itself, which is the honest moment to say so.
                //
                // Only the app's own answer counts. A 403 from a proxy or a WAF
                // arrives as HTML, and treating that as authoritative would
                // silence the bell for the rest of the session over a blip - so
                // an unsigned 403 is retried like any other failure. 401 and 419
                // are unambiguous: nothing but the framework produces them here.
                if (response.status === 401
                    || response.status === 419
                    || (response.status === 403 && isJson(response))) {
                    stop();
                    return null;
                }

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            }).then(function (payload) {
                if (!payload) {
                    return;
                }

                // A fault while drawing is not a fault on the network, and must
                // not be counted as one - six rendering bugs in a row would
                // otherwise put a connectivity warning on screen and slow the
                // poll down. The cursor and the seen-set have already moved on
                // inside succeed(), so the next poll carries on regardless.
                try {
                    succeed(payload);
                } catch (e) {
                    /* Nothing to retry: the answer was received and accepted. */
                }
            }).catch(function (error) {
                // A request we abandoned ourselves - signing out, or the page
                // going into the back-forward cache - is not the network
                // failing. Counting it would walk a paused-and-resumed tab
                // towards a connectivity warning it has no reason to show.
                if (error && error.name === 'AbortError' && !timedOut) {
                    return;
                }

                // Offline, a read timeout, a 502 from the proxy. None of these
                // say anything about the admin's session.
                fail();
            }).finally(function () {
                // finally, not a trailing then: if fail() itself ever threw, a
                // then() here would be skipped and the loop would stop for good
                // - one bad response and the bell is dead until a page load.
                window.clearTimeout(abortTimer);
                if (inFlight === controller) {
                    inFlight = null;
                }
                schedule();
            });
        }

        document.addEventListener('visibilitychange', function () {
            if (document.hidden || stopped) {
                return;
            }

            // Background timers are throttled hard, so coming back can mean the
            // last answer is minutes old. Ask again straight away rather than
            // waiting out a tick that was scheduled in another era.
            if (Date.now() - lastSuccessAt >= POLL_MS) {
                window.clearTimeout(timerId);
                poll();
            }
        });

        // Signing out stops the loop there and then, rather than leaving one
        // more authenticated request in the air behind the redirect.
        //
        // Delegated from the document and matched on the path rather than bound
        // to one element by its absolute URL: the logout form lives inside the
        // header's user menu today, and a listener that silently finds nothing
        // if that form is ever moved, duplicated or rendered later is a quiet
        // way to lose this.
        var LOGOUT_PATH = '{{ parse_url(route('admin.logout'), PHP_URL_PATH) }}';
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form || form.tagName !== 'FORM') {
                return;
            }

            try {
                if (new URL(form.action, window.location.origin).pathname === LOGOUT_PATH) {
                    stop();
                }
            } catch (e) {
                /* A form with an unparseable action is not the logout form. */
            }
        }, true);

        // Leaving the page: drop the timer and abandon anything in the air, so
        // no authenticated request is left chasing a document that has gone.
        // `persisted` says the browser is keeping this page for the back button
        // rather than discarding it, and in that case the loop has to be able to
        // come back with it - stopping for good here is what would leave an
        // admin who pressed Back on a bell that never updated again.
        window.addEventListener('pagehide', function (event) {
            window.clearTimeout(timerId);
            if (inFlight) {
                inFlight.abort();
                inFlight = null;
            }
            if (!event.persisted) {
                stopped = true;
            }
        });

        window.addEventListener('pageshow', function (event) {
            if (!event.persisted || stopped) {
                return;
            }

            // Restored from the back-forward cache, where it may have sat for
            // hours. Ask immediately rather than waiting out a tick.
            window.clearTimeout(timerId);
            lastSuccessAt = 0;
            poll();
        });

        lastSuccessAt = Date.now();
        schedule();
    }());
    </script>
@endif
