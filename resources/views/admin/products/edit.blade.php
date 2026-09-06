<x-layouts.admin>
    <x-slot name="title">Edit {{ $product->name }}</x-slot>

    <div x-data="productForm()">
        <!-- Shopify-style top bar with breadcrumb + actions -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('admin.products.index') }}" class="shrink-0 p-2 -m-1 rounded hover:bg-neutral-200 transition-colors" style="color: #616161;">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 style="font-size: 1.125rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #303030;">{{ $product->name }}</h1>
                <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-neutral' }} shrink-0">{{ $product->is_active ? 'Active' : 'Draft' }}</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('product.show', $product) }}" target="_blank" class="btn btn-secondary text-[13px]">View on site</a>
            </div>
        </div>

        @include('admin.products.partials.save-errors', ['title' => 'Your changes were not saved'])

        <form id="product-form" action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Two-column Shopify layout -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

                <!-- LEFT COLUMN (2/3) -->
                <div class="xl:col-span-2 space-y-4">

                    <!-- Title & Description -->
                    <div class="card p-5 space-y-4">
                        <div>
                            <label for="name" class="form-label form-label-required">Title</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required
                                   minlength="2" maxlength="255"
                                   class="form-input w-full @error('name') form-input-error @enderror"
                                   @input="if(!slugManual) slug = toSlug($event.target.value)">
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="short_description" class="form-label">Short description</label>
                            <textarea name="short_description" id="short_description" rows="2" maxlength="500"
                                      class="form-input w-full @error('short_description') form-input-error @enderror">{{ old('short_description', $product->short_description) }}</textarea>
                            @error('short_description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="description" class="form-label form-label-required">Description</label>
                            {{-- `required` removed: CKEditor hides this textarea so HTML5 validation silently blocks submit. Server validates instead. --}}
                            {{-- Escaped, not raw. The description is stored exactly as
                                 submitted (validated only as string|max:65535, and the CSV
                                 import writes it too), so printing it raw let a stored
                                 `</textarea><script>` close the field and run in the
                                 admin's session. The HTML parser decodes the entities when
                                 reading .value, so CKEditor still loads the real markup. --}}
                            <textarea name="description" id="description" rows="6"
                                      class="form-input w-full @error('description') form-input-error @enderror">{{ old('description', $product->description) }}</textarea>
                            @error('description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Media (images + videos, drag to reorder) -->
                    <div class="card p-5" x-data="imageManager('{{ route('admin.products.images.reorder', $product) }}')">
                        <h2 class="text-[13px] font-semibold mb-1" style="color: #303030;">Media</h2>
                        <p class="text-xs mb-3" style="color: #616161;">Images &amp; videos. <strong>Drag tiles to reorder</strong> (saved instantly). The tile marked "Main" is the primary image.</p>

                        @php $allMedia = $product->images->sortBy('position'); @endphp

                        <!-- Existing media grid (sortable) -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mb-3" x-ref="mediaList">
                            @foreach($allMedia as $image)
                            {{-- Contained, so the tile shows the file as uploaded rather than a
                                 centre crop of it. The admin layout carries no delegated error
                                 handler (that lives in the storefront layout), so a saved file
                                 that has since gone missing marks its own frame instead of
                                 leaving a blank square that still looks like real media. --}}
                            <div class="kk-media {{ $image->is_video ? 'kk-media--dark' : '' }} relative group rounded-lg overflow-hidden aspect-square media-tile"
                                 style="border: 1px solid #e3e3e3; cursor: grab;"
                                 draggable="true" data-id="{{ $image->id }}"
                                 @dragstart="onDragStart($event)" @dragover.prevent="onDragOver($event)"
                                 @drop.prevent="onDrop($event)" @dragend="onDragEnd()"
                                 x-show="!deletedIds.includes({{ $image->id }})">
                                @if($image->is_video)
                                    <video class="kk-media__fill" src="{{ $image->display_url }}" muted playsinline preload="metadata" aria-hidden="true" tabindex="-1" onerror="this.remove()"></video>
                                    <video src="{{ $image->display_url }}" muted playsinline preload="metadata" onerror="this.closest('.kk-media').classList.add('is-broken')"></video>
                                    <span class="absolute top-1.5 left-1.5 z-10 px-1.5 py-0.5 text-[10px] font-semibold rounded text-white" style="background: rgba(0,0,0,0.7);">&#9654; Video</span>
                                @else
                                    <img class="kk-media__fill" src="{{ $image->display_url }}" alt="" aria-hidden="true" onerror="this.remove()">
                                    <img src="{{ $image->display_url }}" alt="{{ $image->alt_text }}" onerror="this.closest('.kk-media').classList.add('is-broken')">
                                    @if($image->is_primary)
                                        <span class="absolute bottom-0 left-0 right-0 z-10 px-2 py-1 text-[10px] font-semibold text-center text-white" style="background: rgba(0,91,211,0.85);">Main</span>
                                    @endif
                                @endif
                                <span class="kk-media__fallback" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                                        <circle cx="8.5" cy="9.5" r="1.5"/>
                                        <path d="M21 15l-5-5L5 20"/>
                                    </svg>
                                    File missing
                                </span>
                                {{-- Touch screens send no HTML5 drag events, so a tile can also step one place with a tap. --}}
                                <div class="absolute top-1.5 right-1.5 z-10 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button type="button" @click="moveTile($el, -1)" title="Move earlier" aria-label="Move earlier"
                                            class="w-7 h-7 bg-white rounded-full flex items-center justify-center shadow-sm">
                                        <svg style="width: 0.875rem; height: 0.875rem; color: #303030;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button type="button" @click="moveTile($el, 1)" title="Move later" aria-label="Move later"
                                            class="w-7 h-7 bg-white rounded-full flex items-center justify-center shadow-sm">
                                        <svg style="width: 0.875rem; height: 0.875rem; color: #303030;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    <button type="button" @click="markForDelete({{ $image->id }})"
                                            class="w-7 h-7 bg-white rounded-full flex items-center justify-center shadow-sm">
                                        <svg style="width: 0.875rem; height: 0.875rem; color: #d72c0d;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                            @endforeach

                            <!-- New image previews -->
                            <template x-for="(preview, index) in galleryPreviews" :key="'img'+index">
                                <div class="kk-media relative group rounded-lg overflow-hidden aspect-square" style="border: 1px solid #e3e3e3;">
                                    <img class="kk-media__fill" :src="preview.url" alt="" aria-hidden="true">
                                    <img :src="preview.url" alt="New image preview">
                                    <span class="absolute top-1.5 left-1.5 z-10 px-1.5 py-0.5 text-[10px] font-semibold rounded text-white" style="background: #2a9d3e;">New</span>
                                    <button type="button" @click="removeGalleryImage(index)" class="absolute top-1.5 right-1.5 z-10 w-7 h-7 bg-white rounded-full flex items-center justify-center shadow-sm">
                                        <svg style="width: 0.875rem; height: 0.875rem; color: #d72c0d;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                            <!-- New video previews -->
                            <template x-for="(preview, index) in videoPreviews" :key="'vid'+index">
                                <div class="kk-media kk-media--dark relative group rounded-lg overflow-hidden aspect-square" style="border: 1px solid #e3e3e3;">
                                    <video class="kk-media__fill" :src="preview.url" muted playsinline aria-hidden="true" tabindex="-1"></video>
                                    <video :src="preview.url" muted playsinline></video>
                                    <span class="absolute top-1.5 left-1.5 z-10 px-1.5 py-0.5 text-[10px] font-semibold rounded text-white" style="background: #2a9d3e;">New &#9654;</span>
                                    <button type="button" @click="removeVideo(index)" class="absolute top-1.5 right-1.5 z-10 w-7 h-7 bg-white rounded-full flex items-center justify-center shadow-sm">
                                        <svg style="width: 0.875rem; height: 0.875rem; color: #d72c0d;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <!-- Hidden delete inputs -->
                        <template x-for="id in deletedIds" :key="id">
                            <input type="hidden" name="delete_images[]" :value="id">
                        </template>

                        <!-- Upload zones -->
                        <div class="flex flex-wrap gap-3">
                            {{-- Every zone cancels the drag events. Without that the browser
                                 handles the drop itself and navigates away from the form,
                                 taking any unsaved edit with it. --}}
                            <div class="flex-1 min-w-[45%] border border-dashed rounded-lg p-3 text-center cursor-pointer hover:border-neutral-400 transition-colors" style="border-color: #b5b5b5;"
                                 @click="$refs.mainFileInput.click()"
                                 @dragover.prevent @dragleave.prevent
                                 @drop.prevent="handleMainImage($event.dataTransfer.files[0])">
                                <input type="file" name="main_image" accept="image/jpeg,image/jpg,image/png,image/webp,image/gif" x-ref="mainFileInput" style="display: none;" @change="handleMainImage($event.target.files[0])">
                                <p class="text-xs font-medium" style="color: #005bd3;" x-text="mainImageChanged ? 'Main image ready ✓' : 'Set / replace main image'">Set / replace main image</p>
                                <p class="text-[11px] mt-0.5" style="color: #616161;">JPG, PNG, WEBP or GIF, max 2MB</p>
                            </div>
                            <div class="flex-1 min-w-[45%] border border-dashed rounded-lg p-3 text-center cursor-pointer hover:border-neutral-400 transition-colors" style="border-color: #b5b5b5;"
                                 @click="$refs.galleryInput.click()"
                                 @dragover.prevent @dragleave.prevent
                                 @drop.prevent="handleGalleryFiles($event.dataTransfer.files)">
                                <input type="file" name="images[]" multiple accept="image/jpeg,image/jpg,image/png,image/webp,image/gif" x-ref="galleryInput" style="display: none;" @change="handleGalleryFiles($event.target.files)">
                                <p class="text-xs font-medium" style="color: #005bd3;">Add images</p>
                                <p class="text-[11px] mt-0.5" style="color: #616161;">Up to 10 per save, JPG/PNG/WEBP/GIF, max 2MB each</p>
                                {{-- Printed from the constant the storefront lays out with, so
                                     the advice and the crop cannot drift apart. --}}
                                @php $kkImgSize = \App\Models\Product::IMAGE_SIZE; @endphp
                                <p class="text-[11px] mt-1" style="color: #616161;">Recommended {{ $kkImgSize[0] }} &times; {{ $kkImgSize[1] }} px (3:4 portrait). Images are cropped to fill this shape on the storefront, so keep the product away from the edges.</p>
                            </div>
                            <div class="flex-1 min-w-[45%] border border-dashed rounded-lg p-3 text-center cursor-pointer hover:border-neutral-400 transition-colors" style="border-color: #b5b5b5;"
                                 @click="$refs.videoInput.click()"
                                 @dragover.prevent @dragleave.prevent
                                 @drop.prevent="handleVideoFiles($event.dataTransfer.files)">
                                <input type="file" name="videos[]" multiple accept="video/mp4,video/webm,video/quicktime" x-ref="videoInput" style="display: none;" @change="handleVideoFiles($event.target.files)">
                                <p class="text-xs font-medium" style="color: #005bd3;">Add videos</p>
                                <p class="text-[11px] mt-0.5" style="color: #616161;">Up to 5 per save, MP4/WEBM/MOV, max 50MB each</p>
                            </div>
                        </div>
                        @error('main_image') <p class="form-error mt-2">{{ $message }}</p> @enderror
                        {{-- The array-level max:10 / max:5 rules report under `images` and
                             `videos`; without these the save was rejected in silence. --}}
                        @error('images') <p class="form-error mt-2">{{ $message }}</p> @enderror
                        @error('images.*') <p class="form-error mt-2">{{ $message }}</p> @enderror
                        @error('videos') <p class="form-error mt-2">{{ $message }}</p> @enderror
                        @error('videos.*') <p class="form-error mt-2">{{ $message }}</p> @enderror
                    </div>

                    <!-- Pricing -->
                    <div class="card p-5">
                        <h2 class="text-[13px] font-semibold mb-4" style="color: #303030;">Pricing</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="price" class="form-label form-label-required">Price</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px]" style="color: #616161;">₹</span>
                                    <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" required
                                           step="0.01" min="0" max="9999999.99" class="form-input form-input-prefixed w-full @error('price') form-input-error @enderror">
                                </div>
                                @error('price') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="mrp" class="form-label">Compare-at price</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px]" style="color: #616161;">₹</span>
                                    <input type="number" name="mrp" id="mrp" value="{{ old('mrp', $product->mrp) }}"
                                           step="0.01" min="0" max="9999999.99" class="form-input form-input-prefixed w-full @error('mrp') form-input-error @enderror">
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
                                    <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price', $product->cost_price) }}"
                                           step="0.01" min="0" max="9999999.99" class="form-input form-input-prefixed w-full @error('cost_price') form-input-error @enderror">
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
                                <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" required
                                       maxlength="50" pattern="[A-Za-z0-9._/\-]+"
                                       title="Letters, digits and . _ / - only, up to 50 characters."
                                       class="form-input w-full @error('sku') form-input-error @enderror">
                                @error('sku') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="barcode" class="form-label">Barcode (EAN/UPC)</label>
                                <input type="text" name="barcode" id="barcode" value="{{ old('barcode', $product->barcode) }}"
                                       maxlength="50" pattern="[A-Za-z0-9\-]+"
                                       title="Letters, digits and hyphens only, up to 50 characters."
                                       class="form-input w-full @error('barcode') form-input-error @enderror">
                                @error('barcode') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="stock_quantity" class="form-label form-label-required">Quantity</label>
                                <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" required
                                       min="0" max="1000000" step="1" class="form-input w-full @error('stock_quantity') form-input-error @enderror">
                                @error('stock_quantity') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Shipping -->
                    <div class="card p-5">
                        <h2 class="text-[13px] font-semibold mb-1" style="color: #303030;">Shipping</h2>
                        <p class="text-xs mb-4" style="color: #616161;">Used by Shiprocket to calculate shipping rates</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div>
                                <label for="weight" class="form-label">Weight (kg)</label>
                                <input type="number" name="weight" id="weight" value="{{ old('weight', $product->weight) }}"
                                       step="0.01" min="0" max="999999.99" class="form-input w-full @error('weight') form-input-error @enderror"
                                       placeholder="0.5">
                                @error('weight') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="length" class="form-label">Length (cm)</label>
                                <input type="number" name="length" id="length" value="{{ old('length', $product->length) }}"
                                       step="0.1" min="0" max="999999.99" class="form-input w-full @error('length') form-input-error @enderror"
                                       placeholder="10">
                                @error('length') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="width" class="form-label">Width (cm)</label>
                                <input type="number" name="width" id="width" value="{{ old('width', $product->width) }}"
                                       step="0.1" min="0" max="999999.99" class="form-input w-full @error('width') form-input-error @enderror"
                                       placeholder="10">
                                @error('width') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="height" class="form-label">Height (cm)</label>
                                <input type="number" name="height" id="height" value="{{ old('height', $product->height) }}"
                                       step="0.1" min="0" max="999999.99" class="form-input w-full @error('height') form-input-error @enderror"
                                       placeholder="10">
                                @error('height') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label for="hsn_code" class="form-label">HSN code</label>
                                <input type="text" name="hsn_code" id="hsn_code" value="{{ old('hsn_code', $product->hsn_code) }}"
                                       inputmode="numeric" maxlength="8" pattern="[0-9]{4,8}"
                                       title="An HSN code is 4, 6 or 8 digits."
                                       class="form-input w-full @error('hsn_code') form-input-error @enderror"
                                       placeholder="e.g. 6109">
                                @error('hsn_code') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">&nbsp;</label>
                                <label class="flex items-center gap-2 cursor-pointer mt-1">
                                    <input type="checkbox" name="is_taxable" value="1" {{ old('is_taxable', $product->is_taxable) ? 'checked' : '' }} class="form-checkbox">
                                    <span class="text-[13px]" style="color: #303030;">Charge tax on this product</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Variants -->
                    @php
                        // Existing sizes, shaped for the Alpine table above - or, when a save
                        // bounced back, the rows the admin had just typed. Redrawing from the
                        // database there would throw away their work on the way to showing
                        // them the error.
                        $kkSizeRows = old('variants') !== null
                            ? collect(old('variants'))
                                ->filter(fn ($v) => is_array($v))
                                ->map(fn ($v) => [
                                    'id' => ($v['id'] ?? '') !== '' ? (int) $v['id'] : null,
                                    'name' => (string) ($v['name'] ?? ''),
                                    'price' => (string) ($v['price'] ?? ''),
                                    'mrp' => (string) ($v['mrp'] ?? ''),
                                    'stock_quantity' => (string) ($v['stock_quantity'] ?? '0'),
                                    'sku' => (string) ($v['sku'] ?? ''),
                                    'measurements' => (string) ($v['measurements'] ?? ''),
                                    'is_active' => filter_var($v['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                                    'remove' => filter_var($v['delete'] ?? false, FILTER_VALIDATE_BOOLEAN),
                                ])
                                ->values()
                            : $product->variants->map(fn ($v) => [
                                'id' => $v->id,
                                'name' => $v->name,
                                'price' => (string) $v->price,
                                'mrp' => (string) $v->mrp,
                                'stock_quantity' => (int) $v->stock_quantity,
                                'sku' => $v->sku,
                                'measurements' => data_get($v->attributes, 'measurements', ''),
                                'is_active' => (bool) $v->is_active,
                                'remove' => false,
                            ])->values();
                        // Row-level rules fail under keys like `variants.2.sku`, which no
                        // single @error can catch - so the save bounced back silently and
                        // the page looked like it had simply ignored the button.
                        $kkSizeErrors = collect($errors->getMessages())
                            ->filter(fn ($messages, $key) => $key === 'variants' || str_starts_with($key, 'variants.'))
                            ->flatten();
                        // The master Sizes list, as plain names. A size row still posts and
                        // still saves its own copy of the label, so this list only decides
                        // what the picker offers - renaming or deleting a master row later
                        // never reaches a product, an order or a cart line.
                        $kkSizeNames = $sizeOptions->pluck('name')->values();
                    @endphp
                    <!-- Sizes & pricing -->
                    <div class="card p-5" x-data="kkSizes()">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-baseline gap-2">
                                <h2 class="text-[13px] font-semibold form-label-required" style="color: #303030;">Sizes &amp; pricing</h2>
                                {{-- The picker can only offer what the master list holds, so
                                     "the size I need is not there" needs an answer that is one
                                     click away. New tab on purpose: this form is half filled in
                                     and navigating away from it loses everything typed so far. --}}
                                <a href="{{ route('admin.sizes.index') }}" target="_blank" rel="noopener"
                                   class="text-[11px]" style="color: #616161;">Manage sizes</a>
                            </div>
                            <button type="button" @click="add()" class="btn btn-secondary" style="font-size:12px; padding:4px 10px;">+ Add size</button>
                        </div>
                        <p class="text-xs mb-4" style="color: #616161;">Every product needs at least one size. Each row is one size a customer can buy, with its own price and stock, picked from the sizes you keep under Products &rarr; Sizes. Measurements are optional and let the assistant advise on fit. Leave SKU blank and one is generated. Colours are set separately below.</p>

                        @if($kkSizeErrors->isNotEmpty())
                            <div class="mb-3">
                                @foreach($kkSizeErrors as $kkSizeError)
                                    <p class="form-error">{{ $kkSizeError }}</p>
                                @endforeach
                            </div>
                        @endif

                        <p class="text-xs" style="color:#616161; padding:10px 0;" x-show="visibleCount() === 0" x-cloak>At least one size is required - click &ldquo;Add size&rdquo;.</p>

                        <div style="overflow-x:auto;" x-show="visibleCount() > 0" x-cloak>
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
                                        <tr style="border-bottom:1px solid #f1f1f1;" x-show="!r.remove">
                                            <td style="padding:.4rem;">
                                                <input type="hidden" x-bind:name="'variants[' + i + '][id]'" x-bind:value="r.id || ''">
                                                <input type="hidden" x-bind:name="'variants[' + i + '][delete]'" x-bind:value="r.remove ? 1 : ''">
                                                {{-- A shop that has not filled its Sizes list in yet gets the
                                                     free-text box back, not an empty dropdown: locking the admin
                                                     out of editing a product until they have visited another
                                                     screen would be a worse form than the one this replaces. The
                                                     branch is plain Blade because the list cannot change while
                                                     the page is open - only the rows can. --}}
                                                @if($kkSizeNames->isNotEmpty())
                                                    {{-- Same name, same aria-label, same posted value as the text
                                                         box it replaces: nothing downstream can tell the
                                                         difference, and the compare-at guard still finds this row
                                                         by its aria-labels. The options are drawn by Alpine so a
                                                         row added after load gets the list too, and optionsFor()
                                                         carries whatever the row already holds - which on this
                                                         form is usually a size saved long before the list
                                                         existed. --}}
                                                    <select x-bind:name="'variants[' + i + '][name]'" x-model="r.name"
                                                            aria-label="Size"
                                                            x-init="$nextTick(() => { $el.value = r.name })"
                                                            style="width:120px;font-size:12px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.25rem .5rem;background:#fff;">
                                                        <option value="" disabled>Select a size</option>
                                                        <template x-for="opt in optionsFor(r.name)" :key="opt">
                                                            <option x-bind:value="opt" x-text="labelFor(opt)"></option>
                                                        </template>
                                                    </select>
                                                @else
                                                    <input type="text" x-bind:name="'variants[' + i + '][name]'" x-model="r.name" placeholder="M-40"
                                                           maxlength="100" aria-label="Size"
                                                           style="width:92px;font-size:12px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.25rem .5rem;">
                                                @endif
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
                                                <button type="button" @click="drop(i)" title="Remove" class="btn-icon"
                                                        style="color:#d72c0d;background:none;border:0;cursor:pointer;font-size:16px;line-height:1;">&times;</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        {{-- Rows removed from the table still post their id so the server deletes them. --}}
                        <template x-for="(r, i) in rows" :key="'del-' + r.uid">
                            <span>
                                <template x-if="r.remove && r.id">
                                    <span>
                                        <input type="hidden" x-bind:name="'variants[' + i + '][id]'" x-bind:value="r.id">
                                        <input type="hidden" x-bind:name="'variants[' + i + '][delete]'" value="1">
                                    </span>
                                </template>
                            </span>
                        </template>
                    </div>
                    <script>
                        // The admin's own Sizes list, and only what the picker offers with:
                        // the server still accepts any size string it is sent, because
                        // product imports, the API, the seeders and 1,266 live products
                        // already carry values that are on no list at all. A Rule::in here
                        // would start refusing the catalogue that exists.
                        const KK_SIZES = @json($kkSizeNames);
                        function kkSizes() {
                            return {
                                seq: 0,
                                rows: [],
                                init() {
                                    this.rows = (@json($kkSizeRows)).map(r => ({ ...r, uid: ++this.seq, remove: !!r.remove }));
                                    // A size is required, so a product that has none opens on a
                                    // row to fill in rather than on a button to find.
                                    if (this.visibleCount() === 0) { this.add(); }
                                },
                                visibleCount() { return this.rows.filter(r => !r.remove).length; },
                                add() {
                                    this.rows.push({
                                        uid: ++this.seq, id: null, name: '',
                                        price: @json((string) $product->price), mrp: @json((string) $product->mrp),
                                        stock_quantity: 0, sku: '', measurements: '', is_active: true, remove: false,
                                    });
                                },
                                drop(i) {
                                    if (this.rows[i].id) { this.rows[i].remove = true; } else { this.rows.splice(i, 1); }
                                },
                                // A row already holding a size that is not on the master list -
                                // most of this catalogue, until the lists are filled in - keeps
                                // it at the top of that row's own dropdown, so opening a product
                                // and saving it cannot rewrite the size a customer is wearing.
                                optionsFor(v) { return KK_SIZES.includes(v) ? KK_SIZES : (v ? [v, ...KK_SIZES] : KK_SIZES); },
                                labelFor(v) { return KK_SIZES.includes(v) ? v : v + ' — not in list'; },
                            };
                        }
                    </script>

                    <!-- Colours -->
                    @php
                        // Colours live on the product, not on a size row, so a product can
                        // offer any colour in any size without one row per combination.
                        // The colours the admin just typed when a save bounced back, otherwise
                        // the ones on the product. A colour stored as a bare name has no
                        // swatch: it comes back unpicked, so the admin chooses one rather
                        // than the page inventing a black for it.
                        $kkColourRows = collect(old('colours', data_get($product->attributes, 'Colours', [])))
                            ->map(fn ($c) => is_array($c)
                                ? ['name' => (string) ($c['name'] ?? ''), 'hex' => trim((string) ($c['hex'] ?? ''))]
                                : ['name' => (string) $c, 'hex' => ''])
                            ->values();
                        $kkColourErrors = collect($errors->getMessages())
                            ->filter(fn ($messages, $key) => $key === 'colours' || str_starts_with($key, 'colours.'))
                            ->flatten();
                        // The master Colours list: a name and, where the admin set one, the
                        // shade to fill the swatch in with. The product still saves its own
                        // copy of both, so this only decides what the picker offers.
                        $kkColourMasters = $colourOptions->map(fn ($c) => [
                            'name' => (string) $c->name,
                            'hex' => (string) ($c->hex_code ?? ''),
                        ])->values();
                    @endphp
                    <div class="card p-5" x-data="kkColours()">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-baseline gap-2">
                                <h2 class="text-[13px] font-semibold form-label-required" style="color: #303030;">Colours</h2>
                                {{-- One click to the list, in a new tab, for the colour that is
                                     not on it yet - this form is half filled in by now. --}}
                                <a href="{{ route('admin.colours.index') }}" target="_blank" rel="noopener"
                                   class="text-[11px]" style="color: #616161;">Manage colours</a>
                            </div>
                            <button type="button" @click="add()" class="btn btn-secondary" style="font-size:12px; padding:4px 10px;">+ Add colour</button>
                        </div>
                        <p class="text-xs mb-4" style="color: #616161;">Every product needs at least one colour, each with a name and a swatch. Pick from the colours you keep under Products &rarr; Colours and the swatch fills itself in; you can still change it here. They show as swatches on the product page, under the sizes.</p>

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
                                    {{-- Free text again when the master list is empty, for the
                                         same reason as the sizes above: an empty dropdown would
                                         make a shop that has not filled its lists in unable to
                                         save a product at all. --}}
                                    @if($kkColourMasters->isNotEmpty())
                                        <select x-bind:name="'colours[' + i + '][name]'" x-model="c.name"
                                                aria-label="Colour name"
                                                x-init="$nextTick(() => { $el.value = c.name })"
                                                @change="applyShade(c, $event.target.value)"
                                                style="flex:1 1 auto;max-width:240px;font-size:13px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.4rem .6rem;background:#fff;">
                                            <option value="" disabled>Select a colour</option>
                                            <template x-for="opt in optionsFor(c.name)" :key="opt">
                                                <option x-bind:value="opt" x-text="labelFor(opt)"></option>
                                            </template>
                                        </select>
                                    @else
                                        <input type="text" x-bind:name="'colours[' + i + '][name]'" x-model="c.name" placeholder="Navy"
                                               maxlength="60" aria-label="Colour name"
                                               style="flex:1 1 auto;max-width:240px;font-size:13px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.4rem .6rem;">
                                    @endif
                                    <button type="button" @click="rows.splice(i, 1)" title="Remove" class="btn-icon"
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
                        // The admin's own Colours list - {name, hex} - and, like the sizes,
                        // only what the picker offers with. Nothing server-side is narrowed
                        // to it, because 1,266 live products carry colours that are on no
                        // list and an import may post anything at all.
                        const KK_COLOURS = @json($kkColourMasters);
                        const KK_COLOUR_NAMES = KK_COLOURS.map(c => c.name);
                        function kkColours() {
                            return {
                                seq: 0,
                                rows: [],
                                init() {
                                    this.rows = (@json($kkColourRows)).map(c => ({
                                        ...c,
                                        uid: ++this.seq,
                                        picked: !!c.hex,
                                        hex: c.hex || KK_UNPICKED_SWATCH,
                                    }));
                                    // A colour is required, so a product that has none opens on
                                    // a row to fill in.
                                    if (this.rows.length === 0) { this.add(); }
                                },
                                add() { this.rows.push({ uid: ++this.seq, name: '', hex: KK_UNPICKED_SWATCH, picked: false }); },
                                // A colour already on the row that is not on the master list
                                // keeps its place at the top of that row's dropdown, so an old
                                // product is never quietly re-coloured by being opened.
                                optionsFor(v) { return KK_COLOUR_NAMES.includes(v) ? KK_COLOUR_NAMES : (v ? [v, ...KK_COLOUR_NAMES] : KK_COLOUR_NAMES); },
                                labelFor(v) { return KK_COLOUR_NAMES.includes(v) ? v : v + ' — not in list'; },
                                // Picking a master colour that carries a shade fills the swatch
                                // in and counts as having chosen it - that is the whole point of
                                // keeping a hex on the master row. A master colour with no shade
                                // deliberately leaves the row unpicked: the rule that no colour
                                // is ever saved with a swatch nobody chose still holds, and the
                                // admin is asked for one. The swatch stays editable either way,
                                // so this run of Navy can be a shade off the standard Navy.
                                applyShade(c, name) {
                                    const master = KK_COLOURS.find(m => m.name === name);
                                    if (master && master.hex) { c.hex = master.hex; c.picked = true; }
                                },
                            };
                        }
                    </script>

                    <!-- Textures -->
                    @php
                        // Textures live on the product, not on a size row, exactly as the
                        // colours above do - a texture is offered in every size the product
                        // ships in. The textures the admin just typed when a save bounced
                        // back, otherwise the ones on the product. A texture is a name and
                        // nothing else, so there is no swatch to carry back; an entry is
                        // tolerated in map form too, since hand-edited JSON has been found
                        // written that way.
                        $kkTextureRows = collect(old('textures', data_get($product->attributes, 'Textures', [])))
                            ->map(fn ($t) => is_array($t) ? (string) ($t['name'] ?? '') : (string) $t)
                            ->values();
                        // @error cannot name an indexed key like "textures.0", so the row
                        // messages are lifted out of the bag by hand and shown together.
                        $kkTextureErrors = collect($errors->getMessages())
                            ->filter(fn ($messages, $key) => $key === 'textures' || str_starts_with($key, 'textures.'))
                            ->flatten();
                        // The master Textures list, as plain names - the product still saves
                        // its own copy of whichever one is chosen.
                        $kkTextureNames = $textureOptions->pluck('name')->values();
                    @endphp
                    <div class="card p-5" x-data="kkTextures()">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-baseline gap-2">
                                <h2 class="text-[13px] font-semibold" style="color: #303030;">Textures</h2>
                                {{-- Same escape hatch as the two lists above, same new tab. --}}
                                <a href="{{ route('admin.textures.index') }}" target="_blank" rel="noopener"
                                   class="text-[11px]" style="color: #616161;">Manage textures</a>
                            </div>
                            <button type="button" @click="add()" class="btn btn-secondary" style="font-size:12px; padding:4px 10px;">+ Add texture</button>
                        </div>
                        <p class="text-xs mb-4" style="color: #616161;">Optional - a texture is a name on its own, with no swatch to pick, chosen from the textures you keep under Products &rarr; Textures. They show on the product page under the colours, and become a filter on the shop.</p>

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
                                    {{-- Free text when the master list is empty - a texture is
                                         optional, but an empty dropdown next to an "Add texture"
                                         button that does nothing is worse than the box it
                                         replaces. --}}
                                    @if($kkTextureNames->isNotEmpty())
                                        <select x-bind:name="'textures[' + i + ']'" x-model="t.name"
                                                aria-label="Texture name"
                                                x-init="$nextTick(() => { $el.value = t.name })"
                                                style="flex:1 1 auto;max-width:240px;font-size:13px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.4rem .6rem;background:#fff;">
                                            <option value="" disabled>Select a texture</option>
                                            <template x-for="opt in optionsFor(t.name)" :key="opt">
                                                <option x-bind:value="opt" x-text="labelFor(opt)"></option>
                                            </template>
                                        </select>
                                    @else
                                        <input type="text" x-bind:name="'textures[' + i + ']'" x-model="t.name" placeholder="Matte"
                                               maxlength="60" aria-label="Texture name"
                                               style="flex:1 1 auto;max-width:240px;font-size:13px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.4rem .6rem;">
                                    @endif
                                    <button type="button" @click="rows.splice(i, 1)" title="Remove" class="btn-icon"
                                            style="color:#d72c0d;background:none;border:0;cursor:pointer;font-size:16px;line-height:1;">&times;</button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <script>
                        // The admin's own Textures list; not enforced server-side, for the
                        // same reason the sizes and colours are not.
                        const KK_TEXTURES = @json($kkTextureNames);
                        function kkTextures() {
                            return {
                                seq: 0,
                                rows: [],
                                init() {
                                    // uid, not the index, keys the x-for: removing a middle row
                                    // otherwise renumbers the rest and Alpine reuses the wrong
                                    // input for them.
                                    this.rows = (@json($kkTextureRows)).map(name => ({ uid: ++this.seq, name }));
                                },
                                add() { this.rows.push({ uid: ++this.seq, name: '' }); },
                                // A texture the product already carries that is not on the
                                // master list stays selectable on its own row, so opening the
                                // form never rewrites it.
                                optionsFor(v) { return KK_TEXTURES.includes(v) ? KK_TEXTURES : (v ? [v, ...KK_TEXTURES] : KK_TEXTURES); },
                                labelFor(v) { return KK_TEXTURES.includes(v) ? v : v + ' — not in list'; },
                            };
                        }
                    </script>


                    {{-- A+ Content (Amazon-style banner images) - replaces the old Feature Highlights --}}
                    @include('admin.products.partials.aplus-content')
                </div>

                <!-- RIGHT COLUMN (1/3) - Sidebar -->
                <div class="space-y-4">

                    <!-- Status -->
                    <div class="card p-5">
                        <h2 class="text-[13px] font-semibold mb-3" style="color: #303030;">Status</h2>
                        <select name="is_active" class="form-input w-full text-sm">
                            <option value="1" {{ old('is_active', $product->is_active) ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !old('is_active', $product->is_active) ? 'selected' : '' }}>Draft</option>
                        </select>
                        <label class="flex items-center gap-2 mt-3 cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }} class="form-checkbox">
                            <span class="text-[13px]" style="color: #303030;">Featured product</span>
                        </label>
                    </div>

                    <!-- Organization (Category, Brand, Seller) -->
                    <div class="card p-5 space-y-4">
                        <h2 class="text-[13px] font-semibold" style="color: #303030;">Organization</h2>
                        <div>
                            <label for="category_id" class="form-label form-label-required">Category</label>
                            <select name="category_id" id="category_id" required class="form-input w-full @error('category_id') form-input-error @enderror">
                                <option value="">Select</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->path_label ?? $category->name }}</option>
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
                                    <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                            @error('brand_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="seller_id" class="form-label">Seller</label>
                            <select name="seller_id" id="seller_id" class="form-input w-full @error('seller_id') form-input-error @enderror">
                                <option value="">Select</option>
                                @foreach($sellers as $seller)
                                    <option value="{{ $seller->id }}" {{ old('seller_id', $product->seller_id) == $seller->id ? 'selected' : '' }}>{{ $seller->store_name }}</option>
                                @endforeach
                            </select>
                            @error('seller_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="slug" class="form-label">URL handle</label>
                            <input type="text" name="slug" id="slug" x-model="slug"
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
                            <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title', $product->meta_title) }}" maxlength="255"
                                   class="form-input w-full @error('meta_title') form-input-error @enderror">
                            @error('meta_title') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="meta_description" class="form-label">Meta description</label>
                            <textarea name="meta_description" id="meta_description" rows="3" maxlength="500"
                                      class="form-input w-full @error('meta_description') form-input-error @enderror">{{ old('meta_description', $product->meta_description) }}</textarea>
                            @error('meta_description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div class="flex items-center justify-between mt-5 pt-4" style="border-top: 1px solid #e3e3e3;">
                <div>
                    <button type="submit" form="delete-product-form" class="text-[13px] font-medium py-2" style="color: #d72c0d;">Delete product</button>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary text-[13px]">Discard</a>
                    <button type="submit" class="btn btn-primary text-[13px]">Save</button>
                </div>
            </div>
        </form>

        {{-- Delete form kept OUTSIDE the edit form. Nested forms are invalid HTML and
             caused the edit form to submit _method=DELETE, deleting the product on Save. --}}
        <form id="delete-product-form" action="{{ route('admin.products.destroy', $product) }}" method="POST"
              onsubmit="return confirm('Delete {{ addslashes($product->name) }}? This cannot be undone.')">
            @csrf @method('DELETE')
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
                slug: '{{ old("slug", $product->slug) }}',
                slugManual: true,
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
        // matches its file's index when a tile is removed. The videos below already
        // worked this way; they only gained the same wording and clean-up.
        //
        // The caps mirror the server: images[] max:10 and videos[] max:5 per save,
        // counted over the files added in this submission, not over what the product
        // already has.
        const GALLERY_MAX = 10;
        const VIDEO_MAX = 5;
        const IMAGE_MAX_BYTES = 2 * 1024 * 1024;
        const VIDEO_MAX_BYTES = 50 * 1024 * 1024;
        const IMAGE_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        const VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/quicktime'];

        function imageManager(reorderUrl = '') {
            return {
                reorderUrl,
                deletedIds: [],
                mainPreview: null,
                mainImageChanged: false,
                galleryPreviews: [],
                galleryFileList: new DataTransfer(),
                videoPreviews: [],
                videoFileList: new DataTransfer(),
                dragEl: null,
                handleMainImage(file) {
                    if (!file) return;
                    if (!IMAGE_TYPES.includes(file.type)) { if (window.toastr) toastr.error(file.name + ' is not a JPG, PNG, WEBP or GIF.'); return; }
                    if (file.size > IMAGE_MAX_BYTES) { if (window.toastr) toastr.error(file.name + ' exceeds 2MB limit.'); return; }
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    this.$refs.mainFileInput.files = dt.files;
                    this.mainImageChanged = true;
                    const reader = new FileReader();
                    reader.onload = (e) => { this.mainPreview = e.target.result; };
                    reader.readAsDataURL(file);
                },
                markForDelete(id) { if (!confirm('Remove this media item?')) return; this.deletedIds.push(id); },
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
                        toastr.error('Only ' + GALLERY_MAX + ' new images per save - ' + overCap + (overCap === 1 ? ' was' : ' were') + ' left out.');
                    }
                },
                removeGalleryImage(index) {
                    URL.revokeObjectURL(this.galleryPreviews[index].url);
                    this.galleryPreviews.splice(index, 1);
                    this.galleryFileList.items.remove(index);
                    this.$refs.galleryInput.files = this.galleryFileList.files;
                },
                handleVideoFiles(files) {
                    let overCap = 0;
                    for (const file of files) {
                        if (!VIDEO_TYPES.includes(file.type)) { if (window.toastr) toastr.error(file.name + ' is not an MP4, WEBM or MOV.'); continue; }
                        if (file.size > VIDEO_MAX_BYTES) { if (window.toastr) toastr.error(file.name + ' exceeds 50MB.'); continue; }
                        if (this.videoFileList.items.length >= VIDEO_MAX) { overCap++; continue; }
                        this.videoFileList.items.add(file);
                        this.videoPreviews.push({ url: URL.createObjectURL(file), name: file.name });
                    }
                    this.$refs.videoInput.files = this.videoFileList.files;
                    if (overCap > 0 && window.toastr) {
                        toastr.error('Only ' + VIDEO_MAX + ' new videos per save - ' + overCap + (overCap === 1 ? ' was' : ' were') + ' left out.');
                    }
                },
                removeVideo(index) {
                    URL.revokeObjectURL(this.videoPreviews[index].url);
                    this.videoPreviews.splice(index, 1);
                    this.videoFileList.items.remove(index);
                    this.$refs.videoInput.files = this.videoFileList.files;
                },
                // ---- Drag reorder (saves instantly) ----
                onDragStart(e) { this.dragEl = e.currentTarget; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', e.currentTarget.dataset.id || ''); },
                onDragOver(e) {
                    const target = e.currentTarget;
                    if (!this.dragEl || target === this.dragEl) return;
                    const list = this.$refs.mediaList;
                    const items = [...list.querySelectorAll('.media-tile')];
                    if (items.indexOf(target) < items.indexOf(this.dragEl)) list.insertBefore(this.dragEl, target);
                    else list.insertBefore(this.dragEl, target.nextSibling);
                },
                onDrop(e) { e.preventDefault(); },
                onDragEnd() { this.dragEl = null; this.saveOrder(); },
                // Tap fallback for the drag above: Android Chrome and iOS Safari send no
                // HTML5 drag events from touch, so the tile steps one place instead.
                moveTile(btn, dir) {
                    const el = btn.closest('.media-tile');
                    const deleted = this.deletedIds.map(String);
                    const tiles = [...this.$refs.mediaList.querySelectorAll('.media-tile')].filter(t => !deleted.includes(t.dataset.id));
                    const i = tiles.indexOf(el), j = i + dir;
                    if (i < 0 || j < 0 || j >= tiles.length) return;
                    if (dir < 0) tiles[j].before(el); else tiles[j].after(el);
                    this.saveOrder();
                },
                saveOrder() {
                    const deleted = this.deletedIds.map(String);
                    const ids = [...this.$refs.mediaList.querySelectorAll('.media-tile')]
                        .map(el => el.dataset.id)
                        .filter(id => !deleted.includes(id));
                    if (!this.reorderUrl || !ids.length) return;
                    fetch(this.reorderUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '', 'Accept': 'application/json' },
                        body: JSON.stringify({ order: ids }),
                    })
                    // The tile has already moved in the DOM by the time this runs, so a
                    // rejected save used to leave the admin looking at the new order
                    // believing it was stored. Same handling as the A+ reorder beside it.
                    .then(r => { if (window.toastr) { r.ok ? toastr.success('Order saved') : toastr.error('Could not save order'); } })
                    .catch(() => { if (window.toastr) toastr.error('Could not save order'); });
                },
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
