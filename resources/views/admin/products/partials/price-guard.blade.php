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
        const message = form.querySelector('#mrp-compare-error');
        if (!price || !mrp) return;

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

            if (message) {
                message.textContent = bad ? 'Compare-at price must not be less than Price.' : '';
                message.hidden = !bad;
            }
            mrp.classList.toggle('form-input-error', bad);
            // setCustomValidity also blocks the browser's own submit path, so a
            // keyboard Enter or a second submit button cannot slip past.
            mrp.setCustomValidity(bad ? 'Compare-at price must not be less than Price.' : '');

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
                rowMrp.setCustomValidity(bad ? 'MRP must not be less than this size\u2019s price.' : '');
                if (bad && !firstBad) firstBad = rowMrp;
            });

            return firstBad;
        }

        function reject(field, text) {
            field.focus({ preventScroll: true });
            field.scrollIntoView({ block: 'center', behavior: 'smooth' });
            if (window.toastr) toastr.error(text);
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
            if (!productOk) {
                reject(mrp, 'Compare-at price must not be less than Price.');
            } else {
                reject(badVariant, 'Each size\u2019s MRP must not be less than that size\u2019s price.');
            }
        });

        // Catches a value the server rejected and sent back, so the message is
        // already on screen when the form reloads with old input.
        checkProductPricing();
        checkVariantPricing();
    })();
</script>
