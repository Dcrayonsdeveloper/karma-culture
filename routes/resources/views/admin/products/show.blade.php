<x-layouts.admin>
    <x-slot name="title">{{ $product->name }}</x-slot>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <a href="{{ route('admin.products.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Products
            </a>
            <span style="color: #c9cccf;">/</span>
            <h1 style="font-size: 1rem; font-weight: 600; color: #303030; margin: 0;">{{ $product->name }}</h1>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('product.show', $product) }}" target="_blank"
               style="padding: 0.4rem 0.875rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; color: #303030; text-decoration: none;">
                View on Site
            </a>
            <a href="{{ route('admin.products.edit', $product) }}"
               style="padding: 0.4rem 0.875rem; background: #303030; border-radius: 0.5rem; font-size: 13px; color: #fff; text-decoration: none; font-weight: 500;">
                Edit
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;" class="lg:grid-cols-3-2">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; align-items: start;">

            <!-- Left: Details -->
            <div style="display: flex; flex-direction: column; gap: 1rem;">

                <!-- Product Info -->
                <div style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; padding: 1.25rem;">
                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                        @if($product->primary_image_url)
                            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}"
                                 style="width: 96px; height: 96px; object-fit: cover; border-radius: 0.5rem; border: 1px solid #e3e3e3; flex-shrink: 0;">
                        @else
                            <div style="width: 96px; height: 96px; background: #f1f1f1; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="32" height="32" fill="none" stroke="#c9cccf" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        @endif
                        <div style="flex: 1; min-width: 0;">
                            <h2 style="font-size: 1rem; font-weight: 600; color: #303030; margin: 0 0 0.25rem;">{{ $product->name }}</h2>
                            @if($product->sku)
                                <p style="font-size: 12px; color: #6d7175; margin: 0 0 0.5rem;">SKU: {{ $product->sku }}</p>
                            @endif
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <span style="padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; {{ $product->is_active ? 'background:#e4f5e9;color:#1a7431;' : 'background:#f1f1f1;color:#6d7175;' }}">
                                    {{ $product->is_active ? 'Active' : 'Draft' }}
                                </span>
                                @if($product->is_featured)
                                    <span style="padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; background: #fff4e4; color: #b98900;">Featured</span>
                                @endif
                                @if($product->stock_quantity <= 0)
                                    <span style="padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; background: #fee4e2; color: #b42318;">Out of Stock</span>
                                @elseif($product->isLowStock())
                                    <span style="padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; background: #fff4e4; color: #b98900;">Low Stock</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($product->short_description)
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f1f1f1;">
                            <p style="font-size: 13px; color: #6d7175;">{{ $product->short_description }}</p>
                        </div>
                    @endif
                </div>

                <!-- Pricing -->
                <div style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; padding: 1.25rem;">
                    <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 0.875rem;">Pricing</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div>
                            <p style="font-size: 11px; color: #6d7175; margin: 0 0 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">Price</p>
                            <p style="font-size: 1rem; font-weight: 600; color: #303030; margin: 0;">@price($product->price)</p>
                        </div>
                        @if($product->mrp && $product->mrp > $product->price)
                        <div>
                            <p style="font-size: 11px; color: #6d7175; margin: 0 0 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">MRP</p>
                            <p style="font-size: 1rem; font-weight: 600; color: #6d7175; text-decoration: line-through; margin: 0;">@price($product->mrp)</p>
                        </div>
                        @endif
                        @if($product->cost_price)
                        <div>
                            <p style="font-size: 11px; color: #6d7175; margin: 0 0 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">Cost Price</p>
                            <p style="font-size: 1rem; font-weight: 600; color: #303030; margin: 0;">@price($product->cost_price)</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Images -->
                @if($product->images->count())
                <div style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; padding: 1.25rem;">
                    <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 0.875rem;">Images ({{ $product->images->count() }})</h3>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        @foreach($product->images as $image)
                            <img src="{{ $image->url }}" alt="{{ $product->name }}"
                                 style="width: 72px; height: 72px; object-fit: cover; border-radius: 0.375rem; border: 2px solid {{ $image->is_primary ? '#303030' : '#e3e3e3' }};">
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Reviews -->
                @if($product->reviews->count())
                <div style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; padding: 1.25rem;">
                    <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 0.875rem;">Recent Reviews ({{ $product->reviews->count() }})</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        @foreach($product->reviews->take(5) as $review)
                            <div style="padding-bottom: 0.75rem; border-bottom: 1px solid #f1f1f1;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="font-size: 12px; font-weight: 500; color: #303030;">{{ $review->user?->name ?? 'Guest' }}</span>
                                    <div style="display: flex; gap: 1px;">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg width="12" height="12" viewBox="0 0 20 20" fill="{{ $i <= $review->rating ? '#f59e0b' : '#e5e7eb' }}"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        @endfor
                                    </div>
                                </div>
                                @if($review->comment)
                                    <p style="font-size: 12px; color: #6d7175; margin: 0;">{{ Str::limit($review->comment, 120) }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Right: Sidebar -->
            <div style="display: flex; flex-direction: column; gap: 1rem;">

                <!-- Stats -->
                <div style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; padding: 1.25rem;">
                    <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 0.875rem;">Stats</h3>
                    <dl style="display: flex; flex-direction: column; gap: 0.625rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <dt style="color: #6d7175;">Views</dt>
                            <dd style="font-weight: 500; color: #303030;">{{ number_format($product->view_count) }}</dd>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <dt style="color: #6d7175;">Sales</dt>
                            <dd style="font-weight: 500; color: #303030;">{{ number_format($product->sales_count) }}</dd>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <dt style="color: #6d7175;">Rating</dt>
                            <dd style="font-weight: 500; color: #303030;">{{ $product->rating ? number_format($product->rating, 1) . ' / 5' : 'N/A' }}</dd>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <dt style="color: #6d7175;">Reviews</dt>
                            <dd style="font-weight: 500; color: #303030;">{{ $product->review_count }}</dd>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <dt style="color: #6d7175;">Wishlisted</dt>
                            <dd style="font-weight: 500; color: #303030;">{{ $product->wishlist_count }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Stock -->
                <div style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; padding: 1.25rem;">
                    <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 0.875rem;">Inventory</h3>
                    <dl style="display: flex; flex-direction: column; gap: 0.625rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <dt style="color: #6d7175;">Stock Qty</dt>
                            <dd style="font-weight: 600; color: {{ $product->stock_quantity <= 0 ? '#b42318' : ($product->isLowStock() ? '#b98900' : '#1a7431') }};">{{ $product->stock_quantity }}</dd>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <dt style="color: #6d7175;">Low Stock At</dt>
                            <dd style="font-weight: 500; color: #303030;">{{ $product->low_stock_threshold }}</dd>
                        </div>
                        @if($product->barcode)
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <dt style="color: #6d7175;">Barcode</dt>
                            <dd style="font-weight: 500; color: #303030;">{{ $product->barcode }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>

                <!-- Organization -->
                <div style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; padding: 1.25rem;">
                    <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 0.875rem;">Organization</h3>
                    <dl style="display: flex; flex-direction: column; gap: 0.625rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <dt style="color: #6d7175;">Category</dt>
                            <dd style="font-weight: 500; color: #303030;">{{ $product->category?->name ?? '—' }}</dd>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <dt style="color: #6d7175;">Seller</dt>
                            <dd style="font-weight: 500; color: #303030;">{{ $product->seller?->store_name ?? '—' }}</dd>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <dt style="color: #6d7175;">Created</dt>
                            <dd style="font-weight: 500; color: #303030;">{{ $product->created_at->format('d M Y') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
