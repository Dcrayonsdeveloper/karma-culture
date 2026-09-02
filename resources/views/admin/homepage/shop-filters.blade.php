<x-layouts.admin>
    <x-slot name="title">Shop It Your Way - Filter Items</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Shop It Your Way</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <x-admin.form-errors title="The filter item was not saved" />

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

            {{-- Existing items table. Six columns of inputs never fit a phone,
                 so the table keeps a floor width and scrolls inside its own box
                 rather than squeezing every field into a sliver. --}}
            <div style="padding: 0; overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table style="width: 100%; min-width: 860px; font-size: 13px;">
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
                                    <td style="padding: 0.5rem 1rem;"><input type="text" name="label" value="{{ $item->label }}" required maxlength="120" aria-label="Label" class="form-input" style="font-size: 13px;"></td>
                                    <td style="padding: 0.5rem 1rem;"><input type="text" name="sub_label" value="{{ $item->sub_label }}" maxlength="120" aria-label="Sub-label" class="form-input" style="font-size: 13px;"></td>
                                    <td style="padding: 0.5rem 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        @if($item->shade_hex)
                                            <span style="flex: none; display:inline-block; width: 18px; height: 18px; border-radius: 4px; background: {{ $item->shade_hex }}; border: 1px solid #c9cccf;"></span>
                                        @endif
                                        {{-- Hex only. This value is interpolated into a `style`
                                             attribute on the swatch above and again on the home
                                             page, so anything that is not a colour is arbitrary
                                             CSS in the page. --}}
                                        <input type="text" name="shade_hex" value="{{ $item->shade_hex }}" maxlength="9"
                                               pattern="#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})"
                                               title="Enter a hex colour such as #b8895a." aria-label="Shade hex"
                                               class="form-input" style="font-size: 13px; max-width: 110px;" placeholder="#b8895a">
                                        </div>
                                    </td>
                                    <td style="padding: 0.5rem 1rem;"><input type="text" name="query_string" value="{{ $item->query_string }}" maxlength="255"
                                               pattern="[A-Za-z0-9_\-=&amp;%.+,\[\]]+"
                                               title="Enter a query string such as size=M or price_min=1000&amp;price_max=2000."
                                               aria-label="Query string" class="form-input" style="font-size: 13px;" placeholder="size=M"></td>
                                    <td style="padding: 0.5rem 1rem; text-align: center;">
                                        <span class="badge {{ $item->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $item->is_active ? 'Active' : 'Hidden' }}</span>
                                    </td>
                                    <td style="padding: 0.5rem 1rem; text-align: right; white-space: nowrap; min-width: 170px;">
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
                                {{-- position was stamped once at creation and nothing could
                                     change it afterwards, so the order on the home page was
                                     fixed by the order the rows happened to be added in. --}}
                                @if(! $loop->first)
                                    <form action="{{ route('admin.homepage.shop-filters.move', $item) }}" method="POST" style="display: inline;">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 11px;" aria-label="Move up" title="Move up">&uarr;</button>
                                    </form>
                                @endif
                                @if(! $loop->last)
                                    <form action="{{ route('admin.homepage.shop-filters.move', $item) }}" method="POST" style="display: inline;">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 11px;" aria-label="Move down" title="Move down">&darr;</button>
                                    </form>
                                @endif

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
                {{-- auto-fit rather than five fixed tracks: the fields reflow to
                     however many columns the screen can hold instead of only
                     ever being all-five or, below 768px, all-stacked. --}}
                <form action="{{ route('admin.homepage.shop-filters.store') }}" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 0.5rem; align-items: end;">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    {{-- for/id pairs matter beyond accessibility here: the inline validator
                         names the field from its own <label>, so an unlabelled input reports
                         "This field is required" instead of "Label is required". --}}
                    <div>
                        <label for="filter-{{ $type }}-label" class="form-label" style="font-size: 11px; color: #616161;">Label *</label>
                        <input type="text" name="label" id="filter-{{ $type }}-label" required maxlength="120" class="form-input" style="font-size: 13px;" placeholder="@if($type==='size')M @elseif($type==='price')₹1k - 2k @else Tan @endif">
                    </div>
                    <div>
                        <label for="filter-{{ $type }}-sub-label" class="form-label" style="font-size: 11px; color: #616161;">Sub-label</label>
                        <input type="text" name="sub_label" id="filter-{{ $type }}-sub-label" maxlength="120" class="form-input" style="font-size: 13px;" placeholder="120 Styles">
                    </div>
                    <div>
                        <label for="filter-{{ $type }}-shade-hex" class="form-label" style="font-size: 11px; color: #616161;">Shade hex</label>
                        <input type="text" name="shade_hex" id="filter-{{ $type }}-shade-hex" maxlength="9"
                               pattern="#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})"
                               title="Enter a hex colour such as #b8895a."
                               class="form-input" style="font-size: 13px;" placeholder="#b8895a">
                    </div>
                    <div>
                        <label for="filter-{{ $type }}-query-string" class="form-label" style="font-size: 11px; color: #616161;">Query string</label>
                        <input type="text" name="query_string" id="filter-{{ $type }}-query-string" maxlength="255"
                               pattern="[A-Za-z0-9_\-=&amp;%.+,\[\]]+"
                               title="Enter a query string such as size=M or price_min=1000&amp;price_max=2000."
                               class="form-input" style="font-size: 13px;" placeholder="size=M">
                    </div>
                    <button type="submit" class="btn btn-primary" style="font-size: 12px;">+ Add</button>
                </form>
            </div>
        </div>
    @endforeach
</x-layouts.admin>
