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

        <form id="product-form" action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- `variants.*.mrp` is deliberately NOT in this list. Naming a key here is a
                 promise that a field below is already showing it, and the compare-at guard
                 makes no such promise on page load: it re-arms setCustomValidity over the
                 values that came back and prints nothing until the row is touched. A row
                 the admin then deletes takes its inputs off screen while still submitting
                 them, so the guard has nothing to mark and the server's verdict on that row
                 would have vanished entirely. Left to the banner, it is at least said
                 once. --}}
            <x-admin.form-errors title="This product could not be saved."
                                 :handled="['name', 'short_description', 'description', 'main_image', 'images', 'images.*', 'videos', 'videos.*', 'price', 'mrp', 'cost_price', 'sku', 'barcode', 'stock_quantity', 'weight', 'length', 'width', 'height', 'hsn_code', 'category_id', 'brand_id', 'seller_id', 'slug', 'meta_title', 'meta_description']" />

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
                                   class="form-input w-full"
                                   @input="if(!slugManual) slug = toSlug($event.target.value)">
                            <x-field-error field="name" />
                        </div>
                        <div>
                            <label for="short_description" class="form-label">Short description</label>
                            <textarea name="short_description" id="short_description" rows="2" maxlength="500"
                                      class="form-input w-full">{{ old('short_description', $product->short_description) }}</textarea>
                            <x-field-error field="short_description" />
                        </div>
                        <div>
                            <label for="description" class="form-label form-label-required">Description</label>
                            {{-- `required` removed: CKEditor hides this textarea so HTML5 validation silently blocks submit. Server validates instead. --}}
                            <textarea name="description" id="description" rows="6"
                                      class="form-input w-full">{!! old('description', $product->description) !!}</textarea>
                            <x-field-error field="description" />
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
                                {{-- Touch screens send no HTML5 drag events, so a tile can also step one place with a tap.

                                     Both arrows go dead while a reorder is being written. Each tap
                                     posts the whole order, and the server answers concurrent writes
                                     in whatever sequence it finishes them, so three quick taps could
                                     leave the stored order set by the request that happened to land
                                     last rather than by the arrangement on screen. Disabling the
                                     control that starts the request is the honest way to say "one at
                                     a time" - refusing the click silently would look like the tap
                                     missed. The delete cross stays live: it changes nothing the
                                     server has yet been told about. --}}
                                <div class="absolute top-1.5 right-1.5 z-10 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button type="button" @click="moveTile($el, -1)" title="Move earlier" aria-label="Move earlier"
                                            :disabled="reorderBusy" :style="reorderBusy ? 'opacity:0.5; cursor:not-allowed;' : ''"
                                            class="w-7 h-7 bg-white rounded-full flex items-center justify-center shadow-sm">
                                        <svg style="width: 0.875rem; height: 0.875rem; color: #303030;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button type="button" @click="moveTile($el, 1)" title="Move later" aria-label="Move later"
                                            :disabled="reorderBusy" :style="reorderBusy ? 'opacity:0.5; cursor:not-allowed;' : ''"
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
                            {{-- The two multi-file zones below carry their own message wiring,
                                 and the reason is a name that cannot be matched. app.js finds
                                 the control a server note belongs to by normalising the input's
                                 name to the dotted key Laravel uses ("variants[0][sku]" ->
                                 "variants.0.sku"); "images[]" normalises to "images.", which is
                                 never equal to the "images" the note is keyed under. So it
                                 neither links the note to anything on the way in nor retires it
                                 when a new selection is made, and the verdict on the batch that
                                 was rejected hung over the batch that replaced it. Choosing or
                                 dropping files IS the correction, so the note goes with it, and
                                 the description is pointed at the zone rather than at the input
                                 because the input is display:none - a description on something
                                 that can never be focused is read to nobody. role and tabindex
                                 are what make the zone reachable in the first place, and Enter
                                 and Space are then owed the behaviour a real button would give
                                 them. The main image beside them needs none of this: its input
                                 is named "main_image", which matches its key exactly, so app.js
                                 already owns that note from both ends. --}}
                            <div class="flex-1 min-w-[45%] border border-dashed rounded-lg p-3 text-center cursor-pointer hover:border-neutral-400 transition-colors" style="border-color: #b5b5b5;"
                                 x-ref="galleryZone"
                                 role="button" tabindex="0"
                                 @if ($errors->has('images') || $errors->has('images.*')) aria-describedby="kk-srv-err-images" @endif
                                 @click="$refs.galleryInput.click()"
                                 @keydown.enter.prevent="$refs.galleryInput.click()"
                                 @keydown.space.prevent="$refs.galleryInput.click()"
                                 @dragover.prevent @dragleave.prevent
                                 @drop.prevent="handleGalleryFiles($event.dataTransfer.files); $refs.galleryError && $refs.galleryError.remove(); $refs.galleryZone && $refs.galleryZone.removeAttribute('aria-describedby')">
                                <input type="file" name="images[]" multiple accept="image/jpeg,image/jpg,image/png,image/webp,image/gif" x-ref="galleryInput" style="display: none;" @change="handleGalleryFiles($event.target.files); $refs.galleryError && $refs.galleryError.remove(); $refs.galleryZone && $refs.galleryZone.removeAttribute('aria-describedby')">
                                <p class="text-xs font-medium" style="color: #005bd3;">Add images</p>
                                <p class="text-[11px] mt-0.5" style="color: #616161;">Up to 10 per save, JPG/PNG/WEBP/GIF, max 2MB each</p>
                            </div>
                            <div class="flex-1 min-w-[45%] border border-dashed rounded-lg p-3 text-center cursor-pointer hover:border-neutral-400 transition-colors" style="border-color: #b5b5b5;"
                                 x-ref="videoZone"
                                 role="button" tabindex="0"
                                 @if ($errors->has('videos') || $errors->has('videos.*')) aria-describedby="kk-srv-err-videos" @endif
                                 @click="$refs.videoInput.click()"
                                 @keydown.enter.prevent="$refs.videoInput.click()"
                                 @keydown.space.prevent="$refs.videoInput.click()"
                                 @dragover.prevent @dragleave.prevent
                                 @drop.prevent="handleVideoFiles($event.dataTransfer.files); $refs.videoError && $refs.videoError.remove(); $refs.videoZone && $refs.videoZone.removeAttribute('aria-describedby')">
                                <input type="file" name="videos[]" multiple accept="video/mp4,video/webm,video/quicktime" x-ref="videoInput" style="display: none;" @change="handleVideoFiles($event.target.files); $refs.videoError && $refs.videoError.remove(); $refs.videoZone && $refs.videoZone.removeAttribute('aria-describedby')">
                                <p class="text-xs font-medium" style="color: #005bd3;">Add videos</p>
                                <p class="text-[11px] mt-0.5" style="color: #616161;">Up to 5 per save, MP4/WEBM/MOV, max 50MB each</p>
                            </div>
                        </div>
                        <x-field-error field="main_image" />
                        {{-- One control, one note - and which key it comes from is the
                             whole of the care here.

                             A multi-file input is a SINGLE control, but Laravel complains
                             about it in two different registers: the array-level caps
                             (images max:10, videos max:5) land under `images` / `videos`,
                             while the per-file rules - mimes, max size - land under
                             `images.0`, `images.1` and so on. Rendering both keys, which
                             is what the four tags here used to do, put two red paragraphs
                             under one file input for one rejected save. That is the
                             duplicate-message-per-field bug with the server playing both
                             parts, and a save that tripped both caps and a bad file
                             printed four paragraphs for two controls.

                             The array-level message wins when both are present, because
                             it is about the selection as a whole and that is what has to
                             change; a complaint about one file inside a selection that is
                             too big to accept anyway is not yet the useful thing to read,
                             and it surfaces on the next attempt once the count is right.
                             Neither key rendered at all was the original bug: the save
                             bounced back in silence. --}}
                        @php
                            $kkImagesKey = $errors->has('images') ? 'images' : 'images.*';
                            $kkVideosKey = $errors->has('videos') ? 'videos' : 'videos.*';
                        @endphp
                        <x-field-error :field="$kkImagesKey" x-ref="galleryError" />
                        <x-field-error :field="$kkVideosKey" x-ref="videoError" />
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
                                           step="0.01" min="0" max="9999999.99" class="form-input form-input-prefixed w-full">
                                </div>
                                <x-field-error field="price" />
                            </div>
                            <div>
                                <label for="mrp" class="form-label">Compare-at price</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px]" style="color: #616161;">₹</span>
                                    <input type="number" name="mrp" id="mrp" value="{{ old('mrp', $product->mrp) }}"
                                           step="0.01" min="0" max="9999999.99" class="form-input form-input-prefixed w-full">
                                </div>
                                <x-field-error field="mrp" />
                                {{-- Filled in by the compare-at guard below as the two prices are typed. --}}
                                {{-- The message lives in setCustomValidity(), which the
                                     site-wide validator reads back and prints as the one
                                     note under this field. A second paragraph here put the
                                     same sentence on screen twice. --}}
                                <p class="form-hint" style="font-size:11px;color:#999;margin-top:4px;">Shown struck-through on the product page. Must be at least the Price.</p>
                            </div>
                            <div>
                                <label for="cost_price" class="form-label">Cost per item</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px]" style="color: #616161;">₹</span>
                                    <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price', $product->cost_price) }}"
                                           step="0.01" min="0" max="9999999.99" class="form-input form-input-prefixed w-full">
                                </div>
                                <x-field-error field="cost_price" />
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
                                       class="form-input w-full">
                                <x-field-error field="sku" />
                            </div>
                            <div>
                                <label for="barcode" class="form-label">Barcode (EAN/UPC)</label>
                                <input type="text" name="barcode" id="barcode" value="{{ old('barcode', $product->barcode) }}"
                                       maxlength="50" pattern="[A-Za-z0-9\-]+"
                                       title="Letters, digits and hyphens only, up to 50 characters."
                                       class="form-input w-full">
                                <x-field-error field="barcode" />
                            </div>
                            <div>
                                <label for="stock_quantity" class="form-label form-label-required">Quantity</label>
                                <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" required
                                       min="0" max="1000000" step="1" class="form-input w-full">
                                <x-field-error field="stock_quantity" />
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
                                       step="0.01" min="0" max="999999.99" class="form-input w-full"
                                       placeholder="0.5">
                                <x-field-error field="weight" />
                            </div>
                            <div>
                                <label for="length" class="form-label">Length (cm)</label>
                                <input type="number" name="length" id="length" value="{{ old('length', $product->length) }}"
                                       step="0.1" min="0" max="999999.99" class="form-input w-full"
                                       placeholder="10">
                                <x-field-error field="length" />
                            </div>
                            <div>
                                <label for="width" class="form-label">Width (cm)</label>
                                <input type="number" name="width" id="width" value="{{ old('width', $product->width) }}"
                                       step="0.1" min="0" max="999999.99" class="form-input w-full"
                                       placeholder="10">
                                <x-field-error field="width" />
                            </div>
                            <div>
                                <label for="height" class="form-label">Height (cm)</label>
                                <input type="number" name="height" id="height" value="{{ old('height', $product->height) }}"
                                       step="0.1" min="0" max="999999.99" class="form-input w-full"
                                       placeholder="10">
                                <x-field-error field="height" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label for="hsn_code" class="form-label">HSN code</label>
                                <input type="text" name="hsn_code" id="hsn_code" value="{{ old('hsn_code', $product->hsn_code) }}"
                                       inputmode="numeric" maxlength="8" pattern="[0-9]{4,8}"
                                       title="An HSN code is 4, 6 or 8 digits."
                                       class="form-input w-full"
                                       placeholder="e.g. 6109">
                                <x-field-error field="hsn_code" />
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
                        // Existing sizes, shaped for the Alpine table above.
                        $kkSizeRows = $product->variants->map(fn ($v) => [
                            'id' => $v->id,
                            'name' => $v->name,
                            'colour' => data_get($v->attributes, 'Colour', ''),
                            'colour_hex' => data_get($v->attributes, 'colour_hex', '#000000'),
                            'price' => (string) $v->price,
                            'mrp' => (string) $v->mrp,
                            'stock_quantity' => (int) $v->stock_quantity,
                            'sku' => $v->sku,
                            'measurements' => data_get($v->attributes, 'measurements', ''),
                            'is_active' => (bool) $v->is_active,
                        ])->values();
                    @endphp
                    <!-- Sizes & pricing -->
                    <div class="card p-5" x-data="kkSizes()">
                        <div class="flex items-center justify-between mb-1">
                            <h2 class="text-[13px] font-semibold" style="color: #303030;">Sizes &amp; pricing</h2>
                            <button type="button" @click="add()" class="btn btn-secondary" style="font-size:12px; padding:4px 10px;">+ Add size</button>
                        </div>
                        <p class="text-xs mb-4" style="color: #616161;">Each row is one size a customer can buy, with its own price and stock. Measurements are optional and let the assistant advise on fit. Leave SKU blank and one is generated. Colours are set separately below.</p>

                        <p class="text-xs" style="color:#616161; padding:10px 0;" x-show="visibleCount() === 0" x-cloak>No sizes yet - click &ldquo;Add size&rdquo;.</p>

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
                        function kkSizes() {
                            return {
                                seq: 0,
                                rows: [],
                                init() {
                                    this.rows = (@json($kkSizeRows)).map(r => ({ ...r, uid: ++this.seq, remove: false }));
                                },
                                visibleCount() { return this.rows.filter(r => !r.remove).length; },
                                add() {
                                    this.rows.push({
                                        uid: ++this.seq, id: null, name: '', colour: '', colour_hex: '#000000',
                                        price: @json((string) $product->price), mrp: @json((string) $product->mrp),
                                        stock_quantity: 0, sku: '', measurements: '', is_active: true, remove: false,
                                    });
                                },
                                drop(i) {
                                    if (this.rows[i].id) { this.rows[i].remove = true; } else { this.rows.splice(i, 1); }
                                },
                            };
                        }
                    </script>

                    <!-- Colours -->
                    @php
                        // Colours live on the product, not on a size row, so a product can
                        // offer any colour in any size without one row per combination.
                        $kkColourRows = collect(data_get($product->attributes, 'Colours', []))
                            ->map(fn ($c) => is_array($c)
                                ? ['name' => $c['name'] ?? '', 'hex' => $c['hex'] ?? '#000000']
                                : ['name' => (string) $c, 'hex' => '#000000'])
                            ->filter(fn ($c) => $c['name'] !== '')
                            ->values();
                    @endphp
                    <div class="card p-5" x-data="kkColours()">
                        <div class="flex items-center justify-between mb-1">
                            <h2 class="text-[13px] font-semibold" style="color: #303030;">Colours</h2>
                            <button type="button" @click="add()" class="btn btn-secondary" style="font-size:12px; padding:4px 10px;">+ Add colour</button>
                        </div>
                        <p class="text-xs mb-4" style="color: #616161;">The colours this product comes in. They show as swatches on the product page, under the sizes.</p>

                        <p class="text-xs" style="color:#616161; padding:6px 0;" x-show="rows.length === 0" x-cloak>No colours yet - click &ldquo;Add colour&rdquo;.</p>

                        <div x-show="rows.length > 0" x-cloak style="display:flex;flex-direction:column;gap:8px;">
                            <template x-for="(c, i) in rows" :key="c.uid">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <input type="color" x-bind:name="'colours[' + i + '][hex]'" x-model="c.hex" aria-label="Colour swatch"
                                           style="width:34px;height:32px;border:1px solid #d4d4d4;border-radius:.375rem;padding:0;background:none;flex:0 0 auto;">
                                    <input type="text" x-bind:name="'colours[' + i + '][name]'" x-model="c.name" placeholder="Navy"
                                           maxlength="60" aria-label="Colour name"
                                           style="flex:1 1 auto;max-width:240px;font-size:13px;border:1px solid #d4d4d4;border-radius:.375rem;padding:.4rem .6rem;">
                                    <button type="button" @click="rows.splice(i, 1)" title="Remove" class="btn-icon"
                                            style="color:#d72c0d;background:none;border:0;cursor:pointer;font-size:16px;line-height:1;">&times;</button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <script>
                        function kkColours() {
                            return {
                                seq: 0,
                                rows: [],
                                init() {
                                    this.rows = (@json($kkColourRows)).map(c => ({ ...c, uid: ++this.seq }));
                                },
                                add() { this.rows.push({ uid: ++this.seq, name: '', hex: '#000000' }); },
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
                            <select name="category_id" id="category_id" required class="form-input w-full">
                                <option value="">Select</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->path_label ?? $category->name }}</option>
                                @endforeach
                            </select>
                            <x-field-error field="category_id" />
                        </div>
                        <div>
                            <label for="brand_id" class="form-label">Brand</label>
                            <select name="brand_id" id="brand_id" class="form-input w-full">
                                <option value="">Select</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                            <x-field-error field="brand_id" />
                        </div>
                        <div>
                            <label for="seller_id" class="form-label">Seller</label>
                            <select name="seller_id" id="seller_id" class="form-input w-full">
                                <option value="">Select</option>
                                @foreach($sellers as $seller)
                                    <option value="{{ $seller->id }}" {{ old('seller_id', $product->seller_id) == $seller->id ? 'selected' : '' }}>{{ $seller->store_name }}</option>
                                @endforeach
                            </select>
                            <x-field-error field="seller_id" />
                        </div>
                        <div>
                            <label for="slug" class="form-label">URL handle</label>
                            <input type="text" name="slug" id="slug" x-model="slug"
                                   maxlength="255" pattern="[a-z0-9]+(-[a-z0-9]+)*"
                                   title="Lower-case letters, digits and single hyphens, e.g. short-sleeve-t-shirt."
                                   class="form-input w-full"
                                   @input="slugManual = ($event.target.value.trim() !== '')">
                            <x-field-error field="slug" />
                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="card p-5 space-y-4">
                        <h2 class="text-[13px] font-semibold" style="color: #303030;">Search engine listing</h2>
                        <div>
                            <label for="meta_title" class="form-label">Page title</label>
                            <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title', $product->meta_title) }}" maxlength="255"
                                   class="form-input w-full">
                            <x-field-error field="meta_title" />
                        </div>
                        <div>
                            <label for="meta_description" class="form-label">Meta description</label>
                            <textarea name="meta_description" id="meta_description" rows="3" maxlength="500"
                                      class="form-input w-full">{{ old('meta_description', $product->meta_description) }}</textarea>
                            <x-field-error field="meta_description" />
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
                // ---- Reorder bookkeeping ----
                //
                // A reorder is applied to the tiles FIRST and posted afterwards, which
                // makes the grid a claim about the database rather than a picture of
                // it. Two things have to hold for that claim to stay honest, and
                // neither did: only one write may be in flight at a time, and a write
                // the server refuses has to be taken back off the screen.
                //
                // The arrangement the tiles were in before the gesture now being
                // saved, captured BEFORE the DOM is touched so that a refusal has a
                // known-good order to restore. Emptied on success, when the screen and
                // the database agree again and there is nothing left to roll back to.
                orderBeforeMove: [],
                reorderBusy: false,
                tileOrder() {
                    const list = this.$refs.mediaList;
                    return list ? [...list.querySelectorAll('.media-tile')].map(el => el.dataset.id) : [];
                },
                // Restores a known order. Written by id and by insertion rather than by
                // index, because the same grid also holds the <template> anchors and
                // the freshly picked previews, which must be left exactly where Alpine
                // put them.
                applyOrder(ids) {
                    const list = this.$refs.mediaList;
                    if (!list) return;
                    const byId = new Map([...list.querySelectorAll('.media-tile')].map(el => [el.dataset.id, el]));
                    let anchor = null;
                    ids.forEach(function (id) {
                        const el = byId.get(id);
                        if (!el) return;
                        if (anchor) anchor.after(el); else list.prepend(el);
                        anchor = el;
                    });
                },
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
                onDragStart(e) {
                    // Refused outright rather than queued. A drag begun while the
                    // previous order is still being written would race it, and the
                    // loser of that race is whatever the person can actually see.
                    if (this.reorderBusy) { e.preventDefault(); return; }
                    this.orderBeforeMove = this.tileOrder();
                    this.dragEl = e.currentTarget;
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.id || '');
                },
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
                    // The arrows are already disabled while a write is in flight; this
                    // is the belt to that brace, for a call that arrives some other
                    // way (a keyboard activation racing the re-render, say).
                    if (this.reorderBusy) return;
                    const el = btn.closest('.media-tile');
                    const deleted = this.deletedIds.map(String);
                    const tiles = [...this.$refs.mediaList.querySelectorAll('.media-tile')].filter(t => !deleted.includes(t.dataset.id));
                    const i = tiles.indexOf(el), j = i + dir;
                    if (i < 0 || j < 0 || j >= tiles.length) return;
                    // Snapshotted before the step, not after it, or the "previous"
                    // order would be the one we are about to try to save.
                    this.orderBeforeMove = this.tileOrder();
                    if (dir < 0) tiles[j].before(el); else tiles[j].after(el);
                    this.saveOrder();
                },
                saveOrder() {
                    const deleted = this.deletedIds.map(String);
                    const ids = this.tileOrder().filter(id => !deleted.includes(id));
                    if (!this.reorderUrl || !ids.length) return;
                    if (this.reorderBusy) return;

                    this.reorderBusy = true;
                    fetch(this.reorderUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '', 'Accept': 'application/json' },
                        body: JSON.stringify({ order: ids }),
                    }).then(async (r) => {
                        if (r.ok) {
                            this.orderBeforeMove = [];
                            if (window.toastr) toastr.success('Order saved');
                            return;
                        }
                        // Only `r.ok` was ever looked at, so a permission refusal, a
                        // session that had expired while the page sat open, a rejected
                        // payload and a 500 all ended the same way: the tiles left
                        // sitting in an order the server had refused, and not a word
                        // about it. Which sentence a status deserves is not this
                        // view's judgement to make - kkApiError owns that for the
                        // whole site, and is also what keeps a 500's exception text,
                        // and anything it drags along with it, off an admin's screen.
                        this.failReorder(await window.kkFetchError(r));
                    }).catch((e) => {
                        // A request that never got an answer at all - the laptop lid
                        // closed, the wifi dropped - resolves no response, so there is
                        // no status to read. kkApiError calls that 0 and gives it its
                        // own sentence rather than a shrug that blames the server.
                        this.failReorder(window.kkApiError(e));
                    }).finally(() => {
                        this.reorderBusy = false;
                    });
                },
                // The write was refused, so the arrangement on screen is a claim the
                // database never accepted. Put the tiles back where the server still
                // has them and say why - leaving them in the new order would tell the
                // admin the reorder had stuck, and they would find out otherwise only
                // on the storefront.
                failReorder(failure) {
                    if (this.orderBeforeMove.length) this.applyOrder(this.orderBeforeMove);
                    this.orderBeforeMove = [];
                    if (window.toastr) toastr.error(failure.message);
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
