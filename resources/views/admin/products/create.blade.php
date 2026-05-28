<x-layouts.admin>
    <x-slot name="title">Add Product</x-slot>

    <div x-data="productForm()">
        <!-- Shopify-style top bar -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.products.index') }}" class="p-1 rounded hover:bg-neutral-200 transition-colors" style="color: #616161;">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add product</h1>
            </div>
        </div>

        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Two-column Shopify layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                <!-- LEFT COLUMN (2/3) -->
                <div class="lg:col-span-2 space-y-4">

                    <!-- Title & Description -->
                    <div class="card p-5 space-y-4">
                        <div>
                            <label for="name" class="form-label form-label-required">Title</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                   class="form-input w-full @error('name') form-input-error @enderror"
                                   placeholder="Short sleeve t-shirt"
                                   @input="if(!slugManual) slug = toSlug($event.target.value)">
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="short_description" class="form-label">Short description</label>
                            <textarea name="short_description" id="short_description" rows="2"
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
                            <div x-show="mainPreview" x-transition class="relative w-28 h-28 rounded-lg overflow-hidden shrink-0" style="border: 2px solid #005bd3;">
                                <img :src="mainPreview" style="width: 100%; height: 100%; object-fit: cover;">
                                <button type="button" @click="removeMainImage()"
                                        class="absolute top-1 right-1 w-6 h-6 bg-white rounded-full flex items-center justify-center shadow-sm">
                                    <svg style="width: 0.875rem; height: 0.875rem; color: #d72c0d;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <span class="absolute bottom-0 left-0 right-0 px-2 py-0.5 text-[10px] font-semibold text-center text-white" style="background: rgba(0,91,211,0.85);">Main</span>
                            </div>

                            <div class="flex-1 border border-dashed rounded-lg p-5 text-center cursor-pointer transition-colors"
                                 style="border-color: #b5b5b5;"
                                 @click="$refs.mainFileInput.click()"
                                 @dragover.prevent="mainDragOver = true" @dragleave.prevent="mainDragOver = false"
                                 @drop.prevent="mainDragOver = false; handleMainImage($event.dataTransfer.files[0])"
                                 :style="mainDragOver ? 'border-color: #005bd3; background: #f0f6ff;' : ''">
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
                             :style="galleryDragOver ? 'border-color: #005bd3; background: #f0f6ff;' : ''">
                            <input type="file" name="images[]" multiple accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                                   x-ref="galleryInput" style="display: none;" @change="handleGalleryFiles($event.target.files)">
                            <p class="text-xs font-medium" style="color: #005bd3;">Add gallery images</p>
                            <p class="text-[11px]" style="color: #616161;">Up to 10 images, 2MB each</p>
                        </div>
                        <div x-show="galleryPreviews.length > 0" x-transition class="mt-3">
                            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
                                <template x-for="(preview, index) in galleryPreviews" :key="index">
                                    <div class="relative group rounded-lg overflow-hidden aspect-square" style="border: 1px solid #e3e3e3;">
                                        <img :src="preview.url" style="width: 100%; height: 100%; object-fit: cover;">
                                        <button type="button" @click="removeGalleryImage(index)"
                                                class="absolute top-1 right-1 w-5 h-5 bg-white rounded-full flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-opacity">
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
                                    <input type="number" name="price" id="price" value="{{ old('price') }}" required step="0.01" min="0"
                                           class="form-input w-full pl-7 @error('price') form-input-error @enderror">
                                </div>
                                @error('price') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="sale_price" class="form-label">Compare-at price</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px]" style="color: #616161;">₹</span>
                                    <input type="number" name="sale_price" id="sale_price" value="{{ old('sale_price') }}" step="0.01" min="0"
                                           class="form-input w-full pl-7 @error('sale_price') form-input-error @enderror">
                                </div>
                                @error('sale_price') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="cost_price" class="form-label">Cost per item</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px]" style="color: #616161;">₹</span>
                                    <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price') }}" step="0.01" min="0"
                                           class="form-input w-full pl-7 @error('cost_price') form-input-error @enderror">
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
                                       class="form-input w-full @error('sku') form-input-error @enderror">
                                @error('sku') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="barcode" class="form-label">Barcode (EAN/UPC)</label>
                                <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}"
                                       class="form-input w-full @error('barcode') form-input-error @enderror">
                                @error('barcode') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="stock_quantity" class="form-label form-label-required">Quantity</label>
                                <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', 0) }}" required min="0"
                                       class="form-input w-full @error('stock_quantity') form-input-error @enderror">
                                @error('stock_quantity') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Attributes -->
                    @if($attributes->count())
                    <div class="card p-5">
                        <h2 class="text-[13px] font-semibold mb-1" style="color: #303030;">Attributes</h2>
                        <p class="text-xs mb-4" style="color: #616161;">Product specifications and variants</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($attributes as $attribute)
                                <div>
                                    <label class="form-label">{{ $attribute->name }}</label>
                                    @if($attribute->type === 'text')
                                        <input type="text" name="product_attributes[{{ $attribute->name }}]"
                                               value="{{ old('product_attributes.' . $attribute->name) }}"
                                               class="form-input w-full text-sm" placeholder="Enter {{ strtolower($attribute->name) }}">
                                    @else
                                        <select name="product_attributes[{{ $attribute->name }}]" class="form-input w-full text-sm">
                                            <option value="">Select</option>
                                            @foreach($attribute->values as $value)
                                                <option value="{{ $value->value }}" {{ old('product_attributes.' . $attribute->name) === $value->value ? 'selected' : '' }}>{{ $value->value }}</option>
                                            @endforeach
                                        </select>
                                        @if($attribute->type === 'color' && $attribute->values->count())
                                            <div class="flex flex-wrap gap-1 mt-2">
                                                @foreach($attribute->values->take(10) as $value)
                                                    @if($value->color_code)
                                                        <div style="width: 1.25rem; height: 1.25rem; border-radius: 9999px; border: 1px solid #e5e5e5; background-color: {{ $value->color_code }}" title="{{ $value->value }}"></div>
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
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
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
                            <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title') }}"
                                   class="form-input w-full @error('meta_title') form-input-error @enderror" placeholder="Product name">
                            @error('meta_title') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="meta_description" class="form-label">Meta description</label>
                            <textarea name="meta_description" id="meta_description" rows="3"
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

        function imageUploader() {
            return {
                mainPreview: null,
                mainDragOver: false,
                galleryPreviews: [],
                galleryDragOver: false,
                galleryFileList: new DataTransfer(),
                handleMainImage(file) {
                    if (!file || !file.type.startsWith('image/')) return;
                    if (file.size > 2 * 1024 * 1024) { toastr.error(file.name + ' exceeds 2MB.'); return; }
                    const dt = new DataTransfer(); dt.items.add(file);
                    this.$refs.mainFileInput.files = dt.files;
                    const reader = new FileReader();
                    reader.onload = (e) => { this.mainPreview = e.target.result; };
                    reader.readAsDataURL(file);
                },
                removeMainImage() { this.mainPreview = null; this.$refs.mainFileInput.value = ''; },
                handleGalleryFiles(files) {
                    for (const file of files) {
                        if (!file.type.startsWith('image/')) continue;
                        if (file.size > 2 * 1024 * 1024) { toastr.error(file.name + ' exceeds 2MB.'); continue; }
                        if (this.galleryPreviews.length >= 10) { toastr.error('Max 10 gallery images.'); break; }
                        this.galleryFileList.items.add(file);
                        const reader = new FileReader();
                        reader.onload = (e) => { this.galleryPreviews.push({ url: e.target.result, name: file.name }); };
                        reader.readAsDataURL(file);
                    }
                    this.$refs.galleryInput.files = this.galleryFileList.files;
                },
                removeGalleryImage(index) { this.galleryPreviews.splice(index, 1); this.galleryFileList.items.remove(index); this.$refs.galleryInput.files = this.galleryFileList.files; }
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
