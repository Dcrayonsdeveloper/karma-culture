<x-layouts.admin>
    <x-slot name="title">Edit {{ $product->name }}</x-slot>

    <div x-data="productForm()">
        <!-- Shopify-style top bar with breadcrumb + actions -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('admin.products.index') }}" class="shrink-0 p-1 rounded hover:bg-neutral-200 transition-colors" style="color: #616161;">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 style="font-size: 1.125rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #303030;">{{ $product->name }}</h1>
                <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-neutral' }} shrink-0">{{ $product->is_active ? 'Active' : 'Draft' }}</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('product.show', $product) }}" target="_blank" class="btn btn-secondary text-[13px]">View on site</a>
            </div>
        </div>

        <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Two-column Shopify layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                <!-- LEFT COLUMN (2/3) -->
                <div class="lg:col-span-2 space-y-4">

                    <!-- Title & Description -->
                    <div class="card p-5 space-y-4">
                        <div>
                            <label for="name" class="form-label form-label-required">Title</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required
                                   class="form-input w-full @error('name') form-input-error @enderror"
                                   @input="if(!slugManual) slug = toSlug($event.target.value)">
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="short_description" class="form-label">Short description</label>
                            <textarea name="short_description" id="short_description" rows="2"
                                      class="form-input w-full @error('short_description') form-input-error @enderror">{{ old('short_description', $product->short_description) }}</textarea>
                            @error('short_description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="description" class="form-label form-label-required">Description</label>
                            {{-- `required` removed: CKEditor hides this textarea so HTML5 validation silently blocks submit. Server validates instead. --}}
                            <textarea name="description" id="description" rows="6"
                                      class="form-input w-full @error('description') form-input-error @enderror">{!! old('description', $product->description) !!}</textarea>
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
                            <div class="relative group rounded-lg overflow-hidden aspect-square media-tile"
                                 style="border: 1px solid #e3e3e3; cursor: grab;"
                                 draggable="true" data-id="{{ $image->id }}"
                                 @dragstart="onDragStart($event)" @dragover.prevent="onDragOver($event)"
                                 @drop.prevent="onDrop($event)" @dragend="onDragEnd()"
                                 x-show="!deletedIds.includes({{ $image->id }})">
                                @if($image->is_video)
                                    <video src="{{ $image->display_url }}" muted playsinline preload="metadata" style="width: 100%; height: 100%; object-fit: cover; background:#000;"></video>
                                    <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[10px] font-semibold rounded text-white" style="background: rgba(0,0,0,0.7);">&#9654; Video</span>
                                @else
                                    <img src="{{ $image->display_url }}" alt="{{ $image->alt_text }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @if($image->is_primary)
                                        <span class="absolute bottom-0 left-0 right-0 px-2 py-1 text-[10px] font-semibold text-center text-white" style="background: rgba(0,91,211,0.85);">Main</span>
                                    @endif
                                @endif
                                <button type="button" @click="markForDelete({{ $image->id }})"
                                        class="absolute top-1.5 right-1.5 w-6 h-6 bg-white rounded-full flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg style="width: 0.875rem; height: 0.875rem; color: #d72c0d;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            @endforeach

                            <!-- New image previews -->
                            <template x-for="(preview, index) in galleryPreviews" :key="'img'+index">
                                <div class="relative group rounded-lg overflow-hidden aspect-square" style="border: 1px solid #e3e3e3;">
                                    <img :src="preview.url" style="width: 100%; height: 100%; object-fit: cover;">
                                    <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[10px] font-semibold rounded text-white" style="background: #2a9d3e;">New</span>
                                    <button type="button" @click="removeGalleryImage(index)" class="absolute top-1.5 right-1.5 w-6 h-6 bg-white rounded-full flex items-center justify-center shadow-sm">
                                        <svg style="width: 0.875rem; height: 0.875rem; color: #d72c0d;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                            <!-- New video previews -->
                            <template x-for="(preview, index) in videoPreviews" :key="'vid'+index">
                                <div class="relative group rounded-lg overflow-hidden aspect-square" style="border: 1px solid #e3e3e3;">
                                    <video :src="preview.url" muted playsinline style="width: 100%; height: 100%; object-fit: cover; background:#000;"></video>
                                    <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[10px] font-semibold rounded text-white" style="background: #2a9d3e;">New &#9654;</span>
                                    <button type="button" @click="removeVideo(index)" class="absolute top-1.5 right-1.5 w-6 h-6 bg-white rounded-full flex items-center justify-center shadow-sm">
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
                            <div class="flex-1 min-w-[45%] border border-dashed rounded-lg p-3 text-center cursor-pointer hover:border-neutral-400 transition-colors" style="border-color: #b5b5b5;"
                                 @click="$refs.mainFileInput.click()">
                                <input type="file" name="main_image" accept="image/jpeg,image/jpg,image/png,image/webp,image/gif" x-ref="mainFileInput" style="display: none;" @change="handleMainImage($event.target.files[0])">
                                <p class="text-xs font-medium" style="color: #005bd3;" x-text="mainImageChanged ? 'Main image ready ✓' : 'Set / replace main image'">Set / replace main image</p>
                            </div>
                            <div class="flex-1 min-w-[45%] border border-dashed rounded-lg p-3 text-center cursor-pointer hover:border-neutral-400 transition-colors" style="border-color: #b5b5b5;"
                                 @click="$refs.galleryInput.click()">
                                <input type="file" name="images[]" multiple accept="image/jpeg,image/jpg,image/png,image/webp,image/gif" x-ref="galleryInput" style="display: none;" @change="handleGalleryFiles($event.target.files)">
                                <p class="text-xs font-medium" style="color: #005bd3;">Add images</p>
                            </div>
                            <div class="flex-1 min-w-[45%] border border-dashed rounded-lg p-3 text-center cursor-pointer hover:border-neutral-400 transition-colors" style="border-color: #b5b5b5;"
                                 @click="$refs.videoInput.click()">
                                <input type="file" name="videos[]" multiple accept="video/mp4,video/webm,video/quicktime" x-ref="videoInput" style="display: none;" @change="handleVideoFiles($event.target.files)">
                                <p class="text-xs font-medium" style="color: #005bd3;">Add videos <span style="color:#999;">(MP4/WEBM/MOV, max 50MB)</span></p>
                            </div>
                        </div>
                        @error('main_image') <p class="form-error mt-2">{{ $message }}</p> @enderror
                        @error('images.*') <p class="form-error mt-2">{{ $message }}</p> @enderror
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
                                           step="0.01" min="0" class="form-input w-full pl-7 @error('price') form-input-error @enderror">
                                </div>
                                @error('price') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="mrp" class="form-label">Compare-at price</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px]" style="color: #616161;">₹</span>
                                    <input type="number" name="mrp" id="mrp" value="{{ old('mrp', $product->mrp) }}"
                                           step="0.01" min="0" class="form-input w-full pl-7 @error('mrp') form-input-error @enderror">
                                </div>
                                @error('mrp') <p class="form-error">{{ $message }}</p> @enderror
                                <p class="form-hint" style="font-size:11px;color:#999;margin-top:4px;">Shown struck-through on the product page. Must be higher than Price.</p>
                            </div>
                            <div>
                                <label for="cost_price" class="form-label">Cost per item</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px]" style="color: #616161;">₹</span>
                                    <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price', $product->cost_price) }}"
                                           step="0.01" min="0" class="form-input w-full pl-7 @error('cost_price') form-input-error @enderror">
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
                                       class="form-input w-full @error('sku') form-input-error @enderror">
                                @error('sku') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="barcode" class="form-label">Barcode (EAN/UPC)</label>
                                <input type="text" name="barcode" id="barcode" value="{{ old('barcode', $product->barcode) }}"
                                       class="form-input w-full @error('barcode') form-input-error @enderror">
                                @error('barcode') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="stock_quantity" class="form-label form-label-required">Quantity</label>
                                <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" required
                                       min="0" class="form-input w-full @error('stock_quantity') form-input-error @enderror">
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
                                       step="0.01" min="0" class="form-input w-full @error('weight') form-input-error @enderror"
                                       placeholder="0.5">
                                @error('weight') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="length" class="form-label">Length (cm)</label>
                                <input type="number" name="length" id="length" value="{{ old('length', $product->length) }}"
                                       step="0.1" min="0" class="form-input w-full @error('length') form-input-error @enderror"
                                       placeholder="10">
                                @error('length') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="width" class="form-label">Width (cm)</label>
                                <input type="number" name="width" id="width" value="{{ old('width', $product->width) }}"
                                       step="0.1" min="0" class="form-input w-full @error('width') form-input-error @enderror"
                                       placeholder="10">
                                @error('width') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="height" class="form-label">Height (cm)</label>
                                <input type="number" name="height" id="height" value="{{ old('height', $product->height) }}"
                                       step="0.1" min="0" class="form-input w-full @error('height') form-input-error @enderror"
                                       placeholder="10">
                                @error('height') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <label for="hsn_code" class="form-label">HSN code</label>
                                <input type="text" name="hsn_code" id="hsn_code" value="{{ old('hsn_code', $product->hsn_code) }}"
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
                    @if($product->variants->count())
                    <div class="card p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-[13px] font-semibold" style="color: #303030;">Variants</h2>
                                <p class="text-xs" style="color: #616161;">{{ $product->variants->count() }} variants</p>
                            </div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 1px solid #e3e3e3;">
                                        <th style="text-align: left; padding: 0.5rem; font-weight: 500; color: #616161;">Variant</th>
                                        <th style="text-align: left; padding: 0.5rem; font-weight: 500; color: #616161;">SKU</th>
                                        <th style="text-align: right; padding: 0.5rem; font-weight: 500; color: #616161;">Price</th>
                                        <th style="text-align: right; padding: 0.5rem; font-weight: 500; color: #616161;">MRP</th>
                                        <th style="text-align: right; padding: 0.5rem; font-weight: 500; color: #616161;">Stock</th>
                                        <th style="text-align: center; padding: 0.5rem; font-weight: 500; color: #616161;">Active</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($product->variants as $i => $variant)
                                    <tr style="border-bottom: 1px solid #f1f1f1;">
                                        <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $variant->id }}">
                                        <td style="padding: 0.5rem;">
                                            <span style="font-weight: 500; color: #303030;">{{ $variant->name }}</span>
                                            @if($variant->attributes)
                                                <span class="text-xs" style="color: #616161;">
                                                    ({{ collect($variant->attributes)->map(fn($v, $k) => "$v")->join(', ') }})
                                                </span>
                                            @endif
                                        </td>
                                        <td style="padding: 0.5rem;">
                                            <input type="text" name="variants[{{ $i }}][sku]" value="{{ old("variants.$i.sku", $variant->sku) }}"
                                                   style="width: 100px; font-size: 12px; border: 1px solid #d4d4d4; border-radius: 0.375rem; padding: 0.25rem 0.5rem;">
                                        </td>
                                        <td style="padding: 0.5rem; text-align: right;">
                                            <input type="number" name="variants[{{ $i }}][price]" value="{{ old("variants.$i.price", $variant->price) }}"
                                                   step="0.01" min="0"
                                                   style="width: 90px; font-size: 12px; border: 1px solid #d4d4d4; border-radius: 0.375rem; padding: 0.25rem 0.5rem; text-align: right;">
                                        </td>
                                        <td style="padding: 0.5rem; text-align: right;">
                                            <input type="number" name="variants[{{ $i }}][mrp]" value="{{ old("variants.$i.mrp", $variant->mrp) }}"
                                                   step="0.01" min="0"
                                                   style="width: 90px; font-size: 12px; border: 1px solid #d4d4d4; border-radius: 0.375rem; padding: 0.25rem 0.5rem; text-align: right;">
                                        </td>
                                        <td style="padding: 0.5rem; text-align: right;">
                                            <input type="number" name="variants[{{ $i }}][stock_quantity]" value="{{ old("variants.$i.stock_quantity", $variant->stock_quantity) }}"
                                                   min="0"
                                                   style="width: 70px; font-size: 12px; border: 1px solid #d4d4d4; border-radius: 0.375rem; padding: 0.25rem 0.5rem; text-align: right;">
                                        </td>
                                        <td style="padding: 0.5rem; text-align: center;">
                                            <input type="checkbox" name="variants[{{ $i }}][is_active]" value="1" {{ old("variants.$i.is_active", $variant->is_active) ? 'checked' : '' }} class="form-checkbox">
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Attributes -->
                    @if($attributes->count())
                    <div class="card p-5">
                        <h2 class="text-[13px] font-semibold mb-1" style="color: #303030;">Attributes</h2>
                        <p class="text-xs mb-4" style="color: #616161;">Product specifications and variants</p>
                        @php $productAttrs = $product->attributes ?? []; @endphp
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($attributes as $attribute)
                                <div>
                                    <label class="form-label">{{ $attribute->name }}</label>
                                    @if($attribute->type === 'text')
                                        <input type="text" name="product_attributes[{{ $attribute->name }}]"
                                               value="{{ old('product_attributes.' . $attribute->name, $productAttrs[$attribute->name] ?? '') }}"
                                               class="form-input w-full text-sm" placeholder="Enter {{ strtolower($attribute->name) }}">
                                    @else
                                        <select name="product_attributes[{{ $attribute->name }}]" class="form-input w-full text-sm">
                                            <option value="">Select</option>
                                            @foreach($attribute->values as $value)
                                                <option value="{{ $value->value }}" {{ old('product_attributes.' . $attribute->name, $productAttrs[$attribute->name] ?? '') === $value->value ? 'selected' : '' }}>
                                                    {{ $value->value }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($attribute->type === 'color' && $attribute->values->count())
                                            <div class="flex flex-wrap gap-1 mt-2">
                                                @foreach($attribute->values->take(10) as $value)
                                                    @if($value->color_code)
                                                        <div style="width: 1.25rem; height: 1.25rem; border-radius: 9999px; border: 1px solid #e5e5e5; background-color: {{ $value->color_code }}; {{ isset($productAttrs[$attribute->name]) && $productAttrs[$attribute->name] === $value->value ? 'box-shadow: 0 0 0 2px white, 0 0 0 4px #005bd3;' : '' }}" title="{{ $value->value }}"></div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Feature Highlights ("Why You'll Love It") -->
                    <div class="card p-5">
                        <h2 class="text-[13px] font-semibold mb-1" style="color: #303030;">Feature Highlights <span style="color:#999;font-weight:400;">("Why You'll Love It")</span></h2>
                        <p class="text-xs mb-4" style="color: #616161;">Up to 4 sections. Each can have <strong>multiple images</strong>, a heading, and <strong>multiple paragraphs</strong> (separate paragraphs with a blank line). Leave a section empty to skip it.</p>
                        @php $fhRows = array_values($product->feature_highlights ?? []); @endphp
                        <div class="space-y-4">
                            @for($s = 0; $s < 4; $s++)
                                @php
                                    $row = $fhRows[$s] ?? [];
                                    $norm = \App\Http\Controllers\Admin\ProductController::normaliseHighlight($row);
                                    $bodyText = old('fh_body.'.$s, implode("\n\n", $norm['paragraphs']));
                                @endphp
                                <div class="border rounded-lg p-3" style="border-color:#e5e5e5;">
                                    <p class="text-[11px] font-semibold mb-2" style="color:#8a8a8a;">Section {{ $s + 1 }}</p>
                                    @if(!empty($norm['images']))
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        @foreach($norm['images'] as $img)
                                            @php $prev = $img; if(!str_starts_with($prev,'http') && !str_starts_with($prev,'/')) $prev = asset('storage/'.$prev); @endphp
                                            <div class="relative" x-data="{ kept: true }" x-show="kept">
                                                <img src="{{ $prev }}" alt="" class="w-16 h-16 object-cover rounded">
                                                <template x-if="kept"><input type="hidden" name="fh_existing[{{ $s }}][]" value="{{ $img }}"></template>
                                                <button type="button" @click="kept=false" title="Remove image" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-white rounded-full shadow flex items-center justify-center" style="color:#d72c0d;font-size:12px;line-height:1;">&times;</button>
                                            </div>
                                        @endforeach
                                    </div>
                                    @endif
                                    <input type="file" name="fh_images[{{ $s }}][]" multiple accept="image/jpeg,image/png,image/webp" class="form-input w-full text-xs mb-2">
                                    <input type="text" name="fh_heading[{{ $s }}]" value="{{ old('fh_heading.'.$s, $norm['heading'] ?? '') }}" maxlength="120" class="form-input w-full text-sm mb-2" placeholder="Heading (e.g. Premium Cotton Fabric)">
                                    <textarea name="fh_body[{{ $s }}]" rows="4" maxlength="2000" class="form-input w-full text-sm" placeholder="Description. Separate paragraphs with a blank line to create multiple content blocks.">{{ $bodyText }}</textarea>
                                </div>
                            @endfor
                        </div>
                    </div>
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
                                    <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
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
                            <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title', $product->meta_title) }}"
                                   class="form-input w-full @error('meta_title') form-input-error @enderror">
                            @error('meta_title') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="meta_description" class="form-label">Meta description</label>
                            <textarea name="meta_description" id="meta_description" rows="3"
                                      class="form-input w-full @error('meta_description') form-input-error @enderror">{{ old('meta_description', $product->meta_description) }}</textarea>
                            @error('meta_description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div class="flex items-center justify-between mt-5 pt-4" style="border-top: 1px solid #e3e3e3;">
                <div>
                    <button type="submit" form="delete-product-form" class="text-[13px] font-medium" style="color: #d72c0d;">Delete product</button>
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
                    if (!file || !file.type.startsWith('image/')) return;
                    if (file.size > 2 * 1024 * 1024) { toastr.error(file.name + ' exceeds 2MB limit.'); return; }
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
                    for (const file of files) {
                        if (!file.type.startsWith('image/')) continue;
                        if (file.size > 2 * 1024 * 1024) { toastr.error(file.name + ' exceeds 2MB.'); continue; }
                        if (this.galleryPreviews.length >= 10) { toastr.error('Max 10 images.'); break; }
                        this.galleryFileList.items.add(file);
                        const reader = new FileReader();
                        reader.onload = (e) => { this.galleryPreviews.push({ url: e.target.result, name: file.name }); };
                        reader.readAsDataURL(file);
                    }
                    this.$refs.galleryInput.files = this.galleryFileList.files;
                },
                removeGalleryImage(index) { this.galleryPreviews.splice(index, 1); this.galleryFileList.items.remove(index); this.$refs.galleryInput.files = this.galleryFileList.files; },
                handleVideoFiles(files) {
                    for (const file of files) {
                        if (!file.type.startsWith('video/')) continue;
                        if (file.size > 50 * 1024 * 1024) { toastr.error(file.name + ' exceeds 50MB.'); continue; }
                        if (this.videoPreviews.length >= 5) { toastr.error('Max 5 videos.'); break; }
                        this.videoFileList.items.add(file);
                        this.videoPreviews.push({ url: URL.createObjectURL(file), name: file.name });
                    }
                    this.$refs.videoInput.files = this.videoFileList.files;
                },
                removeVideo(index) { this.videoPreviews.splice(index, 1); this.videoFileList.items.remove(index); this.$refs.videoInput.files = this.videoFileList.files; },
                // ---- Drag reorder (saves instantly) ----
                onDragStart(e) { this.dragEl = e.currentTarget; e.dataTransfer.effectAllowed = 'move'; },
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
                saveOrder() {
                    const ids = [...this.$refs.mediaList.querySelectorAll('.media-tile')].map(el => el.dataset.id);
                    if (!this.reorderUrl || !ids.length) return;
                    fetch(this.reorderUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '', 'Accept': 'application/json' },
                        body: JSON.stringify({ order: ids }),
                    }).then(r => { if (r.ok && window.toastr) toastr.success('Order saved'); });
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
    @endpush
</x-layouts.admin>
