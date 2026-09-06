<x-layouts.admin>
    <x-slot name="title">Colours</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Colours</h1>
            <a href="{{ route('admin.colours.create') }}" class="btn btn-primary" style="font-size: 13px;">Add colour</a>
        </div>
    </x-slot>

    {{--
        Worth reading before anyone "tidies up" this list: a product does not
        point at a row here. The picker offers these values, the product saves
        its own copy of the label and swatch it was given, and the cart and the
        order copy the label again. So renaming "Sea Green" to "Seafoam" changes
        what the picker offers from now on and nothing else - every product,
        order and cart line already carrying "Sea Green" still says "Sea Green".
        Deleting works the same way: it takes the colour off the picker, it does
        not take it off a single product.
    --}}
    <p style="font-size: 12px; color: #616161; margin: 0 0 1rem 0; max-width: 62rem; line-height: 1.6;">
        These are the colours the product form offers in its picker. Products keep their own copy of the label they were saved with, so renaming or deleting an entry here never changes a product, an order, or a past cart line &mdash; it only changes what is offered next time. Spelling is matched loosely: <strong>Black</strong>, <strong>black</strong> and <strong>&ldquo;Black&nbsp;&rdquo;</strong> are all one entry, exactly the way the shop&rsquo;s shade rail groups them. <strong>Used by</strong> counts the active products carrying that value right now, so a colour you have only just added legitimately shows 0.
    </p>

    <div class="card" style="overflow: hidden;">
        @if($rows->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #e3e3e3;">
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px; width: 6rem;">Order</th>
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Colour</th>
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Used by</th>
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Status</th>
                            <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            {{--
                                The brands table makes the whole <tr> clickable with an inline
                                onclick and then cancels it again on the actions cell. That is
                                deliberately not copied here: every row carries three separate
                                forms (move up, move down, toggle) as well as delete, and a
                                row-level navigate handler swallows a click on any of them -
                                you press the up arrow and land on the edit screen instead.
                                The explicit Edit link is the only way into the record.
                            --}}
                            <tr style="border-bottom: 1px solid #e3e3e3;" onmouseover="this.style.backgroundColor='#f6f6f7'" onmouseout="this.style.backgroundColor='transparent'">
                                <td style="padding: 0.5rem 1rem;">
                                    {{--
                                        One tiny form per arrow, siblings of each other and of the
                                        delete form further along the row: forms cannot nest, and a
                                        nested one has its _method hoisted into the outer form,
                                        which is how a Save once turned into a delete elsewhere in
                                        this admin.

                                        The arrows hide at the ends of the PAGE rather than of the
                                        whole list, so a row cannot be walked across a page
                                        boundary. Reordering has always been a within-a-screenful
                                        job; if it stops being one, set position on the edit screen
                                        instead of trying to click a row across pages.
                                    --}}
                                    <div style="display: flex; align-items: center; gap: 0.25rem; min-width: 4.25rem;">
                                        @if(! $loop->first)
                                            <form action="{{ route('admin.colours.move', [$row, 'up']) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary pointer-coarse:min-h-9 pointer-coarse:min-w-9" style="font-size: 12px;" aria-label="Move {{ $row->name }} up" title="Move up">&uarr;</button>
                                            </form>
                                        @endif
                                        @if(! $loop->last)
                                            <form action="{{ route('admin.colours.move', [$row, 'down']) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary pointer-coarse:min-h-9 pointer-coarse:min-w-9" style="font-size: 12px;" aria-label="Move {{ $row->name }} down" title="Move down">&darr;</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                                <td style="padding: 0.5rem 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.625rem;">
                                        {{-- An uploaded picture beats the flat hex whenever there is
                                             one: a melange or a print has no single colour, and a
                                             solid square is a worse answer than the fabric itself. --}}
                                        @if($row->image_src)
                                            <img src="{{ $row->image_src }}" alt="{{ $row->name }}" loading="lazy"
                                                 style="width: 34px; height: 32px; border-radius: 0.375rem; object-fit: cover; border: 1px solid #e3e3e3; flex-shrink: 0; background: #fff;">
                                        @else
                                            <span aria-hidden="true"
                                                  style="width: 34px; height: 32px; border-radius: 0.375rem; border: 1px solid #e3e3e3; flex-shrink: 0; display: inline-block; background: {{ $row->swatch }};"></span>
                                        @endif
                                        <div>
                                            <a href="{{ route('admin.colours.edit', $row) }}" style="color: #005bd3; font-weight: 500; text-decoration: none;">{{ $row->name }}</a>
                                            @if($row->description)
                                                <p style="color: #616161; font-size: 12px; margin: 0; max-width: 24rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $row->description }}</p>
                                            @elseif($row->hex_code)
                                                <p style="color: #8a8a8a; font-size: 12px; margin: 0; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">{{ $row->hex_code }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                @php($used = $usage[$row->key] ?? 0)
                                <td style="padding: 0.5rem 1rem; color: {{ $used > 0 ? '#303030' : '#8a8a8a' }};">
                                    {{ $used }} {{ Str::plural('product', $used) }}
                                </td>
                                <td style="padding: 0.5rem 1rem;">
                                    <form action="{{ route('admin.colours.toggle', $row) }}" method="POST" style="display: inline;">
                                        @csrf
                                        {{-- The badge IS the control. Hiding a colour for a season is
                                             the single most common edit on this screen, and making it
                                             a round trip through the edit form only earned mis-saves. --}}
                                        <button type="submit" style="background: none; border: 0; padding: 0; cursor: pointer;" title="{{ $row->is_active ? 'Stop offering this colour in the picker' : 'Offer this colour in the picker again' }}">
                                            @if($row->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-neutral">Hidden</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                                <td style="padding: 0.5rem 1rem;">
                                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem;">
                                        <a href="{{ route('admin.colours.edit', $row) }}" class="btn-icon" title="Edit">
                                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        {{-- addslashes, not the bare name: an apostrophe in the label
                                             closes the JavaScript string early and the confirm never
                                             runs, so the row deletes without asking. --}}
                                        <form action="{{ route('admin.colours.destroy', $row) }}" method="POST" style="display: inline;"
                                              onsubmit="return confirm('Delete the colour &quot;{{ addslashes($row->name) }}&quot;? Products already saved with it keep the label - it just stops being offered in the picker.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Delete" style="color: #b71c1c;">
                                                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($rows->hasPages())
                <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                    {{ $rows->links() }}
                </div>
            @endif
        @else
            {{-- Empty state --}}
            <div style="padding: 3rem 1rem; text-align: center;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 50%; background: #f6f6f7; margin-bottom: 1rem;">
                    <svg style="width: 1.5rem; height: 1.5rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                </div>
                <p style="color: #303030; font-size: 14px; font-weight: 500; margin: 0 0 0.25rem;">No colours yet</p>
                <p style="color: #616161; font-size: 13px; margin: 0 auto 1rem; max-width: 32rem; line-height: 1.6;">
                    While the list is empty the product form has nothing to offer and falls back to a free-text box &mdash; which is how &ldquo;Navy&rdquo;, &ldquo;navy blue&rdquo; and &ldquo;Dark Blue&rdquo; end up as three separate swatches on the shop&rsquo;s shade rail.
                </p>
                <a href="{{ route('admin.colours.create') }}" class="btn btn-primary" style="font-size: 13px;">Add colour</a>
            </div>
        @endif
    </div>
</x-layouts.admin>
