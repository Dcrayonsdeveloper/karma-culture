{{--
    Compare-at price / MRP may never sit below the price it is compared against.
    The server already enforces that (`mrp => gte:price`, and the same rule per
    size row), but only after a round trip that throws away everything else
    typed into the form - images included, since a file input cannot be
    repopulated. So the pair is checked here as the numbers are entered, and the
    submit is blocked until it reads the right way round.
--}}
<script>
    (function () {
        const form = document.getElementById('product-form');
        if (!form) return;

        const price = form.querySelector('#price');
        const mrp = form.querySelector('#mrp');
        if (!price || !mrp) return;

        // The two sentences this guard can say, each written down once.
        //
        // The compare-at complaint used to be published through three channels at
        // the same moment - a hand-rolled <p id="mrp-compare-error">, a
        // setCustomValidity() that the site-wide validator in app.js reads back and
        // renders as its own .kk-field-error, and a toastr from reject() - with the
        // server's own field-error paragraph for `mrp`, worded differently again,
        // sitting between them. One wrong pair of numbers, up to four complaints.
        // setCustomValidity() is now the single source: it is the only one of the
        // three that both stops the submit and reaches the one renderer that owns
        // every other message on this form.
        const COMPARE_MESSAGE = 'Compare-at price must not be less than Price.';
        const VARIANT_MESSAGE = 'MRP must not be less than this size’s price.';

        // A blank compare-at is fine (it falls back to the price on save) and a
        // half-typed number is not a mistake yet: only a complete pair that is
        // the wrong way round counts.
        function isBelow(priceValue, comparedValue) {
            const p = parseFloat(priceValue);
            const c = parseFloat(comparedValue);

            return Number.isFinite(p) && Number.isFinite(c) && c < p;
        }

        function checkProductPricing() {
            const bad = isBelow(price.value, mrp.value);

            mrp.classList.toggle('form-input-error', bad);
            // setCustomValidity carries the whole job on its own, which is why the
            // paragraph that used to sit beside it is gone. It blocks the browser's
            // own submit path, so a keyboard Enter or a second submit button cannot
            // slip past; and messageFor() in app.js returns a customError verbatim,
            // so this exact sentence is what appears under the field on blur, on
            // invalid and on submit - as the field's one note, replacing whatever
            // the last response said rather than stacking on top of it.
            mrp.setCustomValidity(bad ? COMPARE_MESSAGE : '');

            return !bad;
        }

        // The size rows are rendered by Alpine and can be added or removed at
        // any time, so they are looked up on each check rather than bound once.
        // Rows hidden by a pending removal are skipped: marking an invisible
        // input invalid would block the submit with nothing on screen to fix.
        function checkVariantPricing() {
            let firstBad = null;

            form.querySelectorAll('input[aria-label="Size MRP"]').forEach(function (rowMrp) {
                const row = rowMrp.closest('tr');
                const rowPrice = row ? row.querySelector('input[aria-label="Size price"]') : null;
                const bad = rowPrice !== null && rowMrp.offsetParent !== null
                    && isBelow(rowPrice.value, rowMrp.value);

                rowMrp.style.borderColor = bad ? '#d72c0d' : '#d4d4d4';
                rowMrp.setCustomValidity(bad ? VARIANT_MESSAGE : '');
                if (bad && !firstBad) firstBad = rowMrp;
            });

            return firstBad;
        }

        // The submit is stopped, and the reason is put where the reason belongs -
        // under the field, in the same renderer as every other message on this
        // form. reportValidity() re-fires `invalid` on the control, which app.js
        // answers by suppressing the browser's native bubble and printing the
        // customError set above as that field's single note. The toastr this
        // replaces was a second copy of a sentence already on screen, floating in a
        // corner away from the box it was about, and worded differently from it.
        function reject(field) {
            field.focus({ preventScroll: true });
            field.scrollIntoView({ block: 'center', behavior: 'smooth' });
            field.reportValidity();
        }

        price.addEventListener('input', checkProductPricing);
        mrp.addEventListener('input', checkProductPricing);
        form.addEventListener('input', function (event) {
            if (event.target.matches('input[aria-label="Size MRP"], input[aria-label="Size price"]')) {
                checkVariantPricing();
            }
        });

        form.addEventListener('submit', function (event) {
            const productOk = checkProductPricing();
            const badVariant = checkVariantPricing();
            if (productOk && !badVariant) return;

            event.preventDefault();
            // Whichever field is wrong speaks for itself; there is no separate
            // form-level wording to keep in step with it any more.
            reject(productOk ? badVariant : mrp);
        });

        // Re-arms the constraint over the values the server sent back, so a pair
        // that was rejected on the round trip cannot simply be submitted again
        // unchanged. It deliberately does NOT print anything: the server's own
        // server-rendered field-error note is already under the field at this point, and a
        // second sentence saying the same thing in different words is the exact
        // duplication this guard was rewritten to stop. The note appears the moment
        // the person touches the field or tries to save - by which time app.js has
        // retired the server's, so there is still only ever one.
        checkProductPricing();
        checkVariantPricing();
    })();
</script>
