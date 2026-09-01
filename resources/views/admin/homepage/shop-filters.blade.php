<x-layouts.admin>
    <x-slot name="title">Shop It Your Way - Filter Items</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Shop It Your Way</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.homepage.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Homepage
        </a>
    </div>

    <p style="font-size: 12px; color: #616161; margin: 0 0 1rem 0;">
        Controls the three rails on the home page's "Shop It Your Way" section. Each tab (Size, Price, Shade) renders one hanger per item, ordered by position.
    </p>

    @foreach(['size' => 'Size', 'price' => 'Price', 'shade' => 'Shade'] as $type => $label)
        <div class="card" style="margin-bottom: 1.25rem;">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">{{ $label }} Tab</h2>
                <span style="font-size: 11px; color: #616161;">{{ ($items[$type] ?? collect())->count() }} item(s)</span>
            </div>

            {{-- Existing items table --}}
            <div style="padding: 0;">
                <table style="width: 100%; font-size: 13px;">
                    <thead>
                        <tr style="background: #f7f7f7; border-bottom: 1px solid #e3e3e3;">
                            <th style="text-align: left; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Label</th>
                            <th style="text-align: left; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Sub-label</th>
                            <th style="text-align: left; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Shade</th>
                            <th style="text-align: left; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Query</th>
                            <th style="text-align: center; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Active</th>
                            <th style="text-align: right; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($items[$type] ?? []) as $item)
                            <tr style="border-bottom: 1px solid #e3e3e3;">
                                <form action="{{ route('admin.homepage.shop-filters.update', $item) }}" method="POST" style="display: contents;">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="type" value="{{ $item->type }}">
                                    <td style="padding: 0.5rem 1rem;"><input type="text" name="label" value="{{ $item->label }}" required class="form-input" style="font-size: 13px;"></td>
                                    <td style="padding: 0.5rem 1rem;"><input type="text" name="sub_label" value="{{ $item->sub_label }}" class="form-input" style="font-size: 13px;"></td>
                                    <td style="padding: 0.5rem 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                        @if($item->shade_hex)
                                            <span style="display:inline-block; width: 18px; height: 18px; border-radius: 4px; background: {{ $item->shade_hex }}; border: 1px solid #c9cccf;"></span>
                                        @endif
                                        <input type="text" name="shade_hex" value="{{ $item->shade_hex }}" class="form-input" style="font-size: 13px; max-width: 110px;" placeholder="#b8895a">
                                    </td>
                                    <td style="padding: 0.5rem 1rem;"><input type="text" name="query_string" value="{{ $item->query_string }}" class="form-input" style="font-size: 13px;" placeholder="size=M"></td>
                                    <td style="padding: 0.5rem 1rem; text-align: center;">
                                        <span class="badge {{ $item->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $item->is_active ? 'Active' : 'Hidden' }}</span>
                                    </td>
                                    <td style="padding: 0.5rem 1rem; text-align: right; white-space: nowrap;">
                                        <button type="submit" class="btn btn-sm btn-primary" style="font-size: 11px;">Save</button>
                                </form>
                                <form action="{{ route('admin.homepage.shop-filters.toggle', $item) }}" method="POST" style="display: inline;">
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 11px;">{{ $item->is_active ? 'Hide' : 'Show' }}</button>
                                </form>
                                <form action="{{ route('admin.homepage.shop-filters.destroy', $item) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this item?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" style="font-size: 11px;">Delete</button>
                                </form>
                                    </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="padding: 1.5rem; text-align: center; color: #616161; font-size: 12px;">No {{ $label }} items yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Add new item form --}}
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3; background: #fafafa;">
                <form action="{{ route('admin.homepage.shop-filters.store') }}" method="POST" style="display: grid; grid-template-columns: 1fr 1fr 130px 1fr auto; gap: 0.5rem; align-items: end;">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    <div>
                        <label class="form-label" style="font-size: 11px; color: #616161;">Label *</label>
                        <input type="text" name="label" required class="form-input" style="font-size: 13px;" placeholder="@if($type==='size')M @elseif($type==='price')₹1k - 2k @else Tan @endif">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 11px; color: #616161;">Sub-label</label>
                        <input type="text" name="sub_label" class="form-input" style="font-size: 13px;" placeholder="120 Styles">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 11px; color: #616161;">Shade hex</label>
                        <input type="text" name="shade_hex" class="form-input" style="font-size: 13px;" placeholder="#b8895a">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 11px; color: #616161;">Query string</label>
                        <input type="text" name="query_string" class="form-input" style="font-size: 13px;" placeholder="size=M">
                    </div>
                    <button type="submit" class="btn btn-primary" style="font-size: 12px;">+ Add</button>
                </form>
            </div>
        </div>
    @endforeach
</x-layouts.admin>
