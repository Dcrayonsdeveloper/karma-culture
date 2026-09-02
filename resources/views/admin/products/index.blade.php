<x-layouts.admin>
    <x-slot name="title">Products</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Products</h1>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <a href="{{ route('admin.products.export', request()->query()) }}" class="btn btn-secondary" style="font-size: 13px;">
                    Export
                </a>
                <button @click="$dispatch('open-import-modal')" class="btn btn-secondary" style="font-size: 13px;">
                    Import
                </button>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary" style="font-size: 13px;">
                    <svg style="width: 1rem; height: 1rem; margin-right: 0.375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add product
                </a>
            </div>
        </div>
    </x-slot>

    {{-- Stats row - Shopify minimal metrics --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Total products</p>
            <p style="font-size: 1.5rem; font-weight: 600; color: #303030;">{{ number_format($stats['total']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Active</p>
            <p style="font-size: 1.5rem; font-weight: 600; color: #303030;">{{ number_format($stats['active']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Draft</p>
            <p style="font-size: 1.5rem; font-weight: 600; color: #303030;">{{ number_format($stats['inactive']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Out of stock</p>
            <p style="font-size: 1.5rem; font-weight: 600; color: #d72c0d;">{{ number_format($stats['out_of_stock']) }}</p>
        </div>
    </div>

    {{-- Products list card --}}
    @php $pageIds = $products->pluck('id')->toArray(); @endphp
    <div class="card"
         x-data="{
             selected: [],
             showFilters: false,
             toggleAll(checked) {
                 this.selected = checked ? {{ json_encode($pageIds) }} : [];
             },
             toggle(id) {
                 const idx = this.selected.indexOf(id);
                 idx === -1 ? this.selected.push(id) : this.selected.splice(idx, 1);
             },
             get allChecked() {
                 return this.selected.length === {{ count($pageIds) }} && {{ count($pageIds) }} > 0;
             }
         }">

        {{-- Tab filters + Search --}}
        <div style="border-bottom: 1px solid #e3e3e3;">
            <div style="display: flex; align-items: center; gap: 0;">
                <a href="{{ route('admin.products.index') }}"
                   style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#303030' : 'transparent' }}; color: {{ !request('status') ? '#303030' : '#616161' }}; margin-bottom: -1px;">All</a>
                <a href="{{ route('admin.products.index', ['status' => 'active'] + request()->except('status', 'page')) }}"
                   style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'active' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'active' ? '#303030' : '#616161' }}; margin-bottom: -1px;">Active</a>
                <a href="{{ route('admin.products.index', ['status' => 'inactive'] + request()->except('status', 'page')) }}"
                   style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'inactive' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'inactive' ? '#303030' : '#616161' }}; margin-bottom: -1px;">Draft</a>
            </div>
        </div>

        {{-- Search + Filter bar --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.products.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999; width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search products"
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>
                <button type="button" @click="showFilters = !showFilters"
                        style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.375rem 0.75rem; font-size: 13px; font-weight: 500; color: #303030; background: white; border: 1px solid #c9cccf; border-radius: 0.5rem; cursor: pointer;">
                    <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                    @if(request()->hasAny(['category', 'seller', 'stock']))
                        <span style="background: #303030; color: white; font-size: 10px; padding: 0 0.375rem; border-radius: 50rem; line-height: 1.6;">{{ collect(['category', 'seller', 'stock'])->filter(fn($f) => request($f))->count() }}</span>
                    @endif
                </button>
                @if(request()->hasAny(['search', 'status', 'category', 'seller', 'stock']))
                    <a href="{{ route('admin.products.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none; white-space: nowrap;">Clear all</a>
                @endif
            </form>
        </div>

        {{-- Expanded filters --}}
        <div x-show="showFilters" x-cloak x-transition
             style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; background: #fafafa;">
            <form action="{{ route('admin.products.index') }}" method="GET">
                @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;">
                    <div>
                        <label style="font-size: 13px; font-weight: 500; color: #303030; display: block; margin-bottom: 0.25rem;">Category</label>
                        <select name="category" style="width: 100%; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.375rem 2rem 0.375rem 0.5rem;">
                            <option value="">All categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->path_label ?? $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 500; color: #303030; display: block; margin-bottom: 0.25rem;">Vendor</label>
                        <select name="seller" style="width: 100%; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.375rem 2rem 0.375rem 0.5rem;">
                            <option value="">All vendors</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}" {{ request('seller') == $seller->id ? 'selected' : '' }}>
                                    {{ $seller->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 500; color: #303030; display: block; margin-bottom: 0.25rem;">Stock</label>
                        <select name="stock" style="width: 100%; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.375rem 2rem 0.375rem 0.5rem;">
                            <option value="">All stock</option>
                            <option value="out" {{ request('stock') === 'out' ? 'selected' : '' }}>Out of stock</option>
                            <option value="low" {{ request('stock') === 'low' ? 'selected' : '' }}>Low stock (&le; 10)</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.75rem;">
                    <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
                    <a href="{{ route('admin.products.index', request()->only('search', 'status')) }}" class="btn btn-secondary btn-sm">Clear filters</a>
                </div>
            </form>
        </div>

        {{-- Bulk Actions Bar --}}
        <div x-show="selected.length > 0" x-cloak
             style="padding: 0.5rem 1rem; background: #f0f5ff; border-bottom: 1px solid #c4d5f0; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
            <span style="font-size: 13px; font-weight: 500; color: #303030;">
                <span x-text="selected.length"></span> selected
            </span>
            <form method="POST" action="{{ route('admin.products.bulk-action') }}"
                  x-ref="bulkForm"
                  @submit.prevent="
                      const action = $refs.bulkAction.value;
                      if (!action) { alert('Please select an action'); return; }
                      const labels = { activate: 'activate', deactivate: 'deactivate', approve: 'approve', delete: 'permanently delete' };
                      if (!confirm('Are you sure you want to ' + labels[action] + ' ' + selected.length + ' product(s)?')) return;
                      $refs.bulkIds.value = JSON.stringify(selected);
                      $el.submit();
                  ">
                @csrf
                <input type="hidden" name="ids" x-ref="bulkIds" value="">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <select name="action" x-ref="bulkAction" required aria-label="Bulk action"
                            style="font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.25rem 2rem 0.25rem 0.5rem;">
                        <option value="">Select action</option>
                        <option value="activate">Set as active</option>
                        <option value="deactivate">Set as draft</option>
                        <option value="delete">Delete products</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                </div>
            </form>
        </div>

        {{-- Desktop Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%; min-width: 700px;">
                <thead>
                    <tr>
                        <th style="padding-left: 1rem; width: 2.5rem;">
                            <input type="checkbox" style="border-radius: 0.25rem; border: 1px solid #c9cccf; width: 1rem; height: 1rem;"
                                   @change="toggleAll($event.target.checked)"
                                   :checked="allChecked">
                        </th>
                        <th style="text-align: left;">Product</th>
                        <th style="text-align: left;">Status</th>
                        <th style="text-align: left;">Inventory</th>
                        <th style="text-align: left;">Category</th>
                        <th style="text-align: left;">Vendor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="cursor: pointer;" onclick="if(!event.target.closest('input,button,a,form')) window.location='{{ route('admin.products.edit', $product) }}'">
                            <td style="padding-left: 1rem; width: 2.5rem;" onclick="event.stopPropagation()">
                                <input type="checkbox" style="border-radius: 0.25rem; border: 1px solid #c9cccf; width: 1rem; height: 1rem;"
                                       :checked="selected.includes({{ $product->id }})"
                                       @change="toggle({{ $product->id }})">
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    @if($product->primary_image_url)
                                        <img src="{{ $product->primary_image_url }}" alt=""
                                             style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; object-fit: cover; border: 1px solid #e3e3e3;">
                                    @else
                                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background: #f7f7f7; border: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                                            <svg style="width: 1rem; height: 1rem; color: #c9cccf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div style="min-width: 0;">
                                        <p style="font-size: 13px; font-weight: 500; color: #303030; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 300px;">{{ $product->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($product->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-neutral">Draft</span>
                                @endif
                            </td>
                            <td>
                                @if($product->stock_quantity <= 0)
                                    <span style="font-size: 13px; color: #d72c0d;">0 in stock</span>
                                @elseif($product->stock_quantity <= 10)
                                    <span style="font-size: 13px; color: #b98900;">{{ $product->stock_quantity }} in stock</span>
                                @else
                                    <span style="font-size: 13px; color: #005bd3;">{{ $product->stock_quantity }} in stock</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #616161;">{{ $product->category->name ?? '-' }}</span>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #616161;">{{ $product->seller->store_name ?? '-' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg style="width: 1.25rem; height: 1.25rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No products found</h3>
                                    <p style="font-size: 13px; color: #616161; margin-bottom: 1rem;">Get started by adding your first product.</p>
                                    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Add product</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Card View --}}
        <div style="border-top: none; display: none;">
            @forelse($products as $product)
                <a href="{{ route('admin.products.edit', $product) }}" style="display: flex; gap: 0.75rem; padding: 0.75rem 1rem; border-bottom: 1px solid #f1f1f1; text-decoration: none; color: inherit;">
                    @if($product->primary_image_url)
                        <img src="{{ $product->primary_image_url }}" alt=""
                             style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; object-fit: cover; border: 1px solid #e3e3e3; flex-shrink: 0;">
                    @else
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background: #f7f7f7; border: 1px solid #e3e3e3; flex-shrink: 0;"></div>
                    @endif
                    <div style="flex: 1; min-width: 0;">
                        <p style="font-size: 13px; font-weight: 500; color: #303030;">{{ $product->name }}</p>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem;">
                            @if($product->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-neutral">Draft</span>
                            @endif
                            <span style="font-size: 12px; color: #616161;">{{ $product->stock_quantity }} in stock</span>
                        </div>
                    </div>
                    <div style="flex-shrink: 0; text-align: right;">
                        <span style="font-size: 13px; font-weight: 500; color: #303030;">@price($product->price)</span>
                    </div>
                </a>
            @empty
                <div style="padding: 3rem 1rem; text-align: center;">
                    <p style="font-size: 13px; color: #616161;">No products found</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    {{-- Import Modal --}}
    <div x-data="{ open: false }"
         @open-import-modal.window="open = true"
         x-show="open" x-cloak
         style="position: fixed; inset: 0; z-index: 50; overflow-y: auto;">
        <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 0 1rem;">
            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 style="position: fixed; inset: 0; background: rgba(0,0,0,0.4);" @click="open = false"></div>

            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 style="position: relative; background: white; width: 100%; max-width: 32rem; overflow: hidden; border-radius: 0.75rem; box-shadow: 0 16px 70px rgba(0,0,0,0.15);">

                <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                    <h2 style="font-size: 15px; font-weight: 600; color: #303030;">Import products</h2>
                    <button @click="open = false" class="btn-icon">
                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" style="padding: 1.25rem;">
                    @csrf
                    <div style="margin-bottom: 1rem;">
                        <label style="font-size: 13px; font-weight: 500; color: #303030; display: block; margin-bottom: 0.25rem;">CSV File</label>
                        <input type="file" name="csv_file" accept=".csv,.txt" required
                               style="width: 100%; font-size: 13px; border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.375rem 0.5rem;">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Max 10MB. Must contain header row with column names.</p>
                    </div>

                    <div style="background: #f7f7f7; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">
                        <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 0.5rem;">Required columns</h3>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-bottom: 0.75rem;">
                            <span style="padding: 0.125rem 0.5rem; background: #e3f4e8; color: #1a7a2e; border-radius: 50rem; font-size: 12px; font-weight: 500;">name</span>
                            <span style="padding: 0.125rem 0.5rem; background: #e3f4e8; color: #1a7a2e; border-radius: 50rem; font-size: 12px; font-weight: 500;">sku</span>
                            <span style="padding: 0.125rem 0.5rem; background: #e3f4e8; color: #1a7a2e; border-radius: 50rem; font-size: 12px; font-weight: 500;">price</span>
                        </div>
                        <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 0.5rem;">Optional columns</h3>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                            @foreach(['slug', 'category', 'seller', 'sale_price', 'cost_price', 'stock_quantity', 'description', 'short_description', 'is_active', 'is_featured', 'image_url', 'meta_title', 'meta_description'] as $col)
                                <span style="padding: 0.125rem 0.5rem; background: #ebebeb; color: #616161; border-radius: 50rem; font-size: 12px;">{{ $col }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" @click="open = false" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Import products</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>
