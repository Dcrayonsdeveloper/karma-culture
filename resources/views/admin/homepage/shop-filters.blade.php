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

    {{-- Said once here rather than repeated over three tabs. The rule is new:
         hangers used to be hung whatever their query returned, and most of the
         live ones opened onto "No products found". --}}
    <p style="font-size: 12px; color: #616161; margin: 0 0 1rem 0;">
        <strong style="color: #303030;">Matches</strong> is what the shop returns for that hanger's query right now.
        A hanger matching <strong style="color: #303030;">0</strong> is left off the home page rather than sent to an empty results page &mdash;
        it comes back on its own once something matches again. <strong style="color: #303030;">Ignored</strong> means the query names nothing the shop
        filters on (<code>price=2</code> is not a bound; <code>price_min=2000&amp;price_max=3000</code> is), so that hanger opens the whole catalogue.
        The Size and Shade query boxes suggest the values this catalogue carries.
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
                <table style="width: 100%; min-width: 920px; font-size: 13px;">
                    <thead>
                        <tr style="background: #f7f7f7; border-bottom: 1px solid #e3e3e3;">
                            <th style="text-align: left; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Label</th>
                            <th style="text-align: left; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Sub-label</th>
                            <th style="text-align: left; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Shade</th>
                            <th style="text-align: left; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Query</th>
                            <th style="text-align: center; padding: 0.5rem 1rem; font-weight: 500; color: #616161; font-size: 12px;">Matches</th>
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
                                        {{-- The scope has to start inside the cell: the row's <form> is
                                             display:contents and the parser lifts it out of the <tr>, so
                                             anything hung on the row or the form would not hold together. --}}
                                        {{-- flex-wrap, not for the controls but for the validator: an
                                             invalid hex drops its message into this same row, and the
                                             message only steps under the field if it may take a line. --}}
                                        <div x-data="kkShadeField(@js($item->shade_hex ?? ''))" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                                            {{-- The picker cannot draw "no shade" - a blank value reads
                                                 as black - so the flat swatch stays on as the honest
                                                 preview and hatches while the field is empty. The colour
                                                 is server-rendered too: the column used to be readable
                                                 with no JS at all and should stay that way. --}}
                                            <span aria-hidden="true" :style="{ background: swatch }" style="flex: none; display:inline-block; width: 18px; height: 18px; border-radius: 4px; border: 1px solid #c9cccf; background: {{ $item->shade_hex ?: 'repeating-linear-gradient(45deg, #fff, #fff 4px, #e3e3e3 4px, #e3e3e3 8px)' }};"></span>
                                            <input type="color" :value="picker" @click="open()" @input="pick($event.target.value)" aria-label="Shade colour picker"
                                                   style="width: 32px; height: 34px; border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.125rem; background: none; cursor: pointer; flex: 0 0 auto;">
                                        {{-- Hex only. This value is interpolated into a `style`
                                             attribute on the swatch above and again on the home
                                             page, so anything that is not a colour is arbitrary
                                             CSS in the page. --}}
                                        <input type="text" name="shade_hex" value="{{ $item->shade_hex }}" maxlength="9"
                                               pattern="#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})"
                                               title="Enter a hex colour such as #b8895a." aria-label="Shade hex"
                                               x-ref="text" @input="sync($event.target.value)"
                                               class="form-input" style="font-size: 13px; max-width: 96px; min-width: 0;" placeholder="#b8895a">
                                            {{-- Without this the palette is a one-way door: every colour
                                                 is reachable but "no shade" is not, and it is the empty
                                                 shade that lets the home page fall back to its own tan. --}}
                                            <button type="button" x-show="hex !== ''" x-cloak @click="clear()" title="Clear shade" aria-label="Clear shade"
                                                    style="flex: none; color: #d72c0d; background: none; border: 0; cursor: pointer; font-size: 16px; line-height: 1; padding: 0 2px;">&times;</button>
                                        </div>
                                    </td>
                                    <td style="padding: 0.5rem 1rem;"><input type="text" name="query_string" value="{{ $item->query_string }}" maxlength="255"
                                               pattern="[A-Za-z0-9_\-=&amp;%.+,\[\]]+"
                                               @if(! empty($suggestions[$type])) list="kk-query-{{ $type }}" @endif
                                               title="Enter a query string such as size=M or price_min=1000&amp;price_max=2000."
                                               aria-label="Query string" class="form-input" style="font-size: 13px;" placeholder="size=M"></td>
                                    {{-- What the shop returns for this query right now. A hanger
                                         that returns nothing is left off the home page rather than
                                         promoted as a dead end onto "No products found", so the
                                         screen that owns it has to be the one that says why. --}}
                                    @php
                                        $kkMatches = $matches[$item->id] ?? null;
                                        $kkUnread = $unread[$item->id] ?? [];
                                    @endphp
                                    <td style="padding: 0.5rem 1rem; text-align: center; white-space: nowrap;">
                                        @if($kkMatches === null)
                                            <span style="font-size: 11px; color: #616161;" title="No query string, so this hanger links nowhere.">&mdash;</span>
                                        @elseif($kkMatches === 0)
                                            <span class="badge badge-error" title="Nothing matches this query, so the hanger is hidden on the home page. Pick a value the shop carries.">0 &middot; hidden</span>
                                        @elseif($kkUnread)
                                            {{-- A healthy-looking count on a filter that never runs: the
                                                 hanger opens the whole shop instead of the edit it names. --}}
                                            <span class="badge badge-warning" title="The shop does not read {{ implode(', ', $kkUnread) }}, so this hanger opens the full catalogue unfiltered.">ignored</span>
                                        @else
                                            <span style="font-size: 12px; color: #303030;">{{ $kkMatches }}</span>
                                        @endif
                                    </td>
                                    <td style="padding: 0.5rem 1rem; text-align: center;">
                                        <span class="badge {{ $item->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $item->is_active ? 'Active' : 'Hidden' }}</span>
                                    </td>
                                    <td style="padding: 0.5rem 1rem; text-align: right; white-space: nowrap; min-width: 170px;">
                                        <button type="submit" class="btn btn-sm btn-primary pointer-coarse:min-h-9" style="font-size: 11px;">Save</button>
                                </form>
                                <form action="{{ route('admin.homepage.shop-filters.toggle', $item) }}" method="POST" style="display: inline;">
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-secondary pointer-coarse:min-h-9" style="font-size: 11px;">{{ $item->is_active ? 'Hide' : 'Show' }}</button>
                                </form>
                                <form action="{{ route('admin.homepage.shop-filters.destroy', $item) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this item?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger pointer-coarse:min-h-9" style="font-size: 11px;">Delete</button>
                                </form>
                                {{-- position was stamped once at creation and nothing could
                                     change it afterwards, so the order on the home page was
                                     fixed by the order the rows happened to be added in. --}}
                                @if(! $loop->first)
                                    <form action="{{ route('admin.homepage.shop-filters.move', $item) }}" method="POST" style="display: inline;">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" class="btn btn-sm btn-secondary pointer-coarse:min-h-9 pointer-coarse:min-w-9" style="font-size: 11px;" aria-label="Move up" title="Move up">&uarr;</button>
                                    </form>
                                @endif
                                @if(! $loop->last)
                                    <form action="{{ route('admin.homepage.shop-filters.move', $item) }}" method="POST" style="display: inline;">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" class="btn btn-sm btn-secondary pointer-coarse:min-h-9 pointer-coarse:min-w-9" style="font-size: 11px;" aria-label="Move down" title="Move down">&darr;</button>
                                    </form>
                                @endif

                                    </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="padding: 1.5rem; text-align: center; color: #616161; font-size: 12px;">No {{ $label }} items yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- The values the catalogue actually carries, offered on both query
                 fields in this tab. A live hanger read `size=cd` because nothing
                 here ever showed which sizes the shop stocks, and the shopper who
                 clicked it landed on an empty page. Still a plain text box: a
                 datalist suggests, it does not restrict, so a hanger can still be
                 set up ahead of the stock it is waiting for. --}}
            @if(! empty($suggestions[$type]))
                <datalist id="kk-query-{{ $type }}">
                    @foreach($suggestions[$type] as $kkOption)
                        <option value="{{ $kkOption }}"></option>
                    @endforeach
                </datalist>
            @endif

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
                        {{-- flex-wrap so an invalid hex puts the validator's message on a
                             line under the field, instead of a third column beside it. --}}
                        <div x-data="kkShadeField('')" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                            {{-- The same unset signal the rows above carry: a new item has no
                                 shade, and a black chip on its own would read as one. --}}
                            <span aria-hidden="true" :style="{ background: swatch }" style="flex: none; display:inline-block; width: 18px; height: 18px; border-radius: 4px; border: 1px solid #c9cccf; background: repeating-linear-gradient(45deg, #fff, #fff 4px, #e3e3e3 4px, #e3e3e3 8px);"></span>
                            {{-- No name on the picker: the text box is what posts. Most items
                                 have no shade, and a named colour input - which always has a
                                 value - would post every one of them as #000000. --}}
                            <input type="color" :value="picker" @click="open()" @input="pick($event.target.value)" aria-label="Shade colour picker"
                                   style="width: 32px; height: 34px; border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.125rem; background: none; cursor: pointer; flex: 0 0 auto;">
                            {{-- min-width lets the field shrink inside the grid track; without it
                                 the input's default 20-character floor pushes past the column. --}}
                            <input type="text" name="shade_hex" id="filter-{{ $type }}-shade-hex" maxlength="9"
                                   pattern="#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})"
                                   title="Enter a hex colour such as #b8895a."
                                   x-ref="text" @input="sync($event.target.value)"
                                   class="form-input" style="font-size: 13px; min-width: 0;" placeholder="#b8895a">
                            <button type="button" x-show="hex !== ''" x-cloak @click="clear()" title="Clear shade" aria-label="Clear shade"
                                    style="flex: none; color: #d72c0d; background: none; border: 0; cursor: pointer; font-size: 16px; line-height: 1; padding: 0 2px;">&times;</button>
                        </div>
                    </div>
                    <div>
                        <label for="filter-{{ $type }}-query-string" class="form-label" style="font-size: 11px; color: #616161;">Query string</label>
                        <input type="text" name="query_string" id="filter-{{ $type }}-query-string" maxlength="255"
                               pattern="[A-Za-z0-9_\-=&amp;%.+,\[\]]+"
                               @if(! empty($suggestions[$type])) list="kk-query-{{ $type }}" @endif
                               title="Enter a query string such as size=M or price_min=1000&amp;price_max=2000."
                               class="form-input" style="font-size: 13px;" placeholder="size=M">
                    </div>
                    <button type="submit" class="btn btn-primary" style="font-size: 12px;">+ Add</button>
                </form>
            </div>
        </div>
    @endforeach

    {{-- One scope per Shade cell, shared by every tab and row. The text input is
         the only thing that posts; the colour input is a swatch and a palette
         button, so it carries no name. --}}
    <script>
        function kkShadeHex(value) {
            const hex = String(value ?? '').trim();

            return /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/.test(hex) ? hex : '';
        }

        // <input type="color"> takes #rrggbb and nothing else: handed the #fff and
        // #b8895aff forms the server also accepts, it silently falls back to black
        // and shows the wrong colour. Widen and trim for the picker only - the
        // stored value goes back to the server exactly as it was typed.
        function kkShadeRgb(value) {
            const digits = kkShadeHex(value).slice(1);

            if (! digits) return '';

            return '#' + (digits.length === 3 ? digits.replace(/./g, (c) => c + c) : digits.slice(0, 6));
        }

        const KK_SHADE_UNSET = 'repeating-linear-gradient(45deg, #fff, #fff 4px, #e3e3e3 4px, #e3e3e3 8px)';
        const KK_SHADE_BLANK = '#000000';

        function kkShadeField(initial) {
            return {
                hex: String(initial ?? ''),
                picker: kkShadeRgb(initial) || KK_SHADE_BLANK,
                // A shade nobody has set must look unset, not black.
                get swatch() {
                    return kkShadeHex(this.hex) || KK_SHADE_UNSET;
                },
                // Assigning .value fires nothing, so the inline validator would
                // sit on a stale "Enter a hex colour" note over a field the
                // palette had just made valid. Hand it a real event instead.
                write(value) {
                    this.hex = value;
                    this.$refs.text.value = value;
                    this.$refs.text.dispatchEvent(new Event('input', { bubbles: true }));
                },
                sync(value) {
                    this.hex = value;
                    // Half-typed hex should not flick the picker through black,
                    // but a field cleared back to empty has to take it along -
                    // otherwise the swatch reads unset next to a picker still
                    // showing the colour that was deleted.
                    if (value.trim() === '') {
                        this.picker = KK_SHADE_BLANK;

                        return;
                    }

                    const rgb = kkShadeRgb(value);
                    if (rgb) this.picker = rgb;
                },
                // Opening the palette is itself the pick. A colour input fires
                // no event when the colour chosen is the one already shown, so
                // on an empty row - where the picker reads #000000 - black
                // would otherwise be the single shade the palette cannot set.
                open() {
                    if (kkShadeHex(this.hex) === '') this.write(this.picker);
                },
                pick(value) {
                    // The picker only speaks #rrggbb. Carry the alpha of an
                    // 8-digit shade across rather than dropping it the first
                    // time someone opens the palette on that row.
                    const current = kkShadeHex(this.hex);
                    const alpha = current.length === 9 ? current.slice(7) : '';

                    this.picker = value;
                    this.write(value + alpha);
                },
                // The way back to "no shade" - which is what lets the home page
                // fall back to its own tan. Nothing else can empty the field
                // once a colour is in it.
                clear() {
                    this.picker = KK_SHADE_BLANK;
                    this.write('');
                },
            };
        }
    </script>
</x-layouts.admin>
