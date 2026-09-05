{{-- A reload lands at the top of the page.

     Browsers restore the previous scroll offset when a page is reloaded, so a
     shopper who refreshes half way down a listing comes back staring at the
     middle of the page instead of the top of it.

     Back and forward are deliberately left alone - restoring the offset is the
     whole point of the back button, and losing the shopper's place in a long
     listing on the way back from a product would be a worse bug than the one
     this fixes. A URL that names an anchor (/faq#returns) is asking for that
     section rather than for the top, so its jump is left alone too.

     Reloads and nothing else. An ordinary navigation is left alone even though
     it starts at the top and the correction reads like a no-op: it is not one,
     because the correcting passes run as late as the load event, and on a
     storefront that is seconds after the page became readable. Anyone who has
     started scrolling by then keeps their place - see userMoved below, which
     covers the same window on a real reload.

     This has to be an inline script in <head>: it must run before the browser
     lays the page out. The bundle is a deferred module, which runs after
     parsing - by then the offset is already restored and the correction shows
     up as a visible jump. --}}
<script>
    (function () {
        // A reload the page asks for itself is not the shopper asking to start
        // over: signing in from the header modal, or claiming an offer on the
        // cart, reloads only to pick the new state up, and the shopper must
        // come back to the row they were looking at. Those callers set this
        // flag through window.kkReload() below, and it survives exactly one
        // load. sessionStorage throws in some privacy modes, hence the guards.
        var KEEP = 'kk_keep_scroll';
        var keepScroll = false;
        try {
            keepScroll = sessionStorage.getItem(KEEP) === '1';
            if (keepScroll) sessionStorage.removeItem(KEEP);
        } catch (e) {}

        window.kkReload = function () {
            try { sessionStorage.setItem(KEEP, '1'); } catch (e) {}
            window.location.reload();
        };

        var entry = (window.performance && performance.getEntriesByType)
            ? performance.getEntriesByType('navigation')[0]
            : null;
        var navType = entry ? entry.type : null;

        // performance.navigation is long deprecated but is the only reading
        // available on engines without the navigation timing entry.
        if (!navType && window.performance && performance.navigation) {
            var legacy = performance.navigation.type;   // 1 RELOAD, 2 BACK_FORWARD
            navType = legacy === 1 ? 'reload' : (legacy === 2 ? 'back_forward' : 'navigate');
        }

        // Reloads only. This used to fire on anything that was not a back or a
        // forward, on the theory that an ordinary navigation starts at the top
        // anyway and the correction would be a no-op. It is not a no-op: the
        // passes below run as late as the load event, so a shopper who followed
        // a link and started scrolling while the images were still arriving got
        // yanked back to the top.
        if (keepScroll || navType !== 'reload') return;

        var supported = 'scrollRestoration' in history;
        if (supported) history.scrollRestoration = 'manual';

        // 'manual' is stored on this history entry, not on the tab, so leaving
        // it set would kill the restore when the shopper returns here with the
        // back button later. Hand it back as soon as there is nothing left to
        // restore - and again on the way out, because a shopper who clicks a
        // link before this document finished loading would otherwise leave the
        // entry stuck on 'manual' for good.
        function release() {
            if (supported) history.scrollRestoration = 'auto';
        }
        window.addEventListener('pagehide', release);

        if (window.location.hash) { release(); return; }

        // The correcting passes below run as late as the load event, which on a
        // storefront is seconds after the page is readable. Whoever moved the
        // page in the meantime owns where it sits: a shopper who has started
        // reading must never be snapped back. Keyed off input rather than a
        // scroll flag because the corrections themselves emit scroll events.
        var userMoved = false;
        ['wheel', 'touchstart', 'touchmove', 'pointerdown', 'keydown'].forEach(function (type) {
            window.addEventListener(type, function () { userMoved = true; }, { passive: true, capture: true, once: true });
        });

        function toTop() {
            if (userMoved) return;
            if (!window.scrollX && !window.scrollY) return;

            // The exit-intent popup in app.js reads a large upward jump ending
            // near the top as a lunge for the tab bar, which is exactly the
            // shape of this correction. Say so, so it does not ambush a shopper
            // with a discount modal for pressing F5. Scroll events land at the
            // next rendering step rather than inside this call, so the flag has
            // to outlive it - and a background tab never paints, so this is on
            // a timer rather than a frame.
            window.kkScrollTopInProgress = true;
            try {
                window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
            } catch (e) {
                // 'instant' is a newer value of the enum; older engines throw
                // on it rather than ignoring it. The two-argument form is not
                // affected by the scroll-smooth class on <html>.
                window.scrollTo(0, 0);
            }
            window.setTimeout(function () { window.kkScrollTopInProgress = false; }, 250);
        }

        toTop();
        document.addEventListener('DOMContentLoaded', toTop);
        window.addEventListener('load', function () {
            toTop();
            // Images and fonts settle after load and can drag the offset back
            // down with them, so correct once more on the next frame.
            if (window.requestAnimationFrame) {
                window.requestAnimationFrame(function () { toTop(); release(); });
            } else {
                release();
            }
        });
    })();
</script>
