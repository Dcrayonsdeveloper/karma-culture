<x-layouts.admin>
    <x-slot name="title">Inventory</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Inventory</h1>
            <div style="display: flex; gap: 0.5rem;">
                <a href="{{ route('admin.inventory.movements') }}" class="btn btn-secondary" style="font-size: 13px; display: inline-flex; align-items: center; gap: 0.375rem;">
                    <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Movements
                </a>
                <a href="{{ route('admin.inventory.locations.index') }}" class="btn btn-secondary" style="font-size: 13px; display: inline-flex; align-items: center; gap: 0.375rem;">
                    <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Locations
                </a>
                <button onclick="openStockModal(null, '', 0)" class="btn btn-primary" style="font-size: 13px; display: inline-flex; align-items: center; gap: 0.375rem;">
                    <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Adjust Stock
                </button>
            </div>
        </div>
    </x-slot>

    {{-- Stats Row --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: #fff; padding: 0.875rem 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 2.25rem; height: 2.25rem; background: #f6f6f7; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg style="width: 1.125rem; height: 1.125rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <p style="font-size: 12px; color: #616161; margin-bottom: 2px;">Total Products</p>
                    <p style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <a href="{{ route('admin.inventory.index', ['status' => 'in_stock']) }}" style="background: #fff; padding: 0.875rem 1rem; text-decoration: none; display: block;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 2.25rem; height: 2.25rem; background: #cdfee1; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg style="width: 1.125rem; height: 1.125rem; color: #1a7a2e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p style="font-size: 12px; color: #616161; margin-bottom: 2px;">In Stock</p>
                    <p style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e; margin: 0;">{{ $stats['in_stock'] }}</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.inventory.low-stock') }}" style="background: #fff; padding: 0.875rem 1rem; text-decoration: none; display: block;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 2.25rem; height: 2.25rem; background: #fff3cd; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg style="width: 1.125rem; height: 1.125rem; color: #b98900;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p style="font-size: 12px; color: #616161; margin-bottom: 2px;">Low Stock</p>
                    <p style="font-size: 1.25rem; font-weight: 600; color: #b98900; margin: 0;">{{ $stats['low_stock'] }}</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.inventory.out-of-stock') }}" style="background: #fff; padding: 0.875rem 1rem; text-decoration: none; display: block;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 2.25rem; height: 2.25rem; background: #ffe0db; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg style="width: 1.125rem; height: 1.125rem; color: #d72c0d;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p style="font-size: 12px; color: #616161; margin-bottom: 2px;">Out of Stock</p>
                    <p style="font-size: 1.25rem; font-weight: 600; color: #d72c0d; margin: 0;">{{ $stats['out_of_stock'] }}</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Main Card with Tabs + Search + Table --}}
    <div style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; overflow: hidden;">

        {{-- Tab Filters --}}
        <div style="display: flex; border-bottom: 1px solid #e3e3e3;">
            <a href="{{ route('admin.inventory.index', request()->except('status', 'page')) }}"
               style="padding: 0.75rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#303030' : 'transparent' }}; color: {{ !request('status') ? '#303030' : '#616161' }};">
                All
            </a>
            <a href="{{ route('admin.inventory.index', array_merge(request()->except('page'), ['status' => 'in_stock'])) }}"
               style="padding: 0.75rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'in_stock' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'in_stock' ? '#303030' : '#616161' }};">
                In Stock
            </a>
            <a href="{{ route('admin.inventory.index', array_merge(request()->except('page'), ['status' => 'low_stock'])) }}"
               style="padding: 0.75rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'low_stock' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'low_stock' ? '#303030' : '#616161' }};">
                Low Stock
            </a>
            <a href="{{ route('admin.inventory.index', array_merge(request()->except('page'), ['status' => 'out_of_stock'])) }}"
               style="padding: 0.75rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'out_of_stock' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'out_of_stock' ? '#303030' : '#616161' }};">
                Out of Stock
            </a>
        </div>

        {{-- Search + Per Page --}}
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; gap: 0.75rem;">
            <form action="{{ route('admin.inventory.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div style="position: relative; flex: 1; max-width: 320px;">
                    <svg style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..."
                           style="width: 100%; padding: 0.4rem 0.5rem 0.4rem 1.75rem; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; outline: none; color: #303030;">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
                @if(request('search'))
                    <a href="{{ route('admin.inventory.index', request()->except('search', 'page')) }}" style="padding: 0.4rem 0.75rem; font-size: 13px; color: #616161; text-decoration: none;">Clear</a>
                @endif
                <div style="margin-left: auto;">
                    <select name="per_page" onchange="this.form.submit()" style="padding: 0.4rem 0.5rem; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; color: #303030; background: #fff; outline: none;">
                        @foreach([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>{{ $n }} per page</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        {{-- Results Count --}}
        @if($products->total() > 0)
            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e3e3e3; background: #f6f6f7;">
                <p style="font-size: 12px; color: #616161; margin: 0;">
                    Showing <span style="font-weight: 600; color: #303030;">{{ $products->firstItem() }}</span>&ndash;<span style="font-weight: 600; color: #303030;">{{ $products->lastItem() }}</span> of <span style="font-weight: 600; color: #303030;">{{ $products->total() }}</span> products
                </p>
            </div>
        @endif

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th style="text-align: center;">Stock</th>
                        <th style="text-align: center;">Threshold</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                <span style="font-weight: 500; color: #303030; font-size: 13px;">{{ $product->name }}</span>
                            </td>
                            <td>
                                <span style="font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace; font-size: 12px; background: #f6f6f7; color: #616161; padding: 0.125rem 0.375rem; border-radius: 0.25rem;">{{ $product->sku ?? '—' }}</span>
                            </td>
                            <td style="text-align: center;">
                                @php
                                    $qty = $product->stock_quantity;
                                    $threshold = $product->low_stock_threshold;
                                    $isOut = $qty <= 0;
                                    $isLow = !$isOut && $threshold && $qty <= $threshold;
                                    $qtyColor = $isOut ? '#d72c0d' : ($isLow ? '#b98900' : '#1a7a2e');
                                @endphp
                                <span style="font-size: 13px; font-weight: 700; color: {{ $qtyColor }};">{{ $qty }}</span>
                            </td>
                            <td style="text-align: center; color: #616161;">
                                {{ $product->low_stock_threshold ?? '—' }}
                            </td>
                            <td>
                                @if($product->stock_quantity <= 0)
                                    <span class="badge badge-error">Out of Stock</span>
                                @elseif($product->low_stock_threshold && $product->stock_quantity <= $product->low_stock_threshold)
                                    <span class="badge badge-warning">Low Stock</span>
                                @else
                                    <span class="badge badge-success">In Stock</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <button onclick="openStockModal({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->stock_quantity }})"
                                        style="display: inline-flex; align-items: center; padding: 0.25rem 0.625rem; font-size: 12px; font-weight: 500; color: #303030; background: #fff; border: 1px solid #c9cccf; border-radius: 0.375rem; cursor: pointer; gap: 0.25rem;">
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Adjust
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 4rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                    <div style="width: 3rem; height: 3rem; background: #f6f6f7; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <svg style="width: 1.5rem; height: 1.5rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">No products found</p>
                                    @if(request()->hasAny(['search', 'status']))
                                        <a href="{{ route('admin.inventory.index') }}" style="font-size: 13px; color: #005bd3; text-decoration: none;">Clear filters</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    {{-- Update Stock Modal --}}
    <div x-data="{ open: false, productId: null, productName: '', currentStock: 0, isNew: false }"
         x-on:open-stock-modal.window="open = true; productId = $event.detail.id; productName = $event.detail.name; currentStock = $event.detail.stock; isNew = !$event.detail.id"
         x-show="open" x-cloak
         style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center;">

        {{-- Overlay --}}
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5);" x-on:click="open = false"></div>

        {{-- Modal Card --}}
        <div style="position: relative; background: #fff; border-radius: 0.75rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); width: 100%; max-width: 28rem; margin: 0 1rem;" x-transition>

            {{-- Modal Header --}}
            <div style="padding: 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h3 style="margin: 0; font-size: 14px; font-weight: 600; color: #303030;" x-text="'Adjust Stock'"></h3>
                    <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;" x-show="!isNew" x-text="productName"></p>
                </div>
                <button type="button" x-on:click="open = false" style="background: none; border: none; cursor: pointer; padding: 0.25rem; border-radius: 0.375rem; color: #616161; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Current Stock Info --}}
            <div style="padding: 0.75rem 1rem; background: #f6f6f7; border-bottom: 1px solid #e3e3e3;" x-show="!isNew">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 12px; color: #616161;">Current Stock</span>
                    <span style="font-size: 13px; font-weight: 700; color: #303030;" x-text="currentStock"></span>
                </div>
            </div>

            <form method="POST" x-bind:action="'/admin/inventory/' + productId + '/stock'">
                @csrf
                @method('PUT')

                {{-- Modal Body --}}
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div x-show="isNew">
                        <label class="form-label" style="display: block; font-size: 12px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">Product <span style="color: #d72c0d;">*</span></label>
                        <select class="form-select" style="width: 100%; padding: 0.4rem 0.5rem; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; color: #303030; background: #fff; outline: none;"
                                x-on:change="productId = $event.target.value; let opt = $event.target.selectedOptions[0]; currentStock = opt.dataset.stock || 0" x-bind:required="isNew">
                            <option value="">Select a product...</option>
                            @foreach(\App\Models\Product::select('id', 'name', 'stock_quantity')->orderBy('name')->get() as $p)
                                <option value="{{ $p->id }}" data-stock="{{ $p->stock_quantity }}">{{ $p->name }} (Stock: {{ $p->stock_quantity }})</option>
                            @endforeach
                        </select>
                        <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;" x-show="productId && isNew">Current stock: <span style="font-weight: 600; color: #303030;" x-text="currentStock"></span></p>
                    </div>
                    <div>
                        <label class="form-label" style="display: block; font-size: 12px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">Adjustment Type <span style="color: #d72c0d;">*</span></label>
                        <select name="type" class="form-select" required style="width: 100%; padding: 0.4rem 0.5rem; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; color: #303030; background: #fff; outline: none;">
                            <option value="add">Add Stock</option>
                            <option value="remove">Remove Stock</option>
                            <option value="set">Set Stock To</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="display: block; font-size: 12px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">Quantity <span style="color: #d72c0d;">*</span></label>
                        <input type="number" name="quantity" min="0" required class="form-input" placeholder="0"
                               style="width: 100%; padding: 0.4rem 0.5rem; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; color: #303030; outline: none; box-sizing: border-box;">
                    </div>
                    <div>
                        <label class="form-label" style="display: block; font-size: 12px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">Reason <span style="font-weight: 400; color: #616161;">(optional)</span></label>
                        <input type="text" name="reason" class="form-input" placeholder="e.g. Restock, Damaged, Correction"
                               style="width: 100%; padding: 0.4rem 0.5rem; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; color: #303030; outline: none; box-sizing: border-box;">
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div style="padding: 0.75rem 1rem; background: #f6f6f7; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; border-radius: 0 0 0.75rem 0.75rem;">
                    <button type="button" x-on:click="open = false" style="padding: 0.4rem 0.75rem; font-size: 13px; font-weight: 500; color: #303030; background: #fff; border: 1px solid #c9cccf; border-radius: 0.5rem; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 0.4rem 0.75rem; font-size: 13px; font-weight: 500; color: #fff; background: #303030; border: 1px solid #303030; border-radius: 0.5rem; cursor: pointer;">Save Adjustment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStockModal(id, name, stock) {
            window.dispatchEvent(new CustomEvent('open-stock-modal', { detail: { id, name, stock } }));
        }
    </script>
</x-layouts.admin>
