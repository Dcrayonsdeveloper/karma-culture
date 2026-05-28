<x-layouts.admin>
    <x-slot name="title">{{ $category->name }}</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.categories.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Categories
        </a>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">{{ $category->name }}</h1>
            @if($category->is_active)
                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
            @else
                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #f6f6f7; color: #616161;">Inactive</span>
            @endif
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <a href="{{ route('category.show', $category) }}" target="_blank" class="btn btn-secondary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 0.25rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                View on Site
            </a>
            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 0.25rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
        </div>
    </div>

    <!-- Stats row -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 12px; color: #616161; margin: 0 0 0.25rem 0;">Products</p>
            <p style="font-size: 1.125rem; font-weight: 700; color: #303030; margin: 0;">{{ $products->total() }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 12px; color: #616161; margin: 0 0 0.25rem 0;">Subcategories</p>
            <p style="font-size: 1.125rem; font-weight: 700; color: #303030; margin: 0;">{{ $category->children->count() }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 12px; color: #616161; margin: 0 0 0.25rem 0;">Status</p>
            <p style="font-size: 1.125rem; font-weight: 700; color: {{ $category->is_active ? '#1a7a2e' : '#616161' }}; margin: 0;">{{ $category->is_active ? 'Active' : 'Inactive' }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 12px; color: #616161; margin: 0 0 0.25rem 0;">Created</p>
            <p style="font-size: 13px; font-weight: 700; color: #303030; margin: 0;">{{ $category->created_at->format('M d, Y') }}</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
        <!-- Main Content -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Products in Category -->
            <div class="card" style="overflow: hidden;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Products</h2>
                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #e0f0ff; color: #005bd3;">{{ $products->total() }}</span>
                    </div>
                    @if($products->total() > 0)
                        <a href="{{ route('admin.products.index', ['category' => $category->id]) }}" style="font-size: 13px; color: #005bd3; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;">
                            View All
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endif
                </div>

                @if($products->count() > 0)
                    @if($products->total() > 0)
                        <div style="padding: 0.625rem 1rem; border-bottom: 1px solid #e3e3e3;">
                            {{ $products->links('vendor.pagination.info-bar') }}
                        </div>
                    @endif
                    <div>
                        @foreach($products as $product)
                            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #f0f0f0;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <!-- Product Image -->
                                    <a href="{{ route('admin.products.edit', $product) }}" style="flex-shrink: 0; text-decoration: none;">
                                        @if($product->primary_image_url)
                                            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}"
                                                 style="width: 48px; height: 48px; border-radius: 0.5rem; object-fit: cover; border: 1px solid #e3e3e3;">
                                        @else
                                            <div style="width: 48px; height: 48px; border-radius: 0.5rem; background: #f6f6f7; border: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                                                <svg width="20" height="20" fill="none" stroke="#c9cccf" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </a>

                                    <!-- Product Info -->
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem;">
                                            <div style="min-width: 0;">
                                                <a href="{{ route('admin.products.edit', $product) }}"
                                                   style="font-size: 13px; font-weight: 600; color: #303030; text-decoration: none; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    {{ $product->name }}
                                                </a>
                                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.25rem;">
                                                    @if($product->seller)
                                                        <span style="font-size: 12px; color: #616161;">{{ $product->seller->store_name }}</span>
                                                    @endif
                                                    @if($product->sku ?? false)
                                                        <span style="font-size: 12px; color: #616161; font-family: monospace;">SKU: {{ $product->sku }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div style="text-align: right; flex-shrink: 0;">
                                                <p style="font-size: 13px; font-weight: 700; color: #303030; font-family: monospace; margin: 0;">@price($product->price)</p>
                                                @if($product->is_active)
                                                    <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 12px; color: #1a7a2e;">
                                                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #1a7a2e; display: inline-block;"></span>
                                                        Active
                                                    </span>
                                                @else
                                                    <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 12px; color: #616161;">
                                                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #c9cccf; display: inline-block;"></span>
                                                        Inactive
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($products->hasPages())
                        <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                            {{ $products->links() }}
                        </div>
                    @endif
                @else
                    <div style="padding: 3rem 1rem; text-align: center;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: #f6f6f7; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
                            <svg width="24" height="24" fill="none" stroke="#c9cccf" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 0.25rem 0;">No products yet</h3>
                        <p style="font-size: 12px; color: #616161; margin: 0;">Products assigned to this category will appear here.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Category Details -->
            <div class="card" style="overflow: hidden;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Details</h2>
                </div>
                <div style="padding: 1rem;">
                    @if($category->image_url)
                        <div style="margin-bottom: 1rem; display: flex; justify-content: center;">
                            <div style="width: 96px; height: 96px; border-radius: 0.75rem; overflow: hidden; border: 1px solid #e3e3e3;">
                                <img src="{{ asset('storage/' . $category->image_url) }}" alt="{{ $category->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        </div>
                    @endif
                    <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 13px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="color: #616161;">Slug</span>
                            <span style="font-family: monospace; font-size: 12px; color: #303030; background: #f6f6f7; padding: 0.125rem 0.5rem; border-radius: 0.25rem;">{{ $category->slug }}</span>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="color: #616161;">Parent</span>
                            @if($category->parent)
                                <a href="{{ route('admin.categories.show', $category->parent) }}" style="color: #005bd3; text-decoration: none; font-weight: 500;">{{ $category->parent->name }}</a>
                            @else
                                <span style="color: #616161;">Root</span>
                            @endif
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="color: #616161;">Sort Order</span>
                            <span style="font-weight: 500; color: #303030;">{{ $category->sort_order }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card" style="overflow: hidden;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Quick Actions</h2>
                </div>
                <div style="padding: 0.5rem; display: flex; flex-direction: column; gap: 0.125rem;">
                    <a href="{{ route('admin.categories.edit', $category) }}" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem; border-radius: 0.5rem; text-decoration: none;">
                        <div style="width: 32px; height: 32px; border-radius: 0.5rem; background: #e0f0ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg width="16" height="16" fill="none" stroke="#005bd3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">Edit Category</p>
                            <p style="font-size: 12px; color: #616161; margin: 0;">Modify name, description, SEO</p>
                        </div>
                    </a>
                    <form action="{{ route('admin.categories.toggle-status', $category) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <button type="submit" style="width: 100%; display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem; border-radius: 0.5rem; background: none; border: none; cursor: pointer; text-align: left;">
                            <div style="width: 32px; height: 32px; border-radius: 0.5rem; background: {{ $category->is_active ? '#fff3cd' : '#cdfee1' }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                @if($category->is_active)
                                    <svg width="16" height="16" fill="none" stroke="#b98900" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                @else
                                    <svg width="16" height="16" fill="none" stroke="#1a7a2e" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">
                                    {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                                </p>
                                <p style="font-size: 12px; color: #616161; margin: 0;">
                                    {{ $category->is_active ? 'Hide from storefront' : 'Make visible on storefront' }}
                                </p>
                            </div>
                        </button>
                    </form>
                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
                          onsubmit="return confirm('Delete &quot;{{ $category->name }}&quot;? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="width: 100%; display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem; border-radius: 0.5rem; background: none; border: none; cursor: pointer; text-align: left;">
                            <div style="width: 32px; height: 32px; border-radius: 0.5rem; background: #ffe0db; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="16" height="16" fill="none" stroke="#d72c0d" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </div>
                            <div>
                                <p style="font-size: 13px; font-weight: 500; color: #d72c0d; margin: 0;">Delete Category</p>
                                <p style="font-size: 12px; color: #616161; margin: 0;">Permanently remove this category</p>
                            </div>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Subcategories -->
            @if($category->children->count())
                <div class="card" style="overflow: hidden;">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; gap: 0.5rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Subcategories</h2>
                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #f6f6f7; color: #616161;">{{ $category->children->count() }}</span>
                    </div>
                    <div>
                        @foreach($category->children as $child)
                            <a href="{{ route('admin.categories.show', $child) }}"
                               style="display: flex; align-items: center; justify-content: space-between; padding: 0.625rem 1rem; border-bottom: 1px solid #f0f0f0; text-decoration: none;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 28px; height: 28px; border-radius: 0.25rem; background: #f6f6f7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <svg width="14" height="14" fill="none" stroke="#616161" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/>
                                        </svg>
                                    </div>
                                    <span style="font-size: 13px; font-weight: 500; color: #303030;">{{ $child->name }}</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    @if($child->is_active)
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #1a7a2e; display: inline-block;"></span>
                                    @else
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #c9cccf; display: inline-block;"></span>
                                    @endif
                                    <svg width="16" height="16" fill="none" stroke="#c9cccf" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Description -->
            @if($category->description)
                <div class="card" style="overflow: hidden;">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Description</h2>
                    </div>
                    <div style="padding: 1rem;">
                        <p style="font-size: 13px; color: #616161; line-height: 1.6; margin: 0;">{{ $category->description }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
