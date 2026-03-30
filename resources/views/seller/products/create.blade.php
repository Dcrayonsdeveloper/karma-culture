<x-layouts.seller>
    <x-slot name="title">Add Product</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('seller.products.index') }}" class="hover:text-primary-600">Products</a>
        <span>/</span>
        <span>Add Product</span>
    </div>

    <h1 class="text-2xl font-bold text-neutral-900 mb-6">Add New Product</h1>

    <form action="{{ route('seller.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-neutral-900">Basic Information</h2>

                    <div>
                        <label for="name" class="block text-sm font-medium text-neutral-700 mb-1">Product Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="form-input w-full @error('name') border-error-300 @enderror"
                               placeholder="Enter product name">
                        @error('name')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="sku" class="block text-sm font-medium text-neutral-700 mb-1">SKU *</label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku') }}" required
                               class="form-input w-full @error('sku') border-error-300 @enderror"
                               placeholder="e.g., PROD-001">
                        @error('sku')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="category_id" class="block text-sm font-medium text-neutral-700 mb-1">Category *</label>
                        <select name="category_id" id="category_id" required
                                class="form-input w-full @error('category_id') border-error-300 @enderror">
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @if($category->children)
                                    @foreach($category->children as $child)
                                        <option value="{{ $child->id }}" {{ old('category_id') == $child->id ? 'selected' : '' }}>
                                            &nbsp;&nbsp;-- {{ $child->name }}
                                        </option>
                                    @endforeach
                                @endif
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="short_description" class="block text-sm font-medium text-neutral-700 mb-1">Short Description</label>
                        <textarea name="short_description" id="short_description" rows="2"
                                  class="form-input w-full @error('short_description') border-error-300 @enderror"
                                  placeholder="Brief description for product listings...">{{ old('short_description') }}</textarea>
                        @error('short_description')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-neutral-700 mb-1">Full Description *</label>
                        <textarea name="description" id="description" rows="8" required
                                  class="form-input w-full @error('description') border-error-300 @enderror"
                                  placeholder="Detailed product description...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Images -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-neutral-900">Product Images</h2>

                    <div x-data="{ previews: [] }">
                        <label class="block text-sm font-medium text-neutral-700 mb-2">Images</label>
                        <div class="border-2 border-dashed border-neutral-300 rounded-lg p-8 text-center hover:border-primary-500 transition-colors">
                            <input type="file" name="images[]" multiple accept="image/*"
                                   class="hidden" id="product-images"
                                   @change="previews = Array.from($event.target.files).map(f => URL.createObjectURL(f))">
                            <label for="product-images" class="cursor-pointer">
                                <svg class="w-12 h-12 mx-auto text-neutral-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-neutral-600 mb-1">Click to upload images</p>
                                <p class="text-sm text-neutral-600">PNG, JPG up to 5MB each</p>
                            </label>
                        </div>
                        <div x-show="previews.length > 0" class="flex flex-wrap gap-4 mt-4">
                            <template x-for="(preview, index) in previews" :key="index">
                                <div class="relative w-24 h-24">
                                    <img :src="preview" class="w-full h-full object-cover rounded-lg">
                                    <span x-show="index === 0" class="absolute top-1 left-1 badge badge-primary text-xs">Primary</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-neutral-900">Pricing</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="price" class="block text-sm font-medium text-neutral-700 mb-1">Regular Price *</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-600">$</span>
                                <input type="number" name="price" id="price" value="{{ old('price') }}" required
                                       step="0.01" min="0"
                                       class="form-input w-full pl-7 @error('price') border-error-300 @enderror">
                            </div>
                            @error('price')
                                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="mrp" class="block text-sm font-medium text-neutral-700 mb-1">MRP / Compare Price</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-600">$</span>
                                <input type="number" name="mrp" id="mrp" value="{{ old('mrp') }}"
                                       step="0.01" min="0"
                                       class="form-input w-full pl-7 @error('mrp') border-error-300 @enderror">
                            </div>
                            @error('mrp')
                                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="cost_price" class="block text-sm font-medium text-neutral-700 mb-1">Cost Price</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-600">$</span>
                                <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price') }}"
                                       step="0.01" min="0"
                                       class="form-input w-full pl-7 @error('cost_price') border-error-300 @enderror">
                            </div>
                            @error('cost_price')
                                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-neutral-600 mt-1">For your reference only</p>
                        </div>
                    </div>
                </div>

                <!-- Inventory -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-neutral-900">Inventory</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="stock_quantity" class="block text-sm font-medium text-neutral-700 mb-1">Stock Quantity *</label>
                            <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', 0) }}" required
                                   min="0"
                                   class="form-input w-full @error('stock_quantity') border-error-300 @enderror">
                            @error('stock_quantity')
                                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="low_stock_threshold" class="block text-sm font-medium text-neutral-700 mb-1">Low Stock Alert</label>
                            <input type="number" name="low_stock_threshold" id="low_stock_threshold" value="{{ old('low_stock_threshold', 10) }}"
                                   min="0"
                                   class="form-input w-full @error('low_stock_threshold') border-error-300 @enderror">
                            @error('low_stock_threshold')
                                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Shipping -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-neutral-900">Shipping</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="weight" class="block text-sm font-medium text-neutral-700 mb-1">Weight (kg)</label>
                            <input type="number" name="weight" id="weight" value="{{ old('weight') }}"
                                   step="0.01" min="0"
                                   class="form-input w-full @error('weight') border-error-300 @enderror">
                            @error('weight')
                                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Dimensions (cm)</label>
                            <div class="grid grid-cols-3 gap-2">
                                <input type="number" name="length" placeholder="L" value="{{ old('length') }}"
                                       step="0.1" min="0" class="form-input">
                                <input type="number" name="width" placeholder="W" value="{{ old('width') }}"
                                       step="0.1" min="0" class="form-input">
                                <input type="number" name="height" placeholder="H" value="{{ old('height') }}"
                                       step="0.1" min="0" class="form-input">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Status -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-neutral-900">Status</h2>

                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                   class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-neutral-700">Active (visible to customers)</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                                   class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-neutral-700">Featured product</span>
                        </label>
                    </div>
                </div>

                <!-- Brand -->
                @if(isset($brands) && $brands->count())
                    <div class="card p-6 space-y-4">
                        <h2 class="font-semibold text-neutral-900">Brand</h2>
                        <select name="brand_id" class="form-input w-full">
                            <option value="">Select brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- SEO -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-neutral-900">SEO</h2>

                    <div>
                        <label for="meta_title" class="block text-sm font-medium text-neutral-700 mb-1">Meta Title</label>
                        <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title') }}"
                               class="form-input w-full" placeholder="SEO title">
                    </div>

                    <div>
                        <label for="meta_description" class="block text-sm font-medium text-neutral-700 mb-1">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" rows="3"
                                  class="form-input w-full" placeholder="SEO description">{{ old('meta_description') }}</textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card p-6">
                    <button type="submit" class="btn-primary w-full mb-3">Create Product</button>
                    <a href="{{ route('seller.products.index') }}" class="btn-outline w-full text-center">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</x-layouts.seller>
