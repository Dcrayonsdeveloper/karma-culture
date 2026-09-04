<x-layouts.admin>
    <x-slot name="title">Out of Stock</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Out of Stock Products</h1>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.inventory.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Inventory
        </a>
    </div>

    <div style="margin-bottom: 0.5rem;">
        <p style="font-size: 13px; color: #616161; margin: 0;">Products with zero or negative stock</p>
    </div>

@php
    // A refused restock used to vanish without a trace. updateStock() validates
    // the quantity, the type and the location and redirects BACK to this page
    // when any of them is wrong - and this page rendered nothing at all from the
    // error bag. The dialog came back closed, the row still sat at zero, and
    // nothing on screen said the adjustment had been rejected. On this page in
    // particular that reads as success: the admin restocks a sold-out product,
    // sees no complaint, and leaves it unavailable to customers.
    //
    // The bag is therefore rendered inside the dialog, and the dialog is opened
    // when the bag is not empty. WHICH product the refused attempt was about is
    // the one thing the redirect could not carry: the form names its product in
    // the action URL (/admin/inventory/{id}/stock) rather than in a field, so
    // old() knew nothing about it and the reopened dialog would have posted the
    // corrected quantity to `/admin/inventory/null/stock`. The hidden product_id
    // in the form is what puts it in the flashed input - the controller takes the
    // product from the route and ignores the field - and it is looked back up
    // here so the dialog reopens showing the right name, the right totals and
    // the right per-location holdings.
    $restockProduct = $errors->any()
        ? collect($products->items())->firstWhere('id', (int) old('product_id'))
        : null;
@endphp

    {{-- The messages belong in the dialog, beside the boxes they are about, and
         that is where they go whenever the dialog can be reopened. It cannot be
         when the product has left this page between the attempt and the redirect
         - a restock that fails on the quantity changes nothing, but a page of
         rows shifts as other stock moves - and a failure that no one can see is
         the whole defect being fixed here. So on that one path the bag is
         printed on the page instead: nothing is rendered inline in this position,
         so every message is the banner's to carry. --}}
    @if($errors->any() && ! $restockProduct)
        <x-form-errors title="That restock was not saved." style="margin-bottom: 1rem;" />
    @endif

    @if($products->total() > 0)
        <div class="card" style="padding: 0.75rem 1rem; margin-bottom: 1rem; border-left: 4px solid #d72c0d;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d72c0d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                    <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p style="font-size: 13px; font-weight: 500; color: #b71c00; margin: 0;">
                    <span style="font-weight: 700;">{{ $products->total() }}</span> {{ Str::plural('product', $products->total()) }} out of stock. These are unavailable to customers.
                </p>
            </div>
        </div>
    @endif

    <div class="card">
        @if($products->total() > 0)
            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <p style="font-size: 13px; color: #616161; margin: 0;">
                    Showing <span style="font-weight: 500; color: #303030;">{{ $products->firstItem() }}</span>-<span style="font-weight: 500; color: #303030;">{{ $products->lastItem() }}</span> of <span style="font-weight: 500; color: #303030;">{{ $products->total() }}</span> products
                </p>
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Product</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">SKU</th>
                        <th style="padding: 0.5rem 1rem; text-align: center; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Stock</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem; font-weight: 500; color: #303030;">{{ $product->name }}</td>
                            <td style="padding: 0.625rem 1rem;">
                                <span style="font-size: 12px; font-family: monospace; background: #f6f6f7; color: #616161; padding: 0.125rem 0.5rem; border-radius: 0.25rem;">{{ $product->sku ?? '-' }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: center;">
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">{{ $product->stock_quantity }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <button onclick="openStockModal({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->stock_quantity }}, {{ Js::from($product->heldByLocation()) }})"
                                        class="btn btn-primary" style="font-size: 12px; padding: 0.25rem 0.625rem; display: inline-flex; align-items: center; gap: 0.375rem;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    Restock
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                    <div style="width: 48px; height: 48px; background: #cdfee1; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1a7a2e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">All products are in stock!</p>
                                    <p style="font-size: 12px; color: #616161; margin: 0;">No products have zero stock right now.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <!-- Stock Modal -->
@php
    // Everything the dialog's x-data needs, resolved BEFORE the tag rather than
    // inside it.
    //
    // An object arrow carries a ">", and a ">" ends the tag as far as any
    // HTML-ish scan is concerned - so `{{ $restockProduct?->name }}` written in
    // the attribute hid the rest of the element, x-show included, from
    // InventoryModalCenteringTest, the guard that stops this exact dialog losing
    // its centring again. Hoisting is also the reason the tag below is readable
    // at all: it was a single 400-character line of interleaved PHP and JS.
    $defaultLocationId = $locations->firstWhere('is_default', true)?->id ?? $locations->first()?->id;

    $restockOpen = $restockProduct ? 'true' : 'false';
    $restockId = $restockProduct ? (int) $restockProduct->id : 'null';
    $restockName = $restockProduct?->name ?? '';
    $restockStock = (int) ($restockProduct?->stock_quantity ?? 0);
    $restockByLocation = $restockProduct?->heldByLocation() ?? [];
    $restockLocationId = (string) old('location_id', $defaultLocationId);
@endphp

    <div x-data="{ open: {{ $restockOpen }}, productId: {{ $restockId }}, productName: {{ Js::from($restockName) }}, currentStock: {{ $restockStock }}, byLocation: {{ Js::from($restockByLocation) }}, locationId: {{ Js::from($restockLocationId) }}, get heldHere() { return this.byLocation[String(this.locationId)] ?? 0 } }"
         x-on:open-stock-modal.window="open = true; productId = $event.detail.id; productName = $event.detail.name; currentStock = $event.detail.stock; byLocation = $event.detail.byLocation || {}"
         x-show="open" x-cloak
         x-transition.opacity.duration.150ms
         x-effect="document.body.classList.toggle('kk-modal-open', open)"
         class="kk-modal">
        <div class="kk-modal__backdrop" x-on:click="open = false"></div>
        <div class="kk-modal__card">
            <div style="padding: 1rem 1.5rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h3 style="font-size: 14px; font-weight: 600; color: #303030; margin: 0;">Restock Product</h3>
                    <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;" x-text="productName"></p>
                </div>
                <button type="button" x-on:click="open = false" class="btn-icon" style="background: none; border: none; cursor: pointer; color: #616161; padding: 0.25rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div style="padding: 0.625rem 1.5rem; background: #ffe0db; border-bottom: 1px solid #e3e3e3;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 12px; color: #b71c00;">Held at the location below</span>
                    <span style="font-size: 13px; font-weight: 700; color: #d72c0d;" x-text="heldHere"></span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.25rem;">
                    <span style="font-size: 12px; color: #b71c00;">In stock everywhere</span>
                    <span style="font-size: 12px; color: #b71c00;" x-text="currentStock"></span>
                </div>
            </div>
            <form method="POST" id="restock-form" data-restock-product="{{ $restockProduct?->id }}" x-bind:action="'/admin/inventory/' + productId + '/stock'">
                @csrf
                @method('PUT')
                {{-- Not read by the controller, which resolves the product from the
                     route. It exists so that the redirect after a refused save
                     still says which product was being restocked - the note where
                     $restockProduct is worked out, further up, has the why. --}}
                <input type="hidden" name="product_id" x-bind:value="productId">
                <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                    {{-- The headline that says the save was refused, plus anything
                         the controller rejected that has no box of its own below.
                         Every key that DOES have one is listed as handled, so the
                         same sentence is never printed twice. --}}
                    <x-form-errors :handled="['location_id', 'type', 'quantity', 'reason']" title="That restock was not saved." />
                    <div>
                        {{-- Stock sits in a warehouse, so an adjustment has to say
                             which one it happens at. --}}
                        <label for="restock_location" class="form-label">Location</label>
                        <select name="location_id" id="restock_location" x-model="locationId" class="form-select" style="width: 100%;">
                            @forelse($locations as $location)
                                <option value="{{ $location->id }}" @selected($location->is_default)>{{ $location->name }} ({{ $location->code }})</option>
                            @empty
                                <option value="">Main Warehouse</option>
                            @endforelse
                        </select>
                        <x-field-error field="location_id" />
                    </div>
                    <div>
                        <label for="restock_type" class="form-label">Adjustment Type <span style="color: #d72c0d;">*</span></label>
                        <select name="type" id="restock_type" class="form-select" required>
                            <option value="add" @selected(old('type', 'add') === 'add')>Add Stock</option>
                            <option value="set" @selected(old('type') === 'set')>Set Stock To</option>
                        </select>
                        <x-field-error field="type" />
                    </div>
                    <div>
                        {{-- Every label on this form carries a `for` now, and this is
                             the box that shows why: the validator names a field from
                             its label, so with no label to find it fell back to the
                             placeholder and an empty quantity was reported as "0 is
                             required."

                             max and step are the server's own max:1000000 and integer
                             rules, restated where they can be answered before a request
                             is made. A fat-fingered extra digit used to cost a round
                             trip that then said nothing at all. --}}
                        <label for="restock_quantity" class="form-label">Quantity <span style="color: #d72c0d;">*</span></label>
                        <input type="number" name="quantity" id="restock_quantity" min="1" max="1000000" step="1" inputmode="numeric"
                               value="{{ old('quantity') }}" required class="form-input" placeholder="0">
                        <x-field-error field="quantity" />
                    </div>
                    <div>
                        <label for="restock_reason" class="form-label">Reason</label>
                        <input type="text" name="reason" id="restock_reason" maxlength="255" value="{{ old('reason') }}" class="form-input" placeholder="e.g. Restock, Purchase order">
                        <x-field-error field="reason" />
                    </div>
                </div>
                <div style="padding: 0.75rem 1.5rem; background: #f6f6f7; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; border-radius: 0 0 0.75rem 0.75rem;">
                    <button type="button" x-on:click="open = false" class="btn btn-secondary" style="font-size: 13px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Restock Now</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // One dialog serves every row on the page, so whatever a refused restock
        // left in it is still there when a DIFFERENT product's Restock button is
        // clicked - and none of it is true of the row just opened. "The quantity
        // is too large." was a verdict on another product's numbers, and the
        // quantity that earned it must not be sitting pre-filled under a name it
        // was never typed for: one careless Enter and the wrong product is
        // adjusted. Reopening the SAME product keeps both, because that is the
        // admin coming back to correct exactly what was refused.
        //
        // The messages are removed by the markers app.js uses for them, and the
        // outline and aria wiring go back with them, so the two never disagree
        // about what is on screen. `.kk-field-error` catches the server's notes
        // and the validator's own alike - a field can only ever show one, and
        // neither of them survives the change of product.
        // One dialog, reopened over whichever row was clicked, so everything the
        // last product left behind has to go before the next one is shown.
        //
        // Hand-clearing the DOM reached the note, the outline and the aria link
        // but not the state app.js keeps ON the control: the note it tracks as
        // _kkErrorNote, and the 2-second repaint timer the character filter arms,
        // which would fire after this ran and print the previous product's
        // complaint inside the new product's dialog. kkResetForm() is app.js
        // undoing all of it from the inside, so this page does not have to know
        // how any of it is stored.
        function clearRestockErrors(form) {
            if (window.kkResetForm) {
                window.kkResetForm(form);
                return;
            }

            // app.js has not booted yet (or failed to). Clear what is reachable
            // from here rather than leaving the last product's messages up.
            form.querySelectorAll('.kk-field-error, [data-kk-form-error]').forEach(function (note) {
                note.remove();
            });

            form.querySelectorAll('.kk-input-invalid').forEach(function (field) {
                field.classList.remove('kk-input-invalid');
                field.removeAttribute('aria-invalid');

                var describedBy = field.getAttribute('aria-describedby') || '';
                if (describedBy.indexOf('kk-srv-err-') === 0 || describedBy.indexOf('kk-err-') === 0) {
                    field.removeAttribute('aria-describedby');
                }
            });
        }

        function openStockModal(id, name, stock, byLocation) {
            var form = document.getElementById('restock-form');

            if (form && String(form.dataset.restockProduct || '') !== String(id)) {
                clearRestockErrors(form);
                form.dataset.restockProduct = String(id);

                // The numbers go with the messages. The location is deliberately
                // left alone: which warehouse the admin is working at holds good
                // across products, and it is bound to Alpine rather than to this
                // form's DOM value anyway.
                ['quantity', 'reason'].forEach(function (name) {
                    var field = form.querySelector('[name="' + name + '"]');
                    if (field) field.value = '';
                });

                // The adjustment MODE has to travel with them. The select
                // restores old('type') so that the admin coming back to correct
                // the product that WAS refused finds the mode they picked still
                // set; carried onto a different product it leaves a dialog that
                // looks freshly opened - blank quantity, blank reason, the new
                // name and the new totals in the header - while still in
                // absolute "Set Stock To". A row on this page reads zero, so the
                // two modes look interchangeable, but the adjustment happens at
                // ONE location and "Held at the location below" is the figure it
                // overwrites - which is not always zero here, because the
                // product total and the per-location rows can disagree. And the
                // stale mode outlives the mistake: it sits in the dialog for the
                // rest of the page's life, since only a refusal brings the admin
                // back without a reload. It is put back here rather than by
                // dropping the option's server-side pre-selection, because
                // restoring the mode for a re-open of the SAME product is
                // exactly what that pre-selection is for. 'add' is the option
                // the markup defaults to.
                var type = form.querySelector('[name="type"]');
                if (type) type.value = 'add';
            }

            window.dispatchEvent(new CustomEvent('open-stock-modal', { detail: { id, name, stock, byLocation } }));
        }
    </script>
</x-layouts.admin>
