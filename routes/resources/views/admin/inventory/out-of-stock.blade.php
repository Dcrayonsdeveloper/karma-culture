<x-layouts.admin>
    <x-slot name="title">Out of Stock</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Out of Stock Products</h1>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.inventory.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Inventory
        </a>
    </div>

    <div style="margin-bottom: 0.5rem;">
        <p style="font-size: 13px; color: #616161; margin: 0;">Products with zero or negative stock</p>
    </div>

    @if($products->total() > 0)
        <div class="card" style="padding: 0.75rem 1rem; margin-bottom: 1rem; border-left: 4px solid #d72c0d;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d72c0d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                    <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p style="font-size: 13px; font-weight: 500; color: #b71c00; margin: 0;">
                    <span style="font-weight: 700;">{{ $products->total() }}</span> {{ Str::plural('product', $products->total()) }} out of stock. These are unavailable to customers.
                </p>
            </div>
        </div>
    @endif

    <div class="card">
        @if($products->total() > 0)
            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <p style="font-size: 13px; color: #616161; margin: 0;">
                    Showing <span style="font-weight: 500; color: #303030;">{{ $products->firstItem() }}</span>-<span style="font-weight: 500; color: #303030;">{{ $products->lastItem() }}</span> of <span style="font-weight: 500; color: #303030;">{{ $products->total() }}</span> products
                </p>
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Product</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">SKU</th>
                        <th style="padding: 0.5rem 1rem; text-align: center; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Stock</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem; font-weight: 500; color: #303030;">{{ $product->name }}</td>
                            <td style="padding: 0.625rem 1rem;">
                                <span style="font-size: 12px; font-family: monospace; background: #f6f6f7; color: #616161; padding: 0.125rem 0.5rem; border-radius: 0.25rem;">{{ $product->sku ?? '—' }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: center;">
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">{{ $product->stock_quantity }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <button onclick="openStockModal({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->stock_quantity }})"
                                        class="btn btn-primary" style="font-size: 12px; padding: 0.25rem 0.625rem; display: inline-flex; align-items: center; gap: 0.375rem;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    Restock
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                    <div style="width: 48px; height: 48px; background: #cdfee1; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1a7a2e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">All products are in stock!</p>
                                    <p style="font-size: 12px; color: #616161; margin: 0;">No products have zero stock right now.</p>
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

    <!-- Stock Modal -->
    <div x-data="{ open: false, productId: null, productName: '', currentStock: 0 }"
         x-on:open-stock-modal.window="open = true; productId = $event.detail.id; productName = $event.detail.name; currentStock = $event.detail.stock"
         x-show="open" x-cloak
         style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center;">
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5);" x-on:click="open = false"></div>
        <div style="position: relative; background: white; border-radius: 0.75rem; box-shadow: 0 20px 60px rgba(0,0,0,0.15); width: 100%; max-width: 28rem; margin: 0 1rem;" x-transition>
            <div style="padding: 1rem 1.5rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h3 style="font-size: 14px; font-weight: 600; color: #303030; margin: 0;">Restock Product</h3>
                    <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;" x-text="productName"></p>
                </div>
                <button type="button" x-on:click="open = false" style="background: none; border: none; cursor: pointer; color: #616161; padding: 0.25rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div style="padding: 0.625rem 1.5rem; background: #ffe0db; border-bottom: 1px solid #e3e3e3;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 12px; color: #b71c00;">Current Stock</span>
                    <span style="font-size: 13px; font-weight: 700; color: #d72c0d;" x-text="currentStock"></span>
                </div>
            </div>
            <form method="POST" x-bind:action="'/admin/inventory/' + productId + '/stock'">
                @csrf
                @method('PUT')
                <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label">Adjustment Type <span style="color: #d72c0d;">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="add">Add Stock</option>
                            <option value="set">Set Stock To</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Quantity <span style="color: #d72c0d;">*</span></label>
                        <input type="number" name="quantity" min="1" required class="form-input" placeholder="0">
                    </div>
                    <div>
                        <label class="form-label">Reason <span style="font-weight: 400; color: #616161;">(optional)</span></label>
                        <input type="text" name="reason" class="form-input" placeholder="e.g. Restock, Purchase order">
                    </div>
                </div>
                <div style="padding: 0.75rem 1.5rem; background: #f6f6f7; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; border-radius: 0 0 0.75rem 0.75rem;">
                    <button type="button" x-on:click="open = false" class="btn btn-secondary" style="font-size: 13px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Restock Now</button>
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
