<x-layouts.admin>
    <x-slot name="title">{{ $location->name }} - Stock</x-slot>

    @php
        $stockBase = url('/admin/inventory/locations/'.$location->id.'/stock');
        $addFailed = $errors->addStock->isNotEmpty();
        $addOld = fn (string $field) => $addFailed ? old($field, '') : '';
        // The picker needs each product's sizes to hand, so the size dropdown
        // can narrow itself the moment a product is chosen.
        $pickerProducts = $products->map(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'stock' => (int) $product->stock_quantity,
            'variants' => $product->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'name' => \App\Models\ProductVariant::sizeLabel($variant->name),
                'stock' => (int) $variant->stock_quantity,
            ])->values(),
        ])->values();
    @endphp

    <x-slot name="header">
        <div class="page-header">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <h1>{{ $location->name }}</h1>
                @if($location->is_active)
                    <span class="badge badge-success">Active</span>
                @else
                    <span class="badge badge-warning">Inactive</span>
                @endif
                <span style="font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace; font-size: 12px; background: #f6f6f7; color: #616161; padding: 0.125rem 0.375rem; border-radius: 0.25rem;">{{ $location->code }}</span>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <a href="{{ route('admin.inventory.locations.edit', $location) }}" class="btn btn-secondary" style="font-size: 13px;">Edit location</a>
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-add-stock'))" class="btn btn-primary" style="font-size: 13px;">Add product</button>
            </div>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.inventory.locations.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Locations
        </a>
        @if($location->address)
            <span style="font-size: 13px; color: #616161; margin-left: 0.5rem;">{{ $location->address }}</span>
        @endif
    </div>

    {{-- What this location is holding --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin: 1rem 0;">
        <div style="background: #fff; padding: 0.875rem 1rem;">
            <p style="font-size: 12px; color: #616161; margin-bottom: 2px;">Products stocked</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">{{ (int) ($totals->lines ?? 0) }}</p>
        </div>
        <div style="background: #fff; padding: 0.875rem 1rem;">
            <p style="font-size: 12px; color: #616161; margin-bottom: 2px;">Units on hand</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">{{ (int) ($totals->units ?? 0) }}</p>
        </div>
        <div style="background: #fff; padding: 0.875rem 1rem;">
            <p style="font-size: 12px; color: #616161; margin-bottom: 2px;">Reserved</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #b98900; margin: 0;">{{ (int) ($totals->reserved ?? 0) }}</p>
        </div>
        <div style="background: #fff; padding: 0.875rem 1rem;">
            <p style="font-size: 12px; color: #616161; margin-bottom: 2px;">Available</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e; margin: 0;">{{ (int) ($totals->available ?? 0) }}</p>
        </div>
    </div>

    {{-- Add a product to this location --}}
    <div x-data="{ open: {{ $addFailed ? 'true' : 'false' }} }"
         x-on:open-add-stock.window="open = true; $nextTick(() => $refs.product?.focus())">
        <div class="card" x-show="open" x-cloak style="padding: 1.25rem; margin-bottom: 1rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Add a product to {{ $location->name }}</h2>
                <button type="button" x-on:click="open = false" class="btn-icon" style="background: none; border: none; cursor: pointer; color: #616161; padding: 0.25rem;">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('admin.inventory.locations.stock.store', $location) }}" method="POST"
                  x-data="kkLocationPicker(@js($pickerProducts), @js(['productId' => $addOld('product_id'), 'variantId' => $addOld('variant_id')]))">
                @csrf
                <div style="display: flex; flex-wrap: wrap; align-items: flex-start; gap: 0.75rem;">
                    <div style="flex: 1 1 260px;">
                        <label for="stock_product_id" class="form-label">Product <span style="color: #d72c0d;">*</span></label>
                        <select name="product_id" id="stock_product_id" x-ref="product" x-model="productId" required class="form-select" style="width: 100%; font-size: 13px;">
                            <option value="">Choose a product...</option>
                            @foreach($products as $pickable)
                                <option value="{{ $pickable->id }}" @selected($addOld('product_id') == $pickable->id)>{{ $pickable->name }}{{ $pickable->sku ? " ({$pickable->sku})" : '' }}</option>
                            @endforeach
                        </select>
                        @error('product_id', 'addStock')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="flex: 0 1 180px;">
                        {{-- Sizes are optional: a warehouse can hold a product as
                             one pile, or count each size on its own shelf. --}}
                        <label for="stock_variant_id" class="form-label">Size</label>
                        <select name="variant_id" id="stock_variant_id" x-model="variantId" class="form-select" style="width: 100%; font-size: 13px;"
                                x-bind:disabled="variants.length === 0">
                            <option value="">All sizes</option>
                            <template x-for="variant in variants" :key="variant.id">
                                <option :value="variant.id" x-text="variant.name"></option>
                            </template>
                        </select>
                        @error('variant_id', 'addStock')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="flex: 0 0 110px;">
                        <label for="stock_add_quantity" class="form-label">Quantity <span style="color: #d72c0d;">*</span></label>
                        <input type="number" name="quantity" id="stock_add_quantity" min="0" max="1000000" step="1" inputmode="numeric"
                               value="{{ $addOld('quantity') }}" required class="form-input" placeholder="0" style="width: 100%; font-size: 13px;">
                        @error('quantity', 'addStock')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="flex: 1 1 180px;">
                        <label for="stock_add_reason" class="form-label">Reason</label>
                        <input type="text" name="reason" id="stock_add_reason" maxlength="255" value="{{ $addOld('reason') }}"
                               class="form-input" placeholder="e.g. Received from supplier" style="width: 100%; font-size: 13px;">
                        @error('reason', 'addStock')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="flex: 0 0 auto; padding-top: 1.35rem;">
                        <button type="submit" class="btn btn-primary" style="font-size: 13px;">Add to location</button>
                    </div>
                </div>

                <p style="font-size: 12px; color: #616161; margin: 0.75rem 0 0 0;">
                    These units are added to the product's sellable stock as well. A product already stocked here is topped up rather than listed twice.
                </p>
            </form>
        </div>
    </div>

    @if($errors->adjustStock->isNotEmpty())
        @php
            $refusedLine = $stocks->firstWhere('id', (int) old('line_id'));
            $refusedFor = $refusedLine?->product?->name;
        @endphp
        <div role="alert" style="margin-bottom: 1rem; padding: 0.75rem 1rem; border: 1px solid #f0b3a8; background: #fff4f1; border-radius: 0.5rem;">
            <p style="margin: 0; font-size: 13px; color: #b71c00;">
                <span style="font-weight: 600;">Adjustment not saved{{ $refusedFor ? ' for '.$refusedFor : '' }}.</span>
                {{ $errors->adjustStock->first() }}
            </p>
        </div>
    @endif

    {{-- Lines held here --}}
    <div class="card">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <form action="{{ route('admin.inventory.locations.show', $location) }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products here..." aria-label="Search products in this location"
                           style="width: 100%; padding: 0.4rem 0.5rem 0.4rem 1.75rem; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; outline: none; color: #303030;">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
                @if(request('search'))
                    <a href="{{ route('admin.inventory.locations.show', $location) }}" style="padding: 0.4rem 0.75rem; font-size: 13px; color: #616161; text-decoration: none;">Clear</a>
                @endif
            </form>
        </div>

        @if($stocks->total() > 0)
            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e3e3e3;">
                {{ $stocks->links('vendor.pagination.info-bar') }}
            </div>
        @endif

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Product</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">SKU</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Size</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Here</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Reserved</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Available</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">All locations</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $stock)
                        @php
                            $product = $stock->product;
                            $variant = $stock->variant;
                            $label = $product?->name ?? 'Deleted product';
                            $sizeLabel = $variant ? \App\Models\ProductVariant::sizeLabel($variant->name) : null;
                            $onHand = (int) $stock->quantity;
                            $everywhere = (int) ($variant?->stock_quantity ?? $product?->stock_quantity ?? 0);
                        @endphp
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem; font-weight: 500; color: #303030;">
                                @if($product)
                                    <a href="{{ route('admin.products.edit', $product) }}" style="color: #303030; text-decoration: none;">{{ $label }}</a>
                                @else
                                    <span style="color: #616161;">{{ $label }}</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">
                                <span style="font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace; font-size: 12px; background: #f6f6f7; padding: 0.125rem 0.375rem; border-radius: 0.25rem;">{{ $variant?->sku ?? $product?->sku ?? '-' }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $sizeLabel ?? 'All sizes' }}</td>
                            <td style="padding: 0.625rem 1rem; text-align: right; font-weight: 700; color: {{ $onHand > 0 ? '#1a7a2e' : '#d72c0d' }};">{{ $onHand }}</td>
                            <td style="padding: 0.625rem 1rem; text-align: right; color: #616161;">{{ (int) $stock->reserved_quantity }}</td>
                            <td style="padding: 0.625rem 1rem; text-align: right; color: #303030;">{{ (int) $stock->available_quantity }}</td>
                            <td style="padding: 0.625rem 1rem; text-align: right; color: #616161;">{{ $everywhere }}</td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                                    <button type="button"
                                            onclick="kkAdjustLine({{ $stock->id }}, @js($label), @js($sizeLabel ?? 'All sizes'), {{ $onHand }})"
                                            class="btn btn-secondary" style="display: inline-flex; align-items: center; padding: 0.25rem 0.625rem; font-size: 12px; font-weight: 500; color: #303030; background: #fff; border: 1px solid #c9cccf; border-radius: 0.375rem; cursor: pointer; gap: 0.25rem;">
                                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Adjust
                                    </button>
                                    <form action="{{ route('admin.inventory.locations.stock.destroy', [$location, $stock]) }}" method="POST"
                                          onsubmit="return confirm('Remove {{ addslashes($label) }} from {{ addslashes($location->name) }}? Its {{ $onHand }} unit(s) come out of sellable stock too.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn" style="padding: 0; color: #d72c0d; font-size: 13px; font-weight: 500; background: none; border: none; cursor: pointer;">Remove</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 3rem 1rem; text-align: center; color: #616161; font-size: 13px;">
                                @if(request('search'))
                                    No products here match "{{ request('search') }}".
                                    <a href="{{ route('admin.inventory.locations.show', $location) }}" style="color: #005bd3; font-weight: 500; text-decoration: none; margin-left: 0.25rem;">Clear search</a>
                                @else
                                    Nothing is stocked at this location yet.
                                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-add-stock'))"
                                            class="btn" style="padding: 0; color: #005bd3; font-weight: 500; background: none; border: none; cursor: pointer; font-size: 13px;">Add a product</button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stocks->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $stocks->links() }}
            </div>
        @endif
    </div>

    {{-- Adjust one line --}}
    <div x-data="{ open: false, id: null, name: '', size: '', onHand: 0 }"
         x-on:open-adjust-line.window="open = true; id = $event.detail.id; name = $event.detail.name; size = $event.detail.size; onHand = $event.detail.onHand"
         x-show="open" x-cloak
         x-transition.opacity.duration.150ms
         x-effect="document.body.classList.toggle('kk-modal-open', open)"
         class="kk-modal">

        <div class="kk-modal__backdrop" x-on:click="open = false"></div>

        <div class="kk-modal__card">
            <div style="padding: 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h3 style="margin: 0; font-size: 14px; font-weight: 600; color: #303030;">Adjust stock at {{ $location->name }}</h3>
                    <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;"><span x-text="name"></span> &middot; <span x-text="size"></span></p>
                </div>
                <button type="button" x-on:click="open = false" class="btn-icon" style="background: none; border: none; cursor: pointer; padding: 0.25rem; color: #616161;">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div style="padding: 0.75rem 1rem; background: #f6f6f7; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 12px; color: #616161;">Held here</span>
                <span style="font-size: 13px; font-weight: 700; color: #303030;" x-text="onHand"></span>
            </div>

            <form method="POST" x-bind:action="'{{ $stockBase }}/' + id">
                @csrf
                @method('PUT')
                <input type="hidden" name="line_id" x-bind:value="id">

                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label for="line_type" class="form-label">Adjustment type <span style="color: #d72c0d;">*</span></label>
                        <select name="type" id="line_type" required class="form-select" style="width: 100%; font-size: 13px;">
                            <option value="add">Add stock</option>
                            <option value="remove">Remove stock</option>
                            <option value="set">Set stock to</option>
                        </select>
                    </div>
                    <div>
                        <label for="line_quantity" class="form-label">Quantity <span style="color: #d72c0d;">*</span></label>
                        <input type="number" name="quantity" id="line_quantity" min="0" max="1000000" step="1" inputmode="numeric" required
                               class="form-input" placeholder="0" style="width: 100%; font-size: 13px;">
                    </div>
                    <div>
                        <label for="line_reason" class="form-label">Reason</label>
                        <input type="text" name="reason" id="line_reason" maxlength="255" class="form-input"
                               placeholder="e.g. Restock, Damaged, Correction" style="width: 100%; font-size: 13px;">
                    </div>
                    <p style="font-size: 12px; color: #616161; margin: 0;">The product's sellable stock moves by the same amount.</p>
                </div>

                <div style="padding: 0.75rem 1rem; background: #f6f6f7; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; border-radius: 0 0 0.75rem 0.75rem;">
                    <button type="button" x-on:click="open = false" class="btn btn-secondary" style="font-size: 13px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save adjustment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function kkLocationPicker(products, restore) {
            return {
                products: products,
                // A rejected submission comes back with the picker open, so the
                // product it was about has to still be selected. Its options are
                // rendered by Blade, so this value has something to land on.
                productId: restore.productId,
                variantId: '',
                get variants() {
                    const chosen = this.products.find(p => String(p.id) === String(this.productId));

                    return chosen ? chosen.variants : [];
                },
                init() {
                    this.$watch('productId', () => { this.variantId = ''; });

                    // The size options come from x-for, which has not run yet -
                    // restoring the choice before they exist selects nothing.
                    this.$nextTick(() => { this.variantId = restore.variantId; });
                },
            };
        }

        function kkAdjustLine(id, name, size, onHand) {
            window.dispatchEvent(new CustomEvent('open-adjust-line', { detail: { id, name, size, onHand } }));
        }
    </script>
</x-layouts.admin>
