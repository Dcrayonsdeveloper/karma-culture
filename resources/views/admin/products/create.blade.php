<x-layouts.admin>
    <x-slot name="title">Add Product</x-slot>

    <div x-data="productForm()">
        <!-- Shopify-style top bar -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.products.index') }}" class="p-2 -m-1 rounded hover:bg-neutral-200 transition-colors" style="color: #616161;">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add product</h1>
            </div>
        </div>

        @include('admin.products.partials.save-errors')

        <form id="product-form" action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Two-column Shopify layout -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

                <!-- LEFT COLUMN (2/3) -->
                <div class="xl:col-span-2 space-y-4">

                    <!-- Title & Description -->
                    <div class="card p-5 space-y-4">
                        <div>
                            <label for="name" class="form-label form-label-required">Title</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                   minlength="2" maxlength="255"
                                   class="form-input w-full @error('name') form-input-error @enderror"
                                   placeholder="Short sleeve t-shirt"
                                   @input="if(!slugManual) slug = toSlug($event.target.value)">
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="short_description" class="form-label">Short description</label>
                            <textarea name="short_description" id="short_description" rows="2" maxlength="500"
                                      class="form-input w-full @error('short_description') form-input-error @enderror"
                                      placeholder="Brief product summary...">{{ old('short_description') }}</textarea>
                            @error('short_description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="description" class="form-label form-label-required">Description</label>
                            {{-- `required` removed: CKEditor 5 hides this textarea, and HTML5 validation on a hidden field silently blocks submit. Server validates instead. --}}
                            <textarea name="description" id="description" rows="6"
                                      class="form-input w-full @error('description') form-input-error @enderror">{{ old('description') }}</textarea>
                            @error('description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Media -->
                    <div class="card p-5" x-data="imageUploader()">
                        <h2 class="text-[13px] font-semibold mb-4" style="color: #303030;">Media</h2>

                        <!-- Main image upload -->
                        <div class="flex items-start gap-4 mb-4">
                            {{-- The preview crops, because the storefront crops. Showing the
                                 whole file in a square well was the friendlier picture and the
                                 wrong one: it told the admin their off-ratio shot was fine and
                                 the shop then cut its edges off, which is not discoverable from
                                 this screen at all. Same 3:4 box, same cover. --}}
                            <div x-show="mainPreview" x-transition class="kk-media kk-media--cover relative w-28 aspect-[3/4] rounded-lg overflow-hidden shrink-0" style="border: 2px solid #005bd3;">
                                <img :src="mainPreview" alt="Main image preview">
                                <button type="button" @click="removeMainImage()"
                                        class="absolute top-1 right-1 z-10 w-7 h-7 bg-white rounded-full flex items-center justify-center shadow-sm">
                                    <svg style="width: 0.875rem; height: 0.875rem; color: #d72c0d;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <span class="absolute bottom-0 left-0 right-0 z-10 px-2 py-0.5 text-[10px] font-semibold text-center text-white" style="background: rgba(0,91,211,0.85);">Main</span>
                            </div>

                            <div class="flex-1 border border-dashed rounded-lg p-5 text-center cursor-pointer transition-colors"
                                 style="border-color: #b5b5b5;"
                                 @click="$refs.mainFileInput.click()"
                                 @dragover.prevent="mainDragOver = true" @dragleave.prevent="mainDragOver = false"
                                 @drop.prevent="mainDragOver = false; handleMainImage($event.dataTransfer.files[0])"
                                 {{-- Object form, and the resting border colour is repeated here on
                                      purpose: a string :style replaces the whole attribute, and the
                                      object form clears any property it sets to '', so either way
                                      the static border-color above cannot be relied on. --}}
                                 :style="{ borderColor: mainDragOver ? '#005bd3' : '#b5b5b5', background: mainDragOver ? '#f0f6ff' : '' }">
                                <input type="file" name="main_image" accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                                       x-ref="mainFileInput" style="display: none;" @change="handleMainImage($event.target.files[0])">
                                <svg style="width: 1.5rem; height: 1.5rem; margin: 0 auto 0.5rem; color: #b5b5b5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-xs font-medium" style="color: #005bd3;">Add main image</p>
                                <p class="text-[11px] mt-0.5" style="color: #616161;">or drop file to upload</p>
                            </div>
                        </div>
                        @error('main_image') <p class="form-error mb-3">{{ $message }}</p> @enderror

                        <!-- Gallery upload -->
                        <div class="border border-dashed rounded-lg p-4 text-center cursor-pointer transition-colors"
                             style="border-color: #b5b5b5;"
                             @click="$refs.galleryInput.click()"
                             @dragover.prevent="galleryDragOver = true" @dragleave.prevent="galleryDragOver = false"
                             @drop.prevent="galleryDragOver = false; handleGalleryFiles($event.dataTransfer.files)"
                             :style="{ borderColor: galleryDragOver ? '#005bd3' : '#b5b5b5', background: galleryDragOver ? '#f0f6ff' : '' }">
                            <input type="file" name="images[]" multiple accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                                   x-ref="galleryInput" style="display: none;" @change="handleGalleryFiles($event.target.files)">
                            <p class="text-xs font-medium" style="color: #005bd3;">Add gallery images</p>
                            <p class="text-[11px]" style="color: #616161;">Up to 10 images, 2MB each</p>
                            {{-- Printed from the constant the storefront lays out with, so
                                 the advice and the crop cannot drift apart. --}}
                            @php $kkImgSize = \App\Models\Product::IMAGE_SIZE; @endphp
                            <p class="text-[11px] mt-1" style="color: #616161;">Recommended {{ $kkImgSize[0] }} &times; {{ $kkImgSize[1] }} px (3:4 portrait). Images are cropped to fill this shape on the storefront, so keep the product away from the edges.</p>
                        </div>
                        <div x-show="galleryPreviews.length > 0" x-transition class="mt-3">
                            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
                                <template x-for="(preview, index) in galleryPreviews" :key="index">
                                    <div class="kk-media relative group rounded-lg overflow-hidden aspect-square" style="border: 1px solid #e3e3e3;">
                                        <img class="kk-media__fill" :src="preview.url" alt="" aria-hidden="true">
                                        <img :src="preview.url" alt="Gallery image preview">
                                        <button type="button" @click="removeGalleryImage(index)"
                                                class="absolute top-1 right-1 z-10 w-7 h-7 bg-white rounded-full flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg style="width: 0.75rem; height: 0.75rem; color: #d72c0d;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                        @error('images') <p class="form-error mt-2">{{ $message }}</p> @enderror
                        @error('images.*') <p class="form-error mt-2">{{ $message }}</p> @enderror
                    </div>

                    <!-- Pricing -->
                    <div class="card p-5">
                        <h2 class="text-[13px] font-semibold mb-4" style="color: #303030;">Pricing</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="price" class="form-label form-label-required">Price</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px]" style="color: #616161;">₹</span>
                                    <input type="number" name="price" id="price" value="{{ old('price') }}" required step="0.01" min="0" max="9999999.99"
                                           class="form-input form-input-prefixed w-full @error('price') form-input-error @enderror">
                                </div>
                                @error('price') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="mrp" class="form-label">Compare-at price</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px]" style="color: #616161;">₹</span>
                                    <input type="number" name="mrp" id="mrp" value="{{ old('mrp') }}" step="0.01" min="0" max="9999999.99"
                                           class="form-input form-input-prefixed w-full @error('mrp') form-input-error @enderror">
                                </div>
                                @error('mrp') <p class="form-error">{{ $message }}</p> @enderror
                                {{-- Filled in by the compare-at guard below as the two prices are typed. --}}
                                <p class="form-error" id="mrp-compare-error" hidden></p>
                                <p class="form-hint" style="font-size:11px;color:#999;margin-top:4px;">Shown struck-through on the product page. Must be at least the Price.</p>
                            </div>
                            <div>
                                <label for="cost_price" class="form-label">Cost per item</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px]" style="color: #616161;">₹</span>
                                    <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price') }}" step="0.01" min="0" max="9999999.99"
                                           class="form-input form-input-prefixed w-full @error('cost_price') form-input-error @enderror">
                                </div>
                                @error('cost_price') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Inventory -->
                    <div class="card p-5">
                        <h2 class="text-[13px] font-semibold mb-4" style="color: #303030;">Inventory</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="sku" class="form-label form-label-required">SKU</label>
                                <input type="text" name="sku" id="sku" value="{{ old('sku') }}" required placeholder="FK-001"
                                       maxlength="50" pattern="[A-Za-z0-9._/\-]+"
                                       title="Letters, digits and . _ / - only, up to 50 characters."
                                       class="form-input w-full @error('sku') form-input-error @enderror">
                                @error('sku') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="barcode" class="form-label">Barcode (EAN/UPC)</label>
                                <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}"
                                       maxlength="50" pattern="[A-Za-z0-9\-]+"
                                       title="Letters, digits and hyphens only, up to 50 characters."
                                       class="form-input w-full @error('barcode') form-input-error @enderror">
                                @error('barcode') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="stock_quantity" class="form-label form-label-required">Quantity</label>
                                <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', 0) }}" required min="0" max="1000000" step="1"
                                       class="form-input w-full @error('stock_quantity') form-input-error @enderror">
                                @error('stock_quantity') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    @php
                        // A failed save bounces back to this page, so the table redraws from
                        // what was posted. Without this the admin loses every size they just
                        // typed because one of them tripped a rule.
                        $kkSizeRows = collect(old('variants', []))
                            ->filter(fn ($v) => is_array($v))
                            ->map(fn ($v) => [
                                'name' => (string) ($v['name'] ?? ''),
                                'price' => (string) ($v['price'] ?? ''),
                                'mrp' => (string) ($v['mrp'] ?? ''),
                                'stock_quantity' => (string) ($v['stock_quantity'] ?? '0'),
                                'sku' => (string) ($v['sku'] ?? ''),
                                'measurements' => (string) ($v['measurements'] ?? ''),
                                'is_active' => filter_var($v['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                            ])
                            ->values();
                        // Row-level rules fail under keys like `variants.2.sku`, which no
                        // single @error can catch - so the save bounced back silently and
                        // the page looked like it had simply ignored the button.
                        $kkSizeErrors = collect($errors->getMessages())
                            ->filter(fn ($messages, $key) => $key === 'variants' || str_starts_with($key, 'variants.'))
                            ->flatten();
                    @endphp
                    <!-- Sizes & pricing -->
                    <div class="card p-5" x-data="kkSizes()">
                        <div class="flex items-center justify-between mb-1">
                            <h2 class="text-[13px] font-semibold form-label-required" style="color: #303030;">Sizes &amp; pricing</h2>
                            <div style="display:flex; align-items:center; gap:6px;">
                                @include('admin.products.partials.preset-picker', ['type' => 'size'])
                                <button type="button" @click="add()" class="btn btn-secondary" style="font-size:12px; padding:4px 10px;">+ Add size</button>
                            </div>
                        </div>
                        <p class="text-xs mb-4" style="color: #616161;">Every product needs at least one size. Each row is one size a customer can buy. Leave Price or MRP blank and the row uses the product&rsquo;s. Measurements are optional and let the assistant advise on fit. Leave SKU blank and one is generated. Colours are set separately below.</p>

                        @if($kkSizeErrors->isNotEmpty())
                            <div class="mb-3">
                                @foreach($kkSizeErrors as $kkSizeError)
                                    <p class="form-error">{{ $kkSizeError }}</p>
                                @endforeach
                            </div>
                        @endif

                        <p class="text-xs" style="color:#616161; padding:10px 0;" x-show="rows.length === 0" x-cloak>At least one size is required - click &ldquo;Add size&rdquo;.</p>

                        <div style="overflow-x:auto;" x-show="rows.length > 0" x-cloak>
                            <table style="width:100%; font-size:13px; border-collapse:collapse;">
                                <thead>
                                    <tr style="border-bottom:1px solid #e3e3e3;">
                                        <th style="text-align:left;padding:.5rem;font-weight:500;color:#616161;">Size</th>
                                        <th style="text-align:right;padding:.5rem;font-weight:500;color:#616161;">Price</th>
                                        <th style="text-align:right;padding:.5rem;font-weight:500;color:#616161;">MRP</th>
                                        <th style="text-align:right;padding:.5rem;font-weight:500;color:#616161;">Stock</th>
                                        <th style="text-align:left;padding:.5rem;font-weight:500;color:#616161;">Measurements</th>
                                        <th style="text-align:left;padding:.5rem;font-weight:500;color:#616161;">SKU</th>
                                        <th style="text-align:center;padding:.5rem;font-weight:500;color:#616161;">Active</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(r, i) in rows" :key="r.uid">
                                        <tr style="border-bottom:1px solid #f1f1f1;">
                                            <td style="padding:.4rem;">
                                                <input type="text" x-bind:name="'variants[' + i + '][name]'" x-model="r.name" placeholder="M-40"
                                                       maxlength="100" aria-label="Size"
                                                       style="width:92px;font-size:12px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.25rem .5rem;">
                                            </td>
                                            <td style="padding:.4rem;text-align:right;">
                                                <input type="number" step="0.01" min="0" max="9999999.99" x-bind:name="'variants[' + i + '][price]'" x-model="r.price"
                                                       aria-label="Size price"
                                                       style="width:88px;font-size:12px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.25rem .5rem;text-align:right;">
                                            </td>
                                            <td style="padding:.4rem;text-align:right;">
                                                <input type="number" step="0.01" min="0" max="9999999.99" x-bind:name="'variants[' + i + '][mrp]'" x-model="r.mrp"
                                                       aria-label="Size MRP"
                                                       style="width:88px;font-size:12px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.25rem .5rem;text-align:right;">
                                            </td>
                                            <td style="padding:.4rem;text-align:right;">
                                                <input type="number" min="0" max="1000000" step="1" x-bind:name="'variants[' + i + '][stock_quantity]'" x-model="r.stock_quantity"
                                                       aria-label="Size stock"
                                                       style="width:68px;font-size:12px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.25rem .5rem;text-align:right;">
                                            </td>
                                            <td style="padding:.4rem;">
                                                <input type="text" x-bind:name="'variants[' + i + '][measurements]'" x-model="r.measurements"
                                                       placeholder="Chest 40in, Length 28in" maxlength="160" aria-label="Measurements"
                                                       style="width:170px;font-size:12px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.25rem .5rem;">
                                            </td>
                                            <td style="padding:.4rem;">
                                                <input type="text" x-bind:name="'variants[' + i + '][sku]'" x-model="r.sku" placeholder="auto"
                                                       maxlength="50" pattern="[A-Za-z0-9._/\-]+" aria-label="Size SKU"
                                                       title="Letters, digits and . _ / - only, up to 50 characters. Leave blank to generate one."
                                                       style="width:104px;font-size:12px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.25rem .5rem;">
                                            </td>
                                            <td style="padding:.4rem;text-align:center;">
                                                <input type="hidden" x-bind:name="'variants[' + i + '][is_active]'" value="0">
                                                <input type="checkbox" x-bind:name="'variants[' + i + '][is_active]'" value="1" x-model="r.is_active" class="form-checkbox">
                                            </td>
                                            <td style="padding:.4rem;text-align:center;">
                                                <button type="button" @click="rows.splice(i, 1)" title="Remove"
                                                        style="color:#d72c0d;background:none;border:0;cursor:pointer;font-size:16px;line-height:1;">&times;</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <script>
                        {{-- Nothing is saved yet, so a removed row is simply dropped: there is
                             no id to post back and no `delete` flag for the server to act on. --}}
                        function kkSizes() {
                            return {
                                seq: 0,
                                rows: [],
                                // "Pick from library" state - the sizes an admin saved once and
                                // can tick here instead of retyping. Only the name and any default
                                // measurements come across; price, stock and SKU stay per-product.
                                presets: @json($sizePresets ?? []),
                                pickerOpen: false,
                                pickedIds: {},
                                init() {
                                    this.rows = (@json($kkSizeRows)).map(r => ({ ...r, uid: ++this.seq }));
                                    // A size is required, so the form opens on a row to fill in
                                    // rather than on a button the admin has to find first.
                                    if (this.rows.length === 0) { this.add(); }
                                },
                                add() {
                                    this.rows.push({
                                        uid: ++this.seq, name: '', price: '', mrp: '',
                                        stock_quantity: '0', sku: '', measurements: '', is_active: true,
                                    });
                                },
                                addPreset(p) {
                                    // Ticking a size already on the form should not open a second
                                    // row for it, so a name that is already there is skipped.
                                    const has = this.rows.some(r => (r.name || '').trim().toLowerCase() === (p.name || '').trim().toLowerCase());
                                    if (has) { return false; }
                                    this.rows.push({
                                        uid: ++this.seq, name: p.name, price: '', mrp: '',
                                        stock_quantity: '0', sku: '', measurements: p.measurements || '', is_active: true,
                                    });
                                    return true;
                                },
                                applyPicker() {
                                    let added = 0;
                                    (this.presets || []).forEach(p => { if (this.pickedIds[p.id] && this.addPreset(p)) { added++; } });
                                    // Drop the blank opener row once real sizes are picked, so a
                                    // picked-only product does not bounce on an empty size row.
                                    if (added > 0) { this.rows = this.rows.filter(r => (r.name || '').trim() !== ''); }
                                    this.pickedIds = {};
                                    this.pickerOpen = false;
                                },
                            };
                        }
                    </script>

                    <!-- Colours -->
                    @php
                        // Colours live on the product, not on a size row, so a product can
                        // offer any colour in any size without one row per combination.
                        // Blank rows are kept rather than dropped: they are what the admin
                        // left behind, and a "name every colour" error has to point at a
                        // row that is still on the page.
                        $kkColourRows = collect(old('colours', []))
                            ->filter(fn ($c) => is_array($c))
                            ->map(fn ($c) => [
                                'name' => (string) ($c['name'] ?? ''),
                                // No hex posted means the swatch was never picked, so the row
                                // comes back unpicked instead of filled in with a colour the
                                // admin did not choose.
                                'hex' => trim((string) ($c['hex'] ?? '')),
                            ])
                            ->values();
                        $kkColourErrors = collect($errors->getMessages())
                            ->filter(fn ($messages, $key) => $key === 'colours' || str_starts_with($key, 'colours.'))
                            ->flatten();
                    @endphp
                    <div class="card p-5" x-data="kkColours()">
                        <div class="flex items-center justify-between mb-1">
                            <h2 class="text-[13px] font-semibold form-label-required" style="color: #303030;">Colours</h2>
                            <div style="display:flex; align-items:center; gap:6px;">
                                @include('admin.products.partials.preset-picker', ['type' => 'colour'])
                                <button type="button" @click="add()" class="btn btn-secondary" style="font-size:12px; padding:4px 10px;">+ Add colour</button>
                            </div>
                        </div>
                        <p class="text-xs mb-4" style="color: #616161;">Every product needs at least one colour, each with a name and a swatch you pick. They show as swatches on the product page, under the sizes.</p>

                        @if($kkColourErrors->isNotEmpty())
                            <div class="mb-3">
                                @foreach($kkColourErrors as $kkColourError)
                                    <p class="form-error">{{ $kkColourError }}</p>
                                @endforeach
                            </div>
                        @endif

                        <p class="text-xs" style="color:#616161; padding:6px 0;" x-show="rows.length === 0" x-cloak>At least one colour is required - click &ldquo;Add colour&rdquo;.</p>

                        <div x-show="rows.length > 0" x-cloak style="display:flex;flex-direction:column;gap:8px;">
                            <template x-for="(c, i) in rows" :key="c.uid">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    {{-- An unpicked row posts no hex at all, so no colour is ever
                                         saved with a swatch nobody chose. The picker sits
                                         invisibly over the placeholder, so one click on it opens
                                         the browser's colour picker. --}}
                                    <span style="position:relative;display:inline-flex;width:34px;height:32px;flex:0 0 auto;">
                                        <span x-show="!c.picked" aria-hidden="true"
                                              style="position:absolute;inset:0;border:1px dashed #8a8a8a;border-radius:.375rem;display:flex;align-items:center;justify-content:center;font-size:15px;line-height:1;color:#616161;background:repeating-linear-gradient(45deg,#fff,#fff 4px,#f1f1f1 4px,#f1f1f1 8px);">+</span>
                                        <input type="color" x-model="c.hex"
                                               @input="c.picked = true" @change="c.picked = true"
                                               x-bind:name="c.picked ? 'colours[' + i + '][hex]' : false"
                                               x-bind:title="c.picked ? 'Change this colour&rsquo;s swatch' : 'Pick this colour&rsquo;s swatch'"
                                               aria-label="Colour swatch"
                                               x-bind:style="c.picked
                                                   ? 'width:34px;height:32px;border:1px solid #d4d4d4;border-radius:.375rem;padding:0;background:none;cursor:pointer;'
                                                   : 'position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;'">
                                    </span>
                                    <input type="text" x-bind:name="'colours[' + i + '][name]'" x-model="c.name" placeholder="Navy"
                                           maxlength="60" aria-label="Colour name"
                                           style="flex:1 1 auto;max-width:240px;font-size:13px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.4rem .6rem;">
                                    <button type="button" @click="rows.splice(i, 1)" title="Remove"
                                            style="color:#d72c0d;background:none;border:0;cursor:pointer;font-size:16px;line-height:1;">&times;</button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <script>
                        // The browser's colour picker has to open on some colour. This off-grey
                        // is deliberately one nobody lands on by accident, so picking any real
                        // colour - black included - registers as a change and marks the row as
                        // chosen. Until then the row posts no hex and nothing is saved for it.
                        const KK_UNPICKED_SWATCH = '#7f7f81';
                        function kkColours() {
                            return {
                                seq: 0,
                                rows: [],
                                // "Pick from library" state - a picked colour comes in already
                                // swatched, so its row counts as chosen and posts its hex.
                                presets: @json($colourPresets ?? []),
                                pickerOpen: false,
                                pickedIds: {},
                                init() {
                                    this.rows = (@json($kkColourRows)).map(c => ({
                                        ...c,
                                        uid: ++this.seq,
                                        picked: !!c.hex,
                                        hex: c.hex || KK_UNPICKED_SWATCH,
                                    }));
                                    // A colour is required, so the form opens on a row to fill in.
                                    if (this.rows.length === 0) { this.add(); }
                                },
                                add() { this.rows.push({ uid: ++this.seq, name: '', hex: KK_UNPICKED_SWATCH, picked: false }); },
                                addPreset(p) {
                                    const has = this.rows.some(r => (r.name || '').trim().toLowerCase() === (p.name || '').trim().toLowerCase());
                                    if (has) { return false; }
                                    this.rows.push({ uid: ++this.seq, name: p.name, hex: p.hex, picked: true });
                                    return true;
                                },
                                applyPicker() {
                                    let added = 0;
                                    (this.presets || []).forEach(p => { if (this.pickedIds[p.id] && this.addPreset(p)) { added++; } });
                                    // Drop the blank opener row (no name, no swatch picked) once
                                    // real colours are picked.
                                    if (added > 0) { this.rows = this.rows.filter(r => (r.name || '').trim() !== '' || r.picked); }
                                    this.pickedIds = {};
                                    this.pickerOpen = false;
                                },
                            };
                        }
                    </script>

                    <!-- Textures -->
                    @php
                        // Textures live on the product, not on a size row, exactly as the
                        // colours above do - a texture is offered in every size the product
                        // ships in. A texture is a name and nothing else, so unlike a colour
                        // there is no swatch to carry back; an entry is tolerated in map form
                        // too, since hand-edited JSON has been found written that way.
                        // Blank rows are kept rather than dropped: they are what the admin
                        // left behind, and an error about one has to point at a row that is
                        // still on the page.
                        $kkTextureRows = collect(old('textures', []))
                            ->map(fn ($t) => is_array($t) ? (string) ($t['name'] ?? '') : (string) $t)
                            ->values();
                        // @error cannot name an indexed key like "textures.0", so the row
                        // messages are lifted out of the bag by hand and shown together.
                        $kkTextureErrors = collect($errors->getMessages())
                            ->filter(fn ($messages, $key) => $key === 'textures' || str_starts_with($key, 'textures.'))
                            ->flatten();
                    @endphp
                    <div class="card p-5" x-data="kkTextures()">
                        <div class="flex items-center justify-between mb-1">
                            <h2 class="text-[13px] font-semibold" style="color: #303030;">Textures</h2>
                            <div style="display:flex; align-items:center; gap:6px;">
                                @include('admin.products.partials.preset-picker', ['type' => 'texture'])
                                <button type="button" @click="add()" class="btn btn-secondary" style="font-size:12px; padding:4px 10px;">+ Add texture</button>
                            </div>
                        </div>
                        <p class="text-xs mb-4" style="color: #616161;">Optional - a texture is a name on its own, with no swatch to pick. They show on the product page under the colours, and become a filter on the shop.</p>

                        @if($kkTextureErrors->isNotEmpty())
                            <div class="mb-3">
                                @foreach($kkTextureErrors as $kkTextureError)
                                    <p class="form-error">{{ $kkTextureError }}</p>
                                @endforeach
                            </div>
                        @endif

                        <p class="text-xs" style="color:#616161; padding:6px 0;" x-show="rows.length === 0" x-cloak>No textures - click &ldquo;Add texture&rdquo; to offer one.</p>

                        <div x-show="rows.length > 0" x-cloak style="display:flex;flex-direction:column;gap:8px;">
                            <template x-for="(t, i) in rows" :key="t.uid">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <input type="text" x-bind:name="'textures[' + i + ']'" x-model="t.name" placeholder="Matte"
                                           maxlength="60" aria-label="Texture name"
                                           style="flex:1 1 auto;max-width:240px;font-size:13px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.4rem .6rem;">
                                    <button type="button" @click="rows.splice(i, 1)" title="Remove"
                                            style="color:#d72c0d;background:none;border:0;cursor:pointer;font-size:16px;line-height:1;">&times;</button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <script>
                        function kkTextures() {
                            return {
                                seq: 0,
                                rows: [],
                                presets: @json($texturePresets ?? []),
                                pickerOpen: false,
                                pickedIds: {},
                                init() {
                                    // uid, not the index, keys the x-for: removing a middle row
                                    // otherwise renumbers the rest and Alpine reuses the wrong
                                    // input for them.
                                    this.rows = (@json($kkTextureRows)).map(name => ({ uid: ++this.seq, name }));
                                },
                                add() { this.rows.push({ uid: ++this.seq, name: '' }); },
                                addPreset(p) {
                                    const has = this.rows.some(r => (r.name || '').trim().toLowerCase() === (p.name || '').trim().toLowerCase());
                                    if (has) { return false; }
                                    this.rows.push({ uid: ++this.seq, name: p.name });
                                    return true;
                                },
                                applyPicker() {
                                    let added = 0;
                                    (this.presets || []).forEach(p => { if (this.pickedIds[p.id] && this.addPreset(p)) { added++; } });
                                    // Textures are optional, so only blank rows added by hand are
                                    // pruned once a real one is picked.
                                    if (added > 0) { this.rows = this.rows.filter(r => (r.name || '').trim() !== ''); }
                                    this.pickedIds = {};
                                    this.pickerOpen = false;
                                },
                            };
                        }
                    </script>

                </div>

                <!-- RIGHT COLUMN (1/3) - Sidebar -->
                <div class="space-y-4">
                    <!-- Status -->
                    <div class="card p-5">
                        <h2 class="text-[13px] font-semibold mb-3" style="color: #303030;">Status</h2>
                        <select name="is_active" class="form-input w-full text-sm">
                            <option value="1" {{ old('is_active', true) ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !old('is_active', true) ? 'selected' : '' }}>Draft</option>
                        </select>
                        <label class="flex items-center gap-2 mt-3 cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }} class="form-checkbox">
                            <span class="text-[13px]" style="color: #303030;">Featured product</span>
                        </label>
                    </div>

                    <!-- Organization -->
                    <div class="card p-5 space-y-4">
                        <h2 class="text-[13px] font-semibold" style="color: #303030;">Organization</h2>
                        <div>
                            <label for="category_id" class="form-label form-label-required">Category</label>
                            <select name="category_id" id="category_id" required class="form-input w-full @error('category_id') form-input-error @enderror">
                                <option value="">Select</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->path_label ?? $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            {{-- The primary picker above answers "what is this product";
                                 this answers "where should it show". A unisex shirt sits
                                 on the men's and the women's shelf at once, and before
                                 this the admin had to pick one and lose the other.
                                 The primary is added on save, so it is not repeated here. --}}
                            <label class="form-label">Also show in</label>
                            <div style="max-height: 190px; overflow-y: auto; border: 1px solid #e3e3e3; border-radius: 0.5rem; padding: 0.5rem;">
                                @forelse($categories as $category)
                                    <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.2rem 0; font-size: 13px; cursor: pointer;">
                                        <input type="checkbox" name="extra_category_ids[]" value="{{ $category->id }}"
                                               style="width: 0.9rem; height: 0.9rem; accent-color: #303030;"
                                               @checked(in_array($category->id, old('extra_category_ids', $extraCategoryIds ?? [])))>
                                        <span>{{ $category->path_label ?? $category->name }}</span>
                                    </label>
                                @empty
                                    <p style="font-size: 12px; color: #616161;">No categories yet.</p>
                                @endforelse
                            </div>
                            @error('extra_category_ids') <p class="form-error">{{ $message }}</p> @enderror
                            @error('extra_category_ids.*') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="text-[12px] mt-1" style="color: #616161;">
                                Optional. The category above is always included. A parent category
                                also shows everything filed under it, so there is no need to tick both.
                            </p>
                        </div>
                        <div>
                            {{-- Categories say what the product is; a collection is a shelf
                                 someone assembled - Summer Picks, Festive Edit. The built-in
                                 New In / Bestsellers / Introductory Offer pages fill
                                 themselves from the catalogue and are not listed here
                                 because there is no list to add to. --}}
                            <label class="form-label">Collections</label>
                            <div style="max-height: 150px; overflow-y: auto; border: 1px solid #e3e3e3; border-radius: 0.5rem; padding: 0.5rem;">
                                @forelse($collections as $collection)
                                    <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.2rem 0; font-size: 13px; cursor: pointer;">
                                        <input type="checkbox" name="collection_ids[]" value="{{ $collection->id }}"
                                               style="width: 0.9rem; height: 0.9rem; accent-color: #303030;"
                                               @checked(in_array($collection->id, old('collection_ids', $selectedCollectionIds ?? [])))>
                                        <span>{{ $collection->name }}@unless($collection->is_active) <span style="color:#8a8a8a;">(hidden)</span>@endunless</span>
                                    </label>
                                @empty
                                    <p style="font-size: 12px; color: #616161;">
                                        No collections yet. Create one under Products &rarr; Collections.
                                    </p>
                                @endforelse
                            </div>
                            @error('collection_ids') <p class="form-error">{{ $message }}</p> @enderror
                            @error('collection_ids.*') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="brand_id" class="form-label">Brand</label>
                            <select name="brand_id" id="brand_id" class="form-input w-full @error('brand_id') form-input-error @enderror">
                                <option value="">Select</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                            @error('brand_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="seller_id" class="form-label">Seller</label>
                            <select name="seller_id" id="seller_id" class="form-input w-full @error('seller_id') form-input-error @enderror">
                                <option value="">Select</option>
                                @foreach($sellers as $seller)
                                    <option value="{{ $seller->id }}" {{ old('seller_id') == $seller->id ? 'selected' : '' }}>{{ $seller->store_name }}</option>
                                @endforeach
                            </select>
                            @error('seller_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="slug" class="form-label">URL handle</label>
                            <input type="text" name="slug" id="slug" x-model="slug" placeholder="auto-generated"
                                   maxlength="255" pattern="[a-z0-9]+(-[a-z0-9]+)*"
                                   title="Lower-case letters, digits and single hyphens, e.g. short-sleeve-t-shirt."
                                   class="form-input w-full @error('slug') form-input-error @enderror"
                                   @input="slugManual = ($event.target.value.trim() !== '')">
                            @error('slug') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="card p-5 space-y-4">
                        <h2 class="text-[13px] font-semibold" style="color: #303030;">Search engine listing</h2>
                        <div>
                            <label for="meta_title" class="form-label">Page title</label>
                            <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title') }}" maxlength="255"
                                   class="form-input w-full @error('meta_title') form-input-error @enderror" placeholder="Product name">
                            @error('meta_title') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="meta_description" class="form-label">Meta description</label>
                            <textarea name="meta_description" id="meta_description" rows="3" maxlength="500"
                                      class="form-input w-full @error('meta_description') form-input-error @enderror"
                                      placeholder="SEO description...">{{ old('meta_description') }}</textarea>
                            @error('meta_description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div class="flex items-center justify-end gap-2 mt-5 pt-4" style="border-top: 1px solid #e3e3e3;">
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary text-[13px]">Discard</a>
                <button type="submit" class="btn btn-primary text-[13px]">Save product</button>
            </div>
        </form>
    </div>

    @push('styles')
    <style>
        .ck-editor__editable { min-height: 180px; }
        .ck.ck-editor__main>.ck-editor__editable:not(.ck-focused) { border-color: #d4d4d4; }
        .ck.ck-editor__main>.ck-editor__editable.ck-focused { border-color: #005bd3; box-shadow: 0 0 0 1px #005bd3; }
        /* group-hover only fires where a pointer can hover, so on a touch screen the
           tile controls would never appear; there they stay visible instead. */
        @media (hover: none) { .kk-media > .opacity-0 { opacity: 1; } }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        function productForm() {
            return {
                slug: '{{ old("slug", "") }}',
                slugManual: {{ old('slug') ? 'true' : 'false' }},
                toSlug(text) {
                    return text.toLowerCase().trim().replace(/[^\w\s-]/g, '').replace(/[\s_]+/g, '-').replace(/-+/g, '-').replace(/^-+|-+$/g, '');
                }
            };
        }

        // Gallery previews used to be appended from an async FileReader callback, so
        // the "max 10" guard read a count that was still zero while the loop ran and
        // a 20-file selection attached all 20. Object URLs are created synchronously,
        // so the preview list and the FileList that actually gets submitted now grow
        // in step, the cap bites on the first pick, and a preview's index still
        // matches its file's index when a tile is removed.
        const GALLERY_MAX = 10;
        const IMAGE_MAX_BYTES = 2 * 1024 * 1024;
        const IMAGE_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];

        function imageUploader() {
            return {
                mainPreview: null,
                mainDragOver: false,
                galleryPreviews: [],
                galleryDragOver: false,
                galleryFileList: new DataTransfer(),
                galleryMax: GALLERY_MAX,
                handleMainImage(file) {
                    if (!file) return;
                    // Dropping a file bypasses the input's accept list, so re-check the type.
                    if (!IMAGE_TYPES.includes(file.type)) { if (window.toastr) toastr.error(file.name + ' is not a JPG, PNG, WEBP or GIF.'); return; }
                    if (file.size > IMAGE_MAX_BYTES) { if (window.toastr) toastr.error(file.name + ' exceeds 2MB.'); return; }
                    const dt = new DataTransfer(); dt.items.add(file);
                    this.$refs.mainFileInput.files = dt.files;
                    const reader = new FileReader();
                    reader.onload = (e) => { this.mainPreview = e.target.result; };
                    reader.readAsDataURL(file);
                },
                removeMainImage() { this.mainPreview = null; this.$refs.mainFileInput.value = ''; },
                handleGalleryFiles(files) {
                    let overCap = 0;
                    for (const file of files) {
                        if (!IMAGE_TYPES.includes(file.type)) { if (window.toastr) toastr.error(file.name + ' is not a JPG, PNG, WEBP or GIF.'); continue; }
                        if (file.size > IMAGE_MAX_BYTES) { if (window.toastr) toastr.error(file.name + ' exceeds 2MB.'); continue; }
                        // Count what is attached to the input, not what has been previewed.
                        if (this.galleryFileList.items.length >= GALLERY_MAX) { overCap++; continue; }
                        this.galleryFileList.items.add(file);
                        this.galleryPreviews.push({ url: URL.createObjectURL(file), name: file.name });
                    }
                    this.$refs.galleryInput.files = this.galleryFileList.files;
                    if (overCap > 0 && window.toastr) {
                        toastr.error('Only ' + GALLERY_MAX + ' gallery images allowed - ' + overCap + (overCap === 1 ? ' was' : ' were') + ' left out.');
                    }
                },
                removeGalleryImage(index) {
                    URL.revokeObjectURL(this.galleryPreviews[index].url);
                    this.galleryPreviews.splice(index, 1);
                    this.galleryFileList.items.remove(index);
                    this.$refs.galleryInput.files = this.galleryFileList.files;
                }
            };
        }

        ClassicEditor.create(document.querySelector('#description'), {
            toolbar: ['heading', '|', 'bold', 'italic', 'underline', '|', 'bulletedList', 'numberedList', '|', 'link', 'blockQuote', '|', 'undo', 'redo'],
            heading: { options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
                { model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
                { model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' },
            ]}
        }).catch(error => console.error(error));
    </script>

    @include('admin.products.partials.price-guard')
    @endpush
</x-layouts.admin>
