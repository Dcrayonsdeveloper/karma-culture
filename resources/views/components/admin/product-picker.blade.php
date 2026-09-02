@props([
    'name' => 'applicable_products[]',
    'selected' => [],
    'names' => [],
    'label' => 'Applicable Products',
    'hint' => 'Leave empty to apply to all products.',
    'placeholder' => 'Search products by name...',
    'errorKey' => 'applicable_products',
    'minChars' => 2,
])

@once
@push('styles')
<style>
    /* Searchable multi-select (products) --------------------------------- */
    .mselect { position: relative; }

    .mselect__control {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.375rem;
        width: 100%; min-height: 2.625rem; max-height: 8rem; overflow-y: auto;
        padding: 0.375rem 0.625rem;
        border: 1px solid #c9cccf; border-radius: 0.5rem; background: #fff;
        cursor: text; transition: border-color .15s ease, box-shadow .15s ease;
    }
    .mselect__control.is-focused { border-color: #303030; box-shadow: 0 0 0 1px #303030; }

    .mselect__tag {
        display: inline-flex; align-items: center; gap: 0.25rem; max-width: 100%;
        padding: 0.125rem 0.25rem 0.125rem 0.5rem;
        border-radius: 0.375rem; background: #f1f1f1; color: #303030;
        font-size: 12px; font-weight: 500; line-height: 1.5;
    }
    .mselect__tag-label { max-width: 11rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mselect__tag-remove {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 0.125rem; border: 0; border-radius: 0.25rem;
        background: none; color: #616161; cursor: pointer;
    }
    .mselect__tag-remove:hover { background: #e0e0e0; color: #303030; }

    .mselect__input {
        flex: 1 1 7.5rem; min-width: 7.5rem;
        border: 0; background: transparent; padding: 0; outline: none;
        font-size: 13px; color: #303030;
    }
    .mselect__indicator { flex-shrink: 0; margin-left: auto; padding-left: 0.25rem; display: inline-flex; color: #616161; }
    .mselect__spinner { animation: mselect-spin 1s linear infinite; }
    @keyframes mselect-spin { to { transform: rotate(360deg); } }

    /* The dropdown itself */
    .mselect__menu {
        position: absolute; left: 0; right: 0; top: calc(100% + 0.25rem); z-index: 60;
        padding: 0.25rem;
        background: #fff; border: 1px solid #e3e3e3; border-radius: 0.5rem;
        box-shadow: 0 8px 20px rgba(0, 0, 0, .12);
        max-height: min(15rem, 45vh); overflow-y: auto; overscroll-behavior: contain;
    }
    .mselect__option {
        display: flex; align-items: center; gap: 0.5rem;
        width: 100%; padding: 0.5rem 0.625rem;
        border: 0; border-radius: 0.375rem; background: transparent;
        font-size: 13px; line-height: 1.35; color: #303030; text-align: left; cursor: pointer;
    }
    .mselect__option:hover, .mselect__option.is-active { background: #f1f1f1; }
    .mselect__option-icon { flex-shrink: 0; color: #8c9196; }
    .mselect__option-label { min-width: 0; overflow-wrap: anywhere; }
    .mselect__note {
        display: flex; align-items: center; gap: 0.5rem;
        padding: 0.625rem; font-size: 13px; color: #616161;
    }
    .mselect__note strong { color: #303030; font-weight: 500; overflow-wrap: anywhere; }

    .mselect__footer {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
        gap: 0.5rem; font-size: 12px; color: #616161;
    }
    .mselect__clear {
        border: 0; background: none; padding: 0; cursor: pointer;
        font-size: 12px; color: #005bd3; text-decoration: underline;
    }

    /* The admin layout gives every .card overflow-x:auto on mobile, which would
       clip the absolutely-positioned menu. Opt this card out. */
    .layout-admin .card.mselect-card { overflow: visible !important; }

    @media (max-width: 640px) {
        .mselect__input { font-size: 16px; }        /* stops iOS zooming on focus */
        .mselect__control { max-height: 10rem; }
        .mselect__tag-label { max-width: 60vw; }
        .mselect__menu { max-height: min(13rem, 40vh); }
        .mselect__option { padding: 0.625rem; }     /* comfortable tap target */
        .mselect__tag-remove { padding: 0.375rem; margin: -0.25rem -0.125rem -0.25rem 0; }   /* 24px hit area, chip height unchanged */
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('productPicker', (initialSelected = [], initialNames = {}, searchUrl = '', minChars = 2) => ({
            searchUrl: searchUrl,
            minChars: minChars,
            selected: Array.isArray(initialSelected) ? initialSelected.map(Number) : [],
            names: Object.assign({}, initialNames),
            search: '',
            results: [],
            loading: false,
            open: false,
            focused: false,
            active: -1,
            timer: null,
            requestId: 0,

            focusInput() { this.$refs.input.focus(); },
            onFocus() { this.focused = true; this.open = true; },
            onInput() {
                this.open = true;
                this.active = -1;
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.runSearch(), 250);
            },
            close() { this.open = false; this.focused = false; this.active = -1; },

            async runSearch() {
                const q = this.search.trim();
                if (q.length < this.minChars) { this.results = []; this.loading = false; return; }

                const id = ++this.requestId;
                this.loading = true;
                try {
                    const res = await fetch(this.searchUrl + '?q=' + encodeURIComponent(q), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await res.json();
                    if (id !== this.requestId) return;          // a newer keystroke won
                    this.results = (Array.isArray(data) ? data : []).filter(p => !this.selected.includes(p.id));
                } catch (e) {
                    if (id !== this.requestId) return;
                    this.results = [];
                }
                this.loading = false;
                this.active = this.results.length ? 0 : -1;
            },

            move(step) {
                if (!this.results.length) return;
                this.open = true;
                this.active = (this.active + step + this.results.length) % this.results.length;
                this.$nextTick(() => {
                    const el = this.$refs.menu ? this.$refs.menu.querySelector('.is-active') : null;
                    if (el) el.scrollIntoView({ block: 'nearest' });
                });
            },
            choose() {
                if (this.active >= 0 && this.results[this.active]) this.add(this.results[this.active]);
            },

            add(product) {
                if (!this.selected.includes(product.id)) {
                    this.selected.push(product.id);
                    this.names[product.id] = product.name;
                }
                this.search = '';
                this.results = [];
                this.active = -1;
                this.open = false;
                this.$refs.input.focus();
            },
            remove(id) {
                this.selected = this.selected.filter(i => i !== id);
                delete this.names[id];
            },
            clearAll() {
                this.selected = [];
                this.names = {};
                this.$refs.input.focus();
            },
            getName(id) { return this.names[id] || 'Product #' + id; },
        }));
    });
</script>
@endpush
@endonce

<div class="card mselect-card" style="padding: 1.25rem;"
     x-data="productPicker(@js(array_values((array) $selected)), @js((object) $names), @js(route('admin.search.products')), {{ (int) $minChars }})">
    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">{{ $label }}</h2>

    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        <p style="font-size: 12px; color: #616161;">{{ $hint }}</p>

        <div class="mselect" @click.outside="close()" @keydown.escape="close()">
            <div class="mselect__control" :class="{ 'is-focused': focused }" @click="focusInput()">
                {{-- Selected products --}}
                <template x-for="id in selected" :key="id">
                    <span class="mselect__tag">
                        <span class="mselect__tag-label" x-text="getName(id)"></span>
                        <button type="button" class="mselect__tag-remove" @click.stop="remove(id)"
                                :aria-label="'Remove ' + getName(id)">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <input type="hidden" name="{{ $name }}" :value="id">
                    </span>
                </template>

                {{-- Search field --}}
                <input type="text" class="mselect__input" x-ref="input" x-model="search"
                       role="combobox" aria-autocomplete="list" aria-haspopup="listbox"
                       :aria-expanded="open ? 'true' : 'false'"
                       @input="onInput()"
                       @focus="onFocus()"
                       @blur="focused = false"
                       @keydown.arrow-down.prevent="move(1)"
                       @keydown.arrow-up.prevent="move(-1)"
                       @keydown.enter.prevent="choose()"
                       @keydown.backspace="if (search === '' && selected.length) remove(selected[selected.length - 1])"
                       :placeholder="selected.length === 0 ? @js($placeholder) : ''"
                       autocomplete="off">

                {{-- Loading / search indicator --}}
                <span class="mselect__indicator" aria-hidden="true">
                    <template x-if="loading">
                        <svg class="mselect__spinner" width="16" height="16" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <template x-if="!loading">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </template>
                </span>
            </div>

            {{-- Dropdown --}}
            <div class="mselect__menu" role="listbox" x-ref="menu" x-show="open" x-cloak
                 x-transition.opacity.duration.150ms>
                <template x-if="search.trim().length < minChars">
                    <div class="mselect__note">
                        Type at least <strong x-text="minChars"></strong>&nbsp;characters to search products.
                    </div>
                </template>

                <template x-if="loading && search.trim().length >= minChars">
                    <div class="mselect__note">Searching...</div>
                </template>

                <template x-if="!loading && search.trim().length >= minChars && results.length === 0">
                    <div class="mselect__note">
                        No products found for "<strong x-text="search.trim()"></strong>"
                    </div>
                </template>

                <template x-for="(product, index) in results" :key="product.id">
                    <button type="button" class="mselect__option" role="option"
                            :class="{ 'is-active': index === active }"
                            :aria-selected="index === active"
                            @click="add(product)"
                            @mouseenter="active = index">
                        <svg class="mselect__option-icon" width="16" height="16" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span class="mselect__option-label" x-text="product.name"></span>
                    </button>
                </template>
            </div>
        </div>

        <div class="mselect__footer" x-show="selected.length > 0" x-cloak>
            <span x-text="selected.length + (selected.length === 1 ? ' product selected' : ' products selected')"></span>
            <button type="button" class="mselect__clear" @click="clearAll()">Clear all</button>
        </div>

        @error($errorKey)
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
</div>
