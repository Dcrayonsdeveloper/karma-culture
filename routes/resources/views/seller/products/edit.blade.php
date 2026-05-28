<x-layouts.seller>
    <x-slot name="title">Edit Product</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('seller.products.index') }}" class="hover:text-primary-600">Products</a>
        <span>/</span>
        <span>{{ $product->name }}</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-neutral-900">Edit Product</h1>
        <a href="{{ route('product.show', $product) }}" target="_blank" class="btn-outline">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            View Product
        </a>
    </div>

    <form action="{{ route('seller.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-neutral-900">Basic Information</h2>

                    <div>
                        <label for="name" class="block text-sm font-medium text-neutral-700 mb-1">Product Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required
                               class="form-input w-full @error('name') border-error-300 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="sku" class="block text-sm font-medium text-neutral-700 mb-1">SKU *</label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" required
                               class="form-input w-full @error('sku') border-error-300 @enderror">
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
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @if($category->children)
                                    @foreach($category->children as $child)
                                        <option value="{{ $child->id }}" {{ old('category_id', $product->category_id) == $child->id ? 'selected' : '' }}>
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
                                  class="form-input w-full @error('short_description') border-error-300 @enderror">{{ old('short_description', $product->short_description) }}</textarea>
                        @error('short_description')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-neutral-700 mb-1">Full Description *</label>
                        <textarea name="description" id="description" rows="8" required
                                  class="form-input w-full @error('description') border-error-300 @enderror">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Existing Images -->
                @if($product->images && $product->images->count())
                    <div class="card p-6 space-y-4">
                        <h2 class="font-semibold text-neutral-900">Current Images</h2>
                        <div class="grid grid-cols-4 gap-4">
                            @foreach($product->images as $image)
                                <div class="relative group">
                                    <img src="{{ $image->url }}" alt="{{ $product->name }}" class="w-full aspect-square object-cover rounded-lg">
                                    @if($image->is_primary)
                                        <span class="absolute top-2 left-2 badge badge-primary text-xs">Primary</span>
                                    @endif
                                    <button type="button" class="absolute top-2 right-2 p-1 bg-error-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                                            onclick="if(confirm('Delete this image?')) document.getElementById('delete-image-{{ $image->id }}').submit()">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Upload New Images -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-neutral-900">Add More Images</h2>
                    <div class="border-2 border-dashed border-neutral-300 rounded-lg p-8 text-center hover:border-primary-500 transition-colors">
                        <input type="file" name="images[]" multiple accept="image/*" class="hidden" id="product-images">
                        <label for="product-images" class="cursor-pointer">
                            <svg class="w-12 h-12 mx-auto text-neutral-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-neutral-600 mb-1">Click to upload more images</p>
                            <p class="text-sm text-neutral-600">PNG, JPG up to 5MB each</p>
                        </label>
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
                                <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" required
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
                                <input type="number" name="mrp" id="mrp" value="{{ old('mrp', $product->mrp) }}"
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
                                <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price', $product->cost_price) }}"
                                       step="0.01" min="0"
                                       class="form-input w-full pl-7 @error('cost_price') border-error-300 @enderror">
                            </div>
                            @error('cost_price')
                                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Inventory -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-neutral-900">Inventory</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="stock_quantity" class="block text-sm font-medium text-neutral-700 mb-1">Stock Quantity *</label>
                            <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" required
                                   min="0"
                                   class="form-input w-full @error('stock_quantity') border-error-300 @enderror">
                            @error('stock_quantity')
                                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="low_stock_threshold" class="block text-sm font-medium text-neutral-700 mb-1">Low Stock Alert</label>
                            <input type="number" name="low_stock_threshold" id="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 10) }}"
                                   min="0"
                                   class="form-input w-full">
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
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                                   class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-neutral-700">Active</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}
                                   class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-neutral-700">Featured</span>
                        </label>
                    </div>
                </div>

                <!-- Product Stats -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-neutral-900">Statistics</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Total Sold</dt>
                            <dd class="font-medium">{{ number_format($product->total_sold ?? 0) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Views</dt>
                            <dd class="font-medium">{{ number_format($product->views_count ?? 0) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Rating</dt>
                            <dd class="font-medium">{{ number_format($product->average_rating ?? 0, 1) }}/5</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Created</dt>
                            <dd class="font-medium">{{ $product->created_at->format('M d, Y') }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- SEO -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-neutral-900">SEO</h2>

                    <div>
                        <label for="meta_title" class="block text-sm font-medium text-neutral-700 mb-1">Meta Title</label>
                        <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title', $product->meta_title) }}"
                               class="form-input w-full">
                    </div>

                    <div>
                        <label for="meta_description" class="block text-sm font-medium text-neutral-700 mb-1">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" rows="3"
                                  class="form-input w-full">{{ old('meta_description', $product->meta_description) }}</textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card p-6 space-y-3">
                    <button type="submit" class="btn-primary w-full">Update Product</button>
                    <a href="{{ route('seller.products.index') }}" class="btn-outline w-full text-center">Cancel</a>
                </div>

                <!-- Danger Zone -->
                <div class="card p-6 border-error-200">
                    <h2 class="font-semibold text-error-600 mb-4">Danger Zone</h2>
                    <form action="{{ route('seller.products.destroy', $product) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-outline w-full text-error-600 border-error-300 hover:bg-error-50">
                            Delete Product
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </form>
</x-layouts.seller>
