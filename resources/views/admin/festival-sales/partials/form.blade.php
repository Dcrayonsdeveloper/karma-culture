@php
    /**
     * Shared by create and edit.
     *
     * $festivalSale is null on create. $pickerProducts and $pickerCategories are
     * built by the controller; $selectedIds is what is ticked (old input wins,
     * so a failed validation does not lose the admin's selection).
     */
    $sale = $festivalSale ?? null;

    $currentPercent = old('discount_percent', $sale->discount_percent ?? '');
@endphp

<div x-data="festivalSalePicker(@js($pickerProducts), @js(array_values($selectedIds)), '{{ $currentPercent }}')">

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; align-items: start;">

        {{-- ---------------------------------------------------------------
             Left column: the sale itself, then the product picker
             --------------------------------------------------------------- --}}
        <div style="display: flex; flex-direction: column; gap: 1rem; min-width: 0;">

            <div class="card" style="padding: 1.25rem;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Sale details</h2>

                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        {{-- for/id pairs matter beyond accessibility here: the inline
                             validator names the field from its own <label>, so an
                             unlabelled input reports "This field is required"
                             instead of "Name is required". --}}
                        <label for="fest-name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">
                            Sale name <span style="color: #d72c0d;">*</span>
                        </label>
                        <input type="text" name="name" id="fest-name" required minlength="2" maxlength="255"
                               value="{{ old('name', $sale->name ?? '') }}"
                               class="form-input" style="width: 100%;" placeholder="e.g. Diwali Festival Sale">
                        @error('name')
                            <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="fest-percent" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">
                            Discount percentage <span style="color: #d72c0d;">*</span>
                        </label>
                        <div style="display: flex; align-items: center; gap: 0.5rem; max-width: 220px;">
                            <input type="number" name="discount_percent" id="fest-percent" required
                                   min="1" max="95" step="0.01" inputmode="decimal"
                                   x-model="percent"
                                   value="{{ $currentPercent }}"
                                   class="form-input" style="width: 100%;" placeholder="25">
                            <span style="font-size: 15px; font-weight: 600; color: #616161;">%</span>
                        </div>
                        <p style="font-size: 12px; color: #616161; margin-top: 0.375rem;">
                            Taken off every selected product's current price. The old price is
                            remembered and put back when the sale ends.
                        </p>
                        @error('discount_percent')
                            <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="fest-description" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Description</label>
                        <textarea name="description" id="fest-description" rows="2" maxlength="1000"
                                  class="form-textarea" style="width: 100%;"
                                  placeholder="Shown under the banner on the sale page.">{{ old('description', $sale->description ?? '') }}</textarea>
                        @error('description')
                            <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------------------------
                 The picker. A scrolling grid of tiles rather than a stack of
                 selects: an admin building a festival sale is choosing dozens
                 of products at a time and needs to see the photograph and what
                 each one will end up costing, which a dropdown cannot show.
                 ------------------------------------------------------------ --}}
            <div class="card" style="padding: 1.25rem;">
                <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Products in this sale</h2>
                    <span style="font-size: 12px; color: #616161;">
                        <strong x-text="selected.length" style="color: #303030;"></strong>
                        <span x-text="selected.length === 1 ? 'product selected' : 'products selected'"></span>
                    </span>
                </div>

                <div style="display: flex; gap: 0.5rem; margin: 0.875rem 0; flex-wrap: wrap;">
                    <input type="search" x-model="query" @input="page = 1"
                           class="form-input" style="flex: 1 1 200px; font-size: 13px;"
                           placeholder="Search by name or SKU..." aria-label="Search products">

                    {{-- Picking a parent category matches everything filed under
                         its children too: each product carries its whole ancestor
                         chain, so "Men's" finds a shirt filed under Men's > Shirts
                         without this control knowing the tree exists. --}}
                    <select x-model.number="category" @change="page = 1"
                            class="form-select" style="flex: 0 1 220px; font-size: 13px;"
                            aria-label="Filter products by category">
                        <option value="0">All categories</option>
                        @foreach($pickerCategories as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }} ({{ $option['count'] }})</option>
                        @endforeach
                    </select>

                    <button type="button" class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;"
                            @click="selectAllShown()"
                            x-text="'Select these ' + visible.length"></button>
                    <button type="button" class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;"
                            @click="clearShown()" x-show="shownSelectedCount > 0" x-cloak
                            x-text="'Deselect these ' + shownSelectedCount"></button>
                    <button type="button" class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;"
                            @click="clearAll()" x-show="selected.length > 0" x-cloak>Clear all</button>
                </div>

                @error('products')
                    <p style="font-size: 12px; color: #d72c0d; margin-bottom: 0.5rem;">{{ $message }}</p>
                @enderror
                @error('products.*')
                    <p style="font-size: 12px; color: #d72c0d; margin-bottom: 0.5rem;">{{ $message }}</p>
                @enderror

                {{-- The scroll box. Fixed height so the page itself does not
                     become thousands of tiles long, and the Save button stays
                     reachable without scrolling past the whole catalogue. --}}
                <div class="fest-picker-scroll">
                    <template x-if="visible.length === 0">
                        <p style="font-size: 13px; color: #616161; padding: 1rem; margin: 0;">
                            No products match this search and category.
                        </p>
                    </template>

                    <div class="fest-picker-grid">
                        <template x-for="p in paged" :key="p.id">
                            <label class="fest-tile" :class="{ 'is-picked': isPicked(p.id) }">
                                <input type="checkbox" class="fest-tile__check"
                                       :checked="isPicked(p.id)"
                                       @change="toggle(p.id)"
                                       :aria-label="'Include ' + p.name + ' in this sale'">
                                <img :src="p.img" :alt="''" class="fest-tile__img" loading="lazy"
                                     onerror="this.src='{{ asset_v('images/no-product-image.svg') }}'">
                                <div class="fest-tile__body">
                                    <p class="fest-tile__name" x-text="p.name" :title="p.name"></p>
                                    <p class="fest-tile__sku" x-text="p.sku || '—'"></p>
                                    <p class="fest-tile__price">
                                        <span class="fest-tile__was" x-text="money(p.price)"></span>
                                        <span class="fest-tile__now" x-text="money(salePrice(p.price))"></span>
                                    </p>
                                </div>
                            </label>
                        </template>
                    </div>

                    <div x-show="paged.length < visible.length" x-cloak style="padding: 0.75rem; text-align: center;">
                        <button type="button" class="btn btn-secondary" style="font-size: 12px; padding: 6px 14px;"
                                @click="page++">
                            Show more (<span x-text="visible.length - paged.length"></span> left)
                        </button>
                    </div>
                </div>

                {{-- What actually posts. Driven by `selected` rather than by the
                     checkboxes, so a product ticked and then filtered out of
                     view by a search is still submitted. --}}
                <template x-for="id in selected" :key="'f-' + id">
                    <input type="hidden" name="products[]" :value="id">
                </template>
            </div>
        </div>

        {{-- ---------------------------------------------------------------
             Right column: status and banner
             --------------------------------------------------------------- --}}
        <div style="display: flex; flex-direction: column; gap: 1rem; min-width: 0;">

            <div class="card" style="padding: 1.25rem;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 0.75rem;">Status</h2>

                <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" style="margin-top: 2px;"
                           @checked(old('is_active', $sale->is_active ?? false))>
                    <span>
                        <span style="font-size: 13px; font-weight: 500; color: #303030;">Live</span>
                        <span style="display: block; font-size: 12px; color: #616161;">
                            Saving with this on rewrites every selected product's price
                            across the whole shop. Turning it off puts them all back.
                        </span>
                    </span>
                </label>

                <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer; margin-top: 0.875rem;">
                    <input type="hidden" name="show_on_home" value="0">
                    <input type="checkbox" name="show_on_home" value="1" style="margin-top: 2px;"
                           @checked(old('show_on_home', $sale->show_on_home ?? true))>
                    <span>
                        <span style="font-size: 13px; font-weight: 500; color: #303030;">Show banner on home page</span>
                        <span style="display: block; font-size: 12px; color: #616161;">
                            Needs a banner image and a live sale.
                        </span>
                    </span>
                </label>
            </div>

            <div class="card" style="padding: 1.25rem;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">Banner</h2>
                <p style="font-size: 12px; color: #616161; margin: 0 0 0.875rem 0;">
                    Shown on the home page and at the top of the sale page. Wide artwork
                    works best - around 1600&times;500.
                </p>

                @if($sale?->bannerUrl())
                    <img src="{{ $sale->bannerUrl() }}" alt=""
                         style="width: 100%; border-radius: 0.5rem; margin-bottom: 0.5rem; display: block;">
                    <label style="display: flex; align-items: center; gap: 0.375rem; font-size: 12px; color: #616161; margin-bottom: 0.75rem; cursor: pointer;">
                        <input type="checkbox" name="remove_banner" value="1">
                        Remove this image
                    </label>
                @endif

                <label for="fest-banner" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">
                    {{ $sale?->bannerUrl() ? 'Replace desktop banner' : 'Desktop banner' }}
                </label>
                <input type="file" name="banner" id="fest-banner" accept="image/jpeg,image/png,image/webp"
                       class="form-input" style="width: 100%; font-size: 12px;">
                @error('banner')
                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror

                @if($sale?->banner_mobile_path)
                    <img src="{{ $sale->bannerMobileUrl() }}" alt=""
                         style="width: 100%; border-radius: 0.5rem; margin: 0.875rem 0 0.5rem; display: block;">
                    <label style="display: flex; align-items: center; gap: 0.375rem; font-size: 12px; color: #616161; margin-bottom: 0.75rem; cursor: pointer;">
                        <input type="checkbox" name="remove_banner_mobile" value="1">
                        Remove the phone image
                    </label>
                @endif

                <label for="fest-banner-mobile" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin: 0.875rem 0 0.25rem;">
                    Phone banner <span style="font-weight: 400; color: #616161;">(optional)</span>
                </label>
                <input type="file" name="banner_mobile" id="fest-banner-mobile" accept="image/jpeg,image/png,image/webp"
                       class="form-input" style="width: 100%; font-size: 12px;">
                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                    Falls back to the desktop image when empty.
                </p>
                @error('banner_mobile')
                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .fest-picker-scroll {
        max-height: 26rem;
        overflow-y: auto;
        overscroll-behavior: contain;
        border: 1px solid #e3e3e3;
        border-radius: 0.625rem;
        background: #fafafa;
        padding: 0.625rem;
    }

    .fest-picker-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 0.625rem;
    }

    .fest-tile {
        position: relative;
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid #e3e3e3;
        border-radius: 0.5rem;
        overflow: hidden;
        cursor: pointer;
        transition: border-color .12s ease, box-shadow .12s ease;
    }
    .fest-tile:hover { border-color: #b5b5b5; }
    .fest-tile.is-picked {
        border-color: #303030;
        box-shadow: 0 0 0 1px #303030;
    }

    /* The checkbox sits over the photograph, as in a media library: the whole
       tile is the label, so the click target is the card and not a 13px box. */
    .fest-tile__check {
        position: absolute;
        top: 0.375rem;
        left: 0.375rem;
        z-index: 1;
        width: 16px;
        height: 16px;
        accent-color: #303030;
        cursor: pointer;
    }

    .fest-tile__img {
        width: 100%;
        aspect-ratio: 3 / 4;
        object-fit: cover;
        background: #f1f1f1;
        display: block;
    }

    .fest-tile__body { padding: 0.5rem; }

    .fest-tile__name {
        font-size: 12px;
        font-weight: 500;
        color: #303030;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        line-clamp: 2;
        overflow: hidden;
    }

    .fest-tile__sku {
        font-size: 11px;
        color: #8c9196;
        margin: 0.125rem 0 0.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .fest-tile__price {
        margin: 0;
        display: flex;
        align-items: baseline;
        gap: 0.375rem;
        flex-wrap: wrap;
    }
    .fest-tile__was { font-size: 11px; color: #8c9196; text-decoration: line-through; }
    .fest-tile__now { font-size: 12px; font-weight: 600; color: #1a7a2e; }

    @media (max-width: 900px) {
        .fest-picker-scroll { max-height: 22rem; }
        .fest-picker-grid { grid-template-columns: repeat(auto-fill, minmax(128px, 1fr)); }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('festivalSalePicker', (rows, initial, initialPercent) => ({
            rows: rows,
            selected: (initial || []).map(Number),
            query: '',
            category: 0,
            percent: initialPercent,

            // How many tiles are in the DOM at once. The catalogue can run to
            // thousands and rendering all of them makes the page unusable, so
            // the list grows a page at a time and search narrows it instead.
            perPage: 120,
            page: 1,

            get visible() {
                const q = this.query.trim().toLowerCase();
                const cat = Number(this.category) || 0;

                if (!q && !cat) {
                    return this.rows;
                }

                return this.rows.filter(p => {
                    // cats is the category's own id plus every ancestor, so a
                    // parent category matches its children's products too.
                    if (cat && !(p.cats || []).includes(cat)) { return false; }
                    if (!q) { return true; }

                    return p.name.toLowerCase().includes(q)
                        || (p.sku || '').toLowerCase().includes(q);
                });
            },

            get paged() {
                return this.visible.slice(0, this.perPage * this.page);
            },

            // How much of the current filter is already ticked - what the
            // "Deselect these N" button acts on, so narrowing to a category and
            // dropping just that category is one click rather than N.
            get shownSelectedCount() {
                return this.visible.reduce((n, p) => n + (this.selected.includes(p.id) ? 1 : 0), 0);
            },

            isPicked(id) { return this.selected.includes(id); },

            toggle(id) {
                const i = this.selected.indexOf(id);
                if (i === -1) { this.selected.push(id); } else { this.selected.splice(i, 1); }
            },

            selectAllShown() {
                for (const p of this.visible) {
                    if (!this.selected.includes(p.id)) { this.selected.push(p.id); }
                }
            },

            clearShown() {
                const shown = new Set(this.visible.map(p => p.id));
                this.selected = this.selected.filter(id => !shown.has(id));
            },

            clearAll() { this.selected = []; },

            salePrice(base) {
                const pct = parseFloat(this.percent);

                if (!isFinite(pct) || pct <= 0) { return base; }

                // The same floor App\Models\FestivalSale::priceFor() applies, so
                // the preview cannot promise a price the server will not honour.
                return Math.max(1, Math.round(base * (1 - pct / 100) * 100) / 100);
            },

            money(v) {
                return '{{ currency_symbol() }}' + Number(v).toLocaleString('en-IN', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                });
            },
        }));
    });
</script>
@endpush
@endonce
