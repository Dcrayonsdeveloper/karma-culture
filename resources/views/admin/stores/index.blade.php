<x-layouts.admin>
    <x-slot name="title">Stores</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Stores</h1>
            <a href="{{ route('admin.stores.create') }}" class="btn btn-primary" style="font-size: 13px;">
                <svg style="width: 16px; height: 16px; margin-right: 6px; display: inline-block; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add store
            </a>
        </div>
    </x-slot>

    {{-- Card with search + table --}}
    <div style="background: white; border-radius: 0.75rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">

        {{-- Search --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <svg style="width: 16px; height: 16px; color: #616161; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" placeholder="Search stores..."
                   style="flex: 1; border: none; outline: none; font-size: 13px; color: #303030; background: transparent;"
                   x-data x-on:input.debounce.300ms="
                       let val = $event.target.value.toLowerCase();
                       document.querySelectorAll('tbody tr[data-searchable]').forEach(row => {
                           row.style.display = row.dataset.searchable.toLowerCase().includes(val) ? '' : 'none';
                       });
                   ">
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Store</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Code</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Contact</th>
                        <th style="padding: 0.5rem 1rem; text-align: center; font-weight: 500; color: #616161; font-size: 12px;">Registers</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stores as $store)
                        <tr style="border-bottom: 1px solid #e3e3e3; cursor: pointer;"
                            data-searchable="{{ $store->name }} {{ $store->code }} {{ $store->address }} {{ $store->phone }} {{ $store->email }}"
                            onmouseover="this.style.background='#f6f6f7'"
                            onmouseout="this.style.background='transparent'"
                            onclick="window.location='{{ route('admin.stores.edit', $store) }}'">
                            <td style="padding: 0.625rem 1rem;">
                                <div style="font-weight: 500; color: #303030;">{{ $store->name }}</div>
                                @if($store->address)
                                    <div style="font-size: 12px; color: #616161; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $store->address }}</div>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                <span style="font-family: monospace; font-size: 13px; color: #616161;">{{ $store->code }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161; font-size: 13px;">
                                @if($store->phone)
                                    <div>{{ $store->phone }}</div>
                                @endif
                                @if($store->email)
                                    <div style="font-size: 12px;">{{ $store->email }}</div>
                                @endif
                                @if(!$store->phone && !$store->email)
                                    <span>--</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: center; font-weight: 500; color: #303030;">
                                {{ $store->registers_count }}
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($store->is_active)
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
                                @else
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #fff3cd; color: #8a6d00;">Inactive</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;" onclick="event.stopPropagation()">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                                    <a href="{{ route('admin.stores.edit', $store) }}" style="color: #005bd3; font-size: 12px; font-weight: 500; text-decoration: none;"
                                       onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Edit</a>
                                    <form action="{{ route('admin.stores.destroy', $store) }}" method="POST" onsubmit="return confirm('Delete this store?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color: #d72c0d; font-size: 12px; font-weight: 500; background: none; border: none; cursor: pointer; padding: 0;"
                                                onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <svg style="width: 3rem; height: 3rem; color: #c9cccf; margin-bottom: 0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <p style="font-weight: 500; color: #303030; margin-bottom: 0.25rem;">No stores found</p>
                                    <p style="font-size: 13px; color: #616161;">
                                        <a href="{{ route('admin.stores.create') }}" style="color: #005bd3; text-decoration: none;"
                                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Create one now</a>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stores->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $stores->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
