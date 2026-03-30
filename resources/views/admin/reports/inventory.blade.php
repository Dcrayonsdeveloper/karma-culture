<x-layouts.admin>
    <x-slot name="title">Inventory Report</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Inventory Report</h1>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                {{-- intentionally empty for symmetry; no period selector on inventory --}}
            </div>
        </div>
    </x-slot>

    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Total Products</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total'] ?? 0) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Active</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['active'] ?? 0) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Out of Stock</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #d72c0d;">{{ number_format($stats['out_of_stock'] ?? 0) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Low Stock</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #b98900;">{{ number_format($stats['low_stock'] ?? 0) }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card" style="margin-bottom: 1rem;" x-data="{ open: {{ request()->hasAny(['search', 'stock_status', 'category']) ? 'true' : 'false' }} }">
        <div style="padding: 0.75rem 1rem; display: flex; align-items: center; justify-content: space-between; cursor: pointer;" @click="open = !open">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 13px; font-weight: 500; color: #303030;">
                <svg style="width: 1rem; height: 1rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filters & Search
                @if(request()->hasAny(['search', 'stock_status', 'category']))
                    <span class="badge badge-primary">Active</span>
                @endif
            </div>
            <svg style="width: 1rem; height: 1rem; color: #616161; transition: transform 0.2s;" :style="open ? 'transform: rotate(180deg)' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div x-show="open" x-cloak x-transition style="padding: 0 1rem 1rem 1rem; border-top: 1px solid #e3e3e3;">
            <form action="{{ route('admin.reports.inventory') }}" method="GET" style="padding-top: 1rem;">
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
                    <div>
                        <label class="form-label">Stock Status</label>
                        <select name="stock_status" class="form-input" style="width: 100%;">
                            <option value="">All Status</option>
                            <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                            <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                            <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Category</label>
                        <select name="category" class="form-input" style="width: 100%;">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Product name or SKU..."
                               class="form-input" style="width: 100%;">
                    </div>
                    <div>
                        <label class="form-label">&nbsp;</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary" style="flex-shrink: 0;" title="Apply filters">
                                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                            @if(request()->hasAny(['search', 'stock_status', 'category']))
                                <a href="{{ route('admin.reports.inventory') }}" class="btn btn-secondary" style="flex-shrink: 0;" title="Reset filters">
                                    <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        @if($products->total() > 0)
            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                <p style="font-size: 13px; color: #616161; margin: 0;">
                    Showing <span style="font-weight: 500; color: #303030;">{{ $products->firstItem() }}</span>&ndash;<span style="font-weight: 500; color: #303030;">{{ $products->lastItem() }}</span> of <span style="font-weight: 500; color: #303030;">{{ $products->total() }}</span> products
                </p>
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Product Name</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">SKU</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Category</th>
                        <th style="padding: 0.5rem 1rem; text-align: center; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">
                            <a href="{{ route('admin.reports.inventory', array_merge(request()->query(), [
                                'sort' => 'stock_quantity',
                                'direction' => request('sort') === 'stock_quantity' && request('direction') === 'asc' ? 'desc' : 'asc'
                            ])) }}" style="display: inline-flex; align-items: center; gap: 0.25rem; color: #616161; text-decoration: none;">
                                Stock Qty
                                @if(request('sort') === 'stock_quantity')
                                    <svg style="width: 0.75rem; height: 0.75rem; {{ request('direction') === 'desc' ? 'transform: rotate(180deg);' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                @else
                                    <svg style="width: 0.75rem; height: 0.75rem; color: #babfc3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th style="padding: 0.5rem 1rem; text-align: center; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Last Movement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem; font-weight: 500; color: #303030;">{{ $product->name }}</td>
                            <td style="padding: 0.625rem 1rem;">
                                <span style="font-size: 12px; font-family: monospace; background: #f6f6f7; color: #616161; padding: 0.125rem 0.375rem; border-radius: 0.25rem;">{{ $product->sku ?? '—' }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">
                                {{ $product->category->name ?? '—' }}
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: center;">
                                @php
                                    $qty = $product->stock_quantity;
                                    $threshold = $product->low_stock_threshold;
                                    $isOut = $qty <= 0;
                                    $isLow = !$isOut && $threshold && $qty <= $threshold;
                                    $qtyColor = $isOut ? '#d72c0d' : ($isLow ? '#b98900' : '#1a7a2e');
                                @endphp
                                <span style="font-weight: 600; color: {{ $qtyColor }};">{{ $qty }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: center;">
                                @if($product->stock_quantity <= 0)
                                    <span class="badge badge-error">Out of Stock</span>
                                @elseif($product->low_stock_threshold && $product->stock_quantity <= $product->low_stock_threshold)
                                    <span class="badge badge-warning">Low Stock</span>
                                @else
                                    <span class="badge badge-success">In Stock</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">
                                @if($product->last_movement_at)
                                    {{ \Carbon\Carbon::parse($product->last_movement_at)->format('M d, Y') }}
                                @else
                                    <span style="color: #616161;">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 4rem; height: 4rem; border-radius: 50%; background: #f6f6f7; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                                        <svg style="width: 2rem; height: 2rem; color: #babfc3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin: 0 0 0.25rem 0;">No products found</h3>
                                    <p style="font-size: 13px; color: #616161; margin: 0;">
                                        @if(request()->hasAny(['search', 'stock_status', 'category']))
                                            Try adjusting your filters to find what you're looking for.
                                        @else
                                            Product inventory data will appear here.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
