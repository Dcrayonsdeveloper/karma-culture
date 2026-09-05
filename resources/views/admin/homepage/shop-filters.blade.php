<x-layouts.admin>
    <x-slot name="title">Shop It Your Way - Filters</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Shop It Your Way</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <x-admin.form-errors title="The filter was not changed" />

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.homepage.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Homepage
        </a>
    </div>

    {{-- This screen used to be a list an admin typed by hand, so every size and
         every shade had to be entered a second time after it had already been
         entered on the product - and a typo here was a hanger that opened onto
         "No products found". Nothing is typed now: the rails are read off the
         catalogue. What is left is the one decision that is genuinely the
         admin's, which is whether a value should be offered at all. --}}
    <p style="font-size: 12px; color: #616161; margin: 0 0 0.5rem 0;">
        These are the filters on the home page's <strong style="color: #303030;">Shop It Your Way</strong> section and in the shop's filter sidebar.
        They are built from your products &mdash; there is nothing to add here.
    </p>
    <p style="font-size: 12px; color: #616161; margin: 0 0 1rem 0;">
        <strong style="color: #303030;">Size</strong> comes from each product's Sizes &amp; pricing rows,
        <strong style="color: #303030;">Shade</strong> and <strong style="color: #303030;">Texture</strong> from the Colours and Textures lists on the product,
        and <strong style="color: #303030;">Price</strong> from what your active products actually cost.
        Add a value to a product and it appears here; take it off the last product carrying it and it goes away on its own.
        Spellings are matched loosely, so <code>Black</code>, <code>black</code> and <code>BLACK</code> are one filter.
    </p>
    <p style="font-size: 12px; color: #616161; margin: 0 0 1.25rem 0;">
        <strong style="color: #303030;">Hide</strong> takes a value off the storefront filters and keeps it off &mdash; a new product carrying the same
        value will not bring it back, only <strong style="color: #303030;">Show</strong> will.
        Hiding never changes a product: a shade you hide here is still that product's shade, and a shopper following a link can still reach it.
    </p>

    @php
        $kkRails = [
            'size' => [
                'title' => 'Size',
                'blurb' => "From each product's Sizes &amp; pricing rows.",
                'empty' => 'No active product has any sizes yet. Add them under Sizes &amp; pricing when you edit a product.',
            ],
            'shade' => [
                'title' => 'Shade',
                'blurb' => "From the Colours list on each product.",
                'empty' => 'No active product lists any colours yet. Add them in the Colours box when you edit a product.',
            ],
            'texture' => [
                'title' => 'Texture',
                'blurb' => "From the Textures list on each product.",
                'empty' => 'No active product lists any textures yet. Add them in the Textures box when you edit a product.',
            ],
            'price' => [
                'title' => 'Price',
                'blurb' => 'Bands worked out from what your active products cost. Empty bands are left out.',
                'empty' => 'Price bands need active products at more than one price.',
            ],
        ];
    @endphp

    @foreach($kkRails as $kkType => $kkRail)
        @php
            $kkValues = $groups[$kkType] ?? collect();
            $kkShown = $kkValues->where('hidden', false)->count();
        @endphp
        <div class="card" style="margin-bottom: 1.25rem;">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <div>
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">{{ $kkRail['title'] }}</h2>
                    <p style="font-size: 11px; color: #616161; margin: 2px 0 0 0;">{!! $kkRail['blurb'] !!}</p>
                </div>
                <span style="font-size: 11px; color: #616161;">{{ $kkShown }} shown &middot; {{ $kkValues->count() - $kkShown }} hidden</span>
            </div>

            {{-- Five columns never fit a phone, so the table keeps a floor width
                 and scrolls inside its own box rather than squeezing every cell
                 into a sliver. --}}
            <div style="padding: 0; overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table style="width: 100%; min-width: 640px; font-size: 13px;">
                    <thead>
                        <tr style="background: #f7f7f7; border-bottom: 1px solid #e3e3e3;">
                            <th style="text-align: left; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Value</th>
                            <th style="text-align: center; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Products</th>
                            <th style="text-align: left; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Opens</th>
                            <th style="text-align: center; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">On the shop</th>
                            <th style="text-align: right; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kkValues as $kkValue)
                            <tr style="border-bottom: 1px solid #e3e3e3;">
                                <td style="padding: 0.5rem 1rem;">
                                    <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                        @if($kkValue->shade_hex)
                                            <span aria-hidden="true" style="flex: none; display: inline-block; width: 16px; height: 16px; border-radius: 50%; border: 1px solid #c9cccf; background: {{ $kkValue->shade_hex }};"></span>
                                        @endif
                                        <strong style="color: #303030; font-weight: 500;">{{ $kkValue->label }}</strong>
                                    </span>
                                </td>
                                {{-- The honest count, not a number somebody typed once. A
                                     hidden value no product carries any more reads "none"
                                     and is listed only so it can be shown again. --}}
                                <td style="padding: 0.5rem 1rem; text-align: center; white-space: nowrap; color: #303030;">
                                    @if($kkValue->count > 0)
                                        {{ $kkValue->count }}
                                    @else
                                        <span style="font-size: 11px; color: #616161;" title="No active product carries this value any more. It is listed because it is hidden; showing it again does nothing until a product uses it.">none</span>
                                    @endif
                                </td>
                                <td style="padding: 0.5rem 1rem;">
                                    @if($kkValue->query_string !== '')
                                        <a href="{{ route('shop') }}?{{ $kkValue->query_string }}" target="_blank" rel="noopener"
                                           style="font-size: 12px; color: #005bd3; text-decoration: none; word-break: break-all;">?{{ $kkValue->query_string }}</a>
                                    @else
                                        <span style="font-size: 11px; color: #616161;">&mdash;</span>
                                    @endif
                                </td>
                                <td style="padding: 0.5rem 1rem; text-align: center;">
                                    <span class="badge {{ $kkValue->hidden ? 'badge-neutral' : 'badge-success' }}">{{ $kkValue->hidden ? 'Hidden' : 'Shown' }}</span>
                                </td>
                                <td style="padding: 0.5rem 1rem; text-align: right; white-space: nowrap;">
                                    @if($kkValue->hidden)
                                        <form action="{{ route('admin.homepage.shop-filter-exclusions.destroy', $kkValue->exclusion_uuid) }}" method="POST" style="display: inline;">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-secondary pointer-coarse:min-h-9" style="font-size: 11px;">Show</button>
                                        </form>
                                    @else
                                        {{-- The confirm spells out what is and is not going
                                             away. On the old screen this button said Delete,
                                             which read as "delete the value" - and it never
                                             was that. --}}
                                        <form action="{{ route('admin.homepage.shop-filter-exclusions.store') }}" method="POST" style="display: inline;"
                                              onsubmit="return confirm('Hide {{ $kkValue->label }} from the shop filters?\n\nYour products keep this value - only the filter is removed.');">
                                            @csrf
                                            <input type="hidden" name="type" value="{{ $kkValue->type }}">
                                            <input type="hidden" name="value_key" value="{{ $kkValue->key }}">
                                            <input type="hidden" name="label" value="{{ $kkValue->label }}">
                                            <button type="submit" class="btn btn-sm btn-secondary pointer-coarse:min-h-9" style="font-size: 11px;">Hide</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="padding: 1.5rem; text-align: center; color: #616161; font-size: 12px;">{!! $kkRail['empty'] !!}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</x-layouts.admin>
