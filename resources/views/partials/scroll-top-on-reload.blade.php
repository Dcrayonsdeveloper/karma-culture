{{-- A reload lands at the top of the page.

     Browsers restore the previous scroll offset when a page is reloaded, so a
     shopper who refreshes half way down a listing comes back staring at the
     middle of the page instead of the top of it.

     Back and forward are deliberately left alone - restoring the offset is the
     whole point of the back button, and losing the shopper's place in a long
     listing on the way back from a product would be a worse bug than the one
     this fixes. A URL that names an anchor (/faq#returns) is asking for that
     section rather than for the top, so its jump is left alone too.

     Everything else - a reload, and an ordinary navigation, where this is a
     no-op - is forced to the top. Testing for "not back/forward" rather than
     for "is a reload" on purpose: the reload flavours differ across browsers
     (F5, the toolbar button, location.reload(), a re-POST) and a missed flavour
     would silently bring the bug back.

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
        // available on engines without the navigation timing entry. 2 is
        // TYPE_BACK_FORWARD.
        if (!navType && window.performance && performance.navigation) {
            navType = performance.navigation.type === 2 ? 'back_forward' : 'navigate';
        }

        if (keepScroll || navType === 'back_forward') return;

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

        function toTop() {
            // Never scroll when already at the top. Beyond being pointless it
            // would emit a scroll event, and the exit-intent popup in app.js
            // reads a large upward jump near the top as an exit signal.
            if (!window.scrollX && !window.scrollY) return;
            try {
                window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
            } catch (e) {
                // 'instant' is a newer value of the enum; older engines throw
                // on it rather than ignoring it. The two-argument form is not
                // affected by the scroll-smooth class on <html>.
                window.scrollTo(0, 0);
            }
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
