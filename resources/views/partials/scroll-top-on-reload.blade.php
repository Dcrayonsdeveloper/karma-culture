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

        if (navType === 'back_forward') return;

        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }

        if (window.location.hash) return;

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
                window.requestAnimationFrame(function () {
                    toTop();
                    // 'manual' is stored on this history entry, not on the tab,
                    // so leaving it set would also kill the restore when the
                    // shopper returns here with the back button later. By now
                    // the document has loaded and nothing is left to restore.
                    if ('scrollRestoration' in history) history.scrollRestoration = 'auto';
                });
            } else if ('scrollRestoration' in history) {
                history.scrollRestoration = 'auto';
            }
        });
    })();
</script>
