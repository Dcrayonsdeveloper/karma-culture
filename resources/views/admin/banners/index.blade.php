<x-layouts.admin>
    <x-slot name="title">Banners</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Banners</h1>
            @unless($trashed)
                <a href="{{ route('admin.banners.create') }}" class="btn btn-primary" style="font-size: 13px;">
                    <svg style="width: 16px; height: 16px; margin-right: 6px; display: inline-block; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add banner
                </a>
            @endunless
        </div>
    </x-slot>

    <x-admin.form-errors title="That could not be done" />

    @php
        // Rows are grouped by placement because `priority` only means anything
        // against the other banners in the same spot - a footer banner numbered
        // 1 is not "above" a hero banner numbered 2, and a single flat list
        // invited exactly that reading.
        $kkPositionLabels = [
            'hero' => 'Hero - top of the home page',
            'sidebar' => 'Sidebar',
            'footer' => 'Footer',
            'category' => 'Category pages',
            'popup' => 'Popup',
        ];

        $kkStateStyles = [
            'live' => 'background: #cdfee1; color: #1a7a2e;',
            'scheduled' => 'background: #e0f0ff; color: #005bd3;',
            'expired' => 'background: #fff3cd; color: #8a6d00;',
            'hidden' => 'background: #ebebeb; color: #616161;',
        ];

        $kkGroups = collect($banners->items())->groupBy('position');
        $kkColumns = 8;
    @endphp

    <div class="card" style="background: white; border-radius: 0.75rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">

        {{-- Search + filters --}}
        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1 1 14rem; min-width: 0;">
                <svg style="width: 16px; height: 16px; color: #616161; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" placeholder="Search banners..."
                       style="flex: 1; border: none; outline: none; font-size: 13px; color: #303030; background: transparent; min-width: 0;"
                       x-data x-on:input.debounce.300ms="
                           let val = $event.target.value.toLowerCase();
                           document.querySelectorAll('tbody tr[data-searchable]').forEach(row => {
                               row.style.display = row.dataset.searchable.toLowerCase().includes(val) ? '' : 'none';
                           });
                       ">
            </div>

            {{-- A GET form, so the filters end up in the URL and the list can be
                 bookmarked and shared. Never nested inside another form. --}}
            <form method="GET" action="{{ route('admin.banners.index') }}" style="display: flex; align-items: center; gap: 0.5rem;">
                @if($trashed)
                    <input type="hidden" name="trashed" value="1">
                @endif
                <label for="banner-position-filter" style="font-size: 12px; color: #616161;">Placement</label>
                <select name="position" id="banner-position-filter" class="form-select" style="font-size: 13px; padding: 0.25rem 1.75rem 0.25rem 0.5rem;"
                        onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($kkPositionLabels as $kkValue => $kkLabel)
                        <option value="{{ $kkValue }}" @selected(request('position') === $kkValue)>{{ \Illuminate\Support\Str::before($kkLabel, ' -') }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn btn-secondary" style="font-size: 12px;">Filter</button></noscript>
            </form>

            <a href="{{ route('admin.banners.index', array_filter(['position' => request('position'), 'trashed' => $trashed ? null : 1])) }}"
               style="font-size: 12px; font-weight: 500; color: {{ $trashed ? '#005bd3' : '#616161' }}; text-decoration: none;"
               onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                {{ $trashed ? 'Back to active banners' : 'View deleted' }}
            </a>
        </div>

        @if($trashed)
            <div style="padding: 0.6rem 1rem; background: #fff8f6; border-bottom: 1px solid #f0c2bd; font-size: 12px; color: #8e1f0b;">
                Deleted banners. The copy, schedule and placement are still here; the uploaded files are not, so restore one and upload its artwork again before switching it on.
            </div>
        @endif

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px; white-space: nowrap;">Order</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Banner</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Mobile</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Video</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">State</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Schedule</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Created</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                    </tr>
                </thead>

                @forelse($kkGroups as $kkPosition => $kkRows)
                    @php
                        // Dragging rewrites the whole placement, and the server
                        // refuses a partial list - so the handles only appear
                        // when this page is showing all of a placement. Half a
                        // placement dragged would renumber its rows against the
                        // ones pagination is hiding.
                        $kkWhole = ($positionTotals[$kkPosition] ?? 0) === $kkRows->count();
                        $kkSortable = ! $trashed && $kkWhole && $kkRows->count() > 1;
                    @endphp
                    <tbody x-data="bannerSorter('{{ $kkPosition }}')" x-ref="list"
                           x-on:dragover.prevent x-on:drop.prevent="onDrop()">
                        <tr style="background: #f6f6f7; border-bottom: 1px solid #e3e3e3;">
                            <td colspan="{{ $kkColumns }}" style="padding: 0.45rem 1rem;">
                                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem;">
                                    <span style="font-size: 12px; font-weight: 700; color: #303030; text-transform: uppercase; letter-spacing: 0.04em;">
                                        {{ $kkPositionLabels[$kkPosition] ?? $kkPosition }}
                                    </span>
                                    <span style="font-size: 12px; color: #616161;">{{ $kkRows->count() }} {{ \Illuminate\Support\Str::plural('banner', $kkRows->count()) }}</span>
                                    @if($kkSortable)
                                        <span style="font-size: 12px; color: #8a8a8a;">Drag a row, or use the arrows, to change the order.</span>
                                        <span x-show="saving" x-cloak style="font-size: 12px; color: #005bd3; font-weight: 500;">Saving order...</span>
                                        <span x-show="saved" x-cloak x-transition style="font-size: 12px; color: #1a7a2e; font-weight: 500;">Order saved.</span>
                                        {{-- A rejected reorder used to leave the rows in their new
                                             places on screen with no message, so the page disagreed
                                             with the database until the next refresh put it back. --}}
                                        <span x-show="failed" x-cloak x-transition style="font-size: 12px; color: #8e1f0b; font-weight: 500;" x-text="failedMessage"></span>
                                    @elseif(! $trashed && ! $kkWhole)
                                        <span style="font-size: 12px; color: #8a8a8a;">Reordering needs the whole placement on one page &mdash; filter to it above.</span>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @foreach($kkRows as $banner)
                            <tr style="border-bottom: 1px solid #e3e3e3;"
                                data-id="{{ $banner->id }}"
                                data-searchable="{{ $banner->name }} {{ $banner->title }} {{ $banner->position }} {{ $banner->link }}"
                                @if($kkSortable)
                                    draggable="true"
                                    x-on:dragstart="onDragStart($event)"
                                    x-on:dragover.prevent="onDragOver($event)"
                                    x-on:dragend="onDragEnd()"
                                    :style="{
                                        opacity: draggingId == {{ $banner->id }} ? '0.5' : '',
                                        borderTop: dropTargetId == {{ $banner->id }} && draggingId != {{ $banner->id }} ? '2px solid #005bd3' : ''
                                    }"
                                @endif>

                                {{-- Order --}}
                                <td style="padding: 0.625rem 1rem; white-space: nowrap;">
                                    <div style="display: flex; align-items: center; gap: 0.35rem;">
                                        <span class="kk-order-badge" title="Display order within {{ $kkPosition }} (priority {{ $banner->priority }})"
                                              style="font-size: 12px; font-weight: 700; color: #616161; min-width: 1.75rem; height: 1.5rem; display: inline-flex; align-items: center; justify-content: center; background: #f1f1f1; border-radius: 0.25rem;">#{{ $loop->iteration }}</span>
                                        @if($kkSortable)
                                            <span style="cursor: grab; color: #8a8a8a;" title="Drag to reorder">
                                                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                            </span>
                                            <span style="display: inline-flex; flex-direction: column;">
                                                <button type="button" @click="moveUp($el)" class="kk-nudge" style="color: #616161; background: none; border: none; cursor: pointer; padding: 0;" title="Move up" aria-label="Move up">
                                                    <svg style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                                </button>
                                                <button type="button" @click="moveDown($el)" class="kk-nudge" style="color: #616161; background: none; border: none; cursor: pointer; padding: 0;" title="Move down" aria-label="Move down">
                                                    <svg style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Desktop artwork + name. The accessors, not
                                     asset_v('storage/'.$column): a banner whose
                                     media is an absolute URL or a web-root path
                                     resolved to /storage/https:/... and 404'd. --}}
                                <td style="padding: 0.625rem 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div class="kk-media{{ $banner->image_url || $banner->video_url ? '' : ' is-broken' }}"
                                             style="width: 80px; height: 48px; border-radius: 0.375rem; border: 1px solid #e3e3e3; flex-shrink: 0;">
                                            @if($banner->image_url)
                                                <img src="{{ $banner->image }}" alt="{{ $banner->alt }}" onerror="this.closest('.kk-media').classList.add('is-broken')">
                                            @elseif($banner->video_url)
                                                <video src="{{ $banner->video }}" muted playsinline preload="metadata" onerror="this.closest('.kk-media').classList.add('is-broken')"></video>
                                            @endif
                                            <span class="kk-media__fallback" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="M21 15l-5-5L5 20"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <div style="min-width: 0;">
                                            <div style="font-weight: 500; color: #303030;">{{ $banner->name ?: $banner->title ?: 'Banner #'.$banner->id }}</div>
                                            @if($banner->title)
                                                <div style="font-size: 12px; color: #616161; max-width: 16rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $banner->title }}</div>
                                            @endif
                                            @if($banner->link)
                                                <div style="font-size: 12px; color: #616161; max-width: 16rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $banner->link }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Mobile artwork. Which breakpoint a banner has
                                     its own file for is otherwise invisible until
                                     someone opens the edit form. --}}
                                <td style="padding: 0.625rem 1rem;">
                                    @if($banner->mobile_image_url)
                                        <img src="{{ $banner->mobile_image }}" alt="{{ $banner->name }} on phones"
                                             style="width: 36px; height: 48px; object-fit: cover; border-radius: 0.375rem; border: 1px solid #e3e3e3;">
                                    @elseif($banner->mobile_video_url)
                                        <span style="font-size: 12px; color: #303030;">Own clip</span>
                                    @else
                                        <span style="font-size: 12px; color: #8a8a8a;">Uses desktop</span>
                                    @endif
                                </td>

                                {{-- Video --}}
                                <td style="padding: 0.625rem 1rem; white-space: nowrap;">
                                    @if($banner->has_video || $banner->has_mobile_video)
                                        <span style="display: inline-flex; align-items: center; gap: 0.2rem; padding: 0.1rem 0.45rem; border-radius: 0.75rem; font-size: 11px; font-weight: 600; background: #303030; color: #fff;">
                                            <svg style="width: 0.7rem; height: 0.7rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                            {{ $banner->has_video && $banner->has_mobile_video ? 'Both' : ($banner->has_video ? 'Desktop' : 'Mobile') }}
                                        </span>
                                    @else
                                        <span style="font-size: 12px; color: #8a8a8a;">None</span>
                                    @endif
                                </td>

                                {{-- State: what a shopper sees, not the raw switch.
                                     "Active" beside a banner nobody can see because
                                     its window closed was true and useless. --}}
                                <td style="padding: 0.625rem 1rem;">
                                    @if($trashed)
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #fbe9e7; color: #8e1f0b;">Deleted</span>
                                    @else
                                        <span title="{{ $banner->state_label }}"
                                              style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; {{ $kkStateStyles[$banner->state] ?? $kkStateStyles['hidden'] }}">
                                            {{ ucfirst($banner->state) }}
                                        </span>
                                        @if($banner->state !== 'live')
                                            <div style="font-size: 12px; color: #616161; margin-top: 0.15rem;">{{ $banner->state_label }}</div>
                                        @endif
                                    @endif
                                </td>

                                {{-- Schedule --}}
                                <td style="padding: 0.625rem 1rem; font-size: 12px; color: #616161; white-space: nowrap;">
                                    @if($banner->starts_at || $banner->ends_at)
                                        <div>From {{ $banner->starts_at?->format('j M Y, H:i') ?? 'now' }}</div>
                                        <div>Until {{ $banner->ends_at?->format('j M Y, H:i') ?? 'further notice' }}</div>
                                    @else
                                        <span style="color: #8a8a8a;">Always</span>
                                    @endif
                                </td>

                                {{-- Created --}}
                                <td style="padding: 0.625rem 1rem; font-size: 12px; color: #616161; white-space: nowrap;">
                                    {{ $banner->created_at?->format('j M Y') ?? '--' }}
                                </td>

                                {{-- Actions --}}
                                <td style="padding: 0.625rem 1rem; text-align: right;">
                                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.6rem;">
                                        @if($trashed)
                                            <form action="{{ route('admin.banners.restore', $banner) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" style="display: inline-flex; align-items: center; min-height: 2.25rem; padding: 0 0.25rem; color: #005bd3; font-size: 12px; font-weight: 500; background: none; border: none; cursor: pointer;"
                                                        onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Restore</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.banners.edit', $banner) }}" style="display: inline-flex; align-items: center; min-height: 2.25rem; padding: 0 0.25rem; color: #005bd3; font-size: 12px; font-weight: 500; text-decoration: none;"
                                               onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Edit</a>
                                            <a href="{{ route('admin.banners.preview', $banner) }}" style="display: inline-flex; align-items: center; min-height: 2.25rem; padding: 0 0.25rem; color: #005bd3; font-size: 12px; font-weight: 500; text-decoration: none;"
                                               onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Preview</a>
                                            <form action="{{ route('admin.banners.toggle', $banner) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" style="display: inline-flex; align-items: center; min-height: 2.25rem; padding: 0 0.25rem; color: #303030; font-size: 12px; font-weight: 500; background: none; border: none; cursor: pointer;"
                                                        onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">{{ $banner->is_active ? 'Deactivate' : 'Activate' }}</button>
                                            </form>
                                            <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" onsubmit="return confirm('Delete this banner? Its uploaded files are removed; the banner itself can be restored from View deleted.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" style="display: inline-flex; align-items: center; min-height: 2.25rem; padding: 0 0.25rem; color: #d72c0d; font-size: 12px; font-weight: 500; background: none; border: none; cursor: pointer;"
                                                        onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="{{ $kkColumns }}" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <svg style="width: 3rem; height: 3rem; color: #c9cccf; margin-bottom: 0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p style="font-weight: 500; color: #303030; margin-bottom: 0.25rem;">{{ $trashed ? 'Nothing deleted' : 'No banners found' }}</p>
                                    @unless($trashed)
                                        <p style="font-size: 13px; color: #616161;">
                                            <a href="{{ route('admin.banners.create') }}" style="color: #005bd3; text-decoration: none;"
                                               onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Create one now</a>
                                        </p>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        @if($banners->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $banners->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
    <style>
        /* The reorder arrows are 14px icons; on a touch screen they are the only
           reorder path (HTML5 drag does not fire there), so they grow to a
           finger-sized box. A mouse keeps the compact look. */
        @media (pointer: coarse) {
            .kk-nudge { min-width: 2.25rem; min-height: 2.25rem; display: inline-flex; align-items: center; justify-content: center; }
        }
    </style>
    <script>
        // One instance per placement, and the placement it belongs to travels
        // with the request. The endpoint scopes its writes to that placement, so
        // this screen cannot renumber the hero rows that Homepage > Hero Banners
        // owns and sorts by the same column.
        function bannerSorter(position) {
            return {
                position: position,
                draggingId: null,
                dropTargetId: null,
                // dragend cannot tell a real drop from Escape or a drop on the
                // page background; only a drop event inside the list can.
                dropped: false,
                saving: false,
                saved: false,
                failed: false,
                failedMessage: 'The new order was not saved. Reload the page and try again.',

                getItems() {
                    return Array.from(this.$refs.list.querySelectorAll('[data-id]'));
                },

                getCard(el) {
                    return el.closest('[data-id]');
                },

                indexOf(card) {
                    return this.getItems().indexOf(card);
                },

                onDragStart(e) {
                    const card = this.getCard(e.target);
                    this.draggingId = card.dataset.id;
                    this.dropped = false;
                    e.dataTransfer.effectAllowed = 'move';
                },

                onDrop() {
                    this.dropped = true;
                },

                onDragOver(e) {
                    const card = this.getCard(e.target);
                    if (!card || this.draggingId === null || card.dataset.id === this.draggingId) {
                        return;
                    }
                    this.dropTargetId = card.dataset.id;
                },

                onDragEnd() {
                    if (this.dropped && this.draggingId && this.dropTargetId && this.draggingId !== this.dropTargetId) {
                        const items = this.getItems();
                        const fromEl = items.find(el => el.dataset.id === this.draggingId);
                        const toEl = items.find(el => el.dataset.id === this.dropTargetId);
                        if (fromEl && toEl) {
                            this.moveDom(fromEl, toEl);
                        }
                    }
                    this.draggingId = null;
                    this.dropTargetId = null;
                    this.dropped = false;
                },

                moveUp(btnEl) {
                    const card = this.getCard(btnEl);
                    const index = this.indexOf(card);
                    if (index <= 0) return;
                    const items = this.getItems();
                    this.moveDom(card, items[index - 1]);
                },

                moveDown(btnEl) {
                    const card = this.getCard(btnEl);
                    const items = this.getItems();
                    const index = items.indexOf(card);
                    if (index >= items.length - 1) return;
                    this.moveDom(card, items[index + 1]);
                },

                moveDom(fromEl, toEl) {
                    const list = this.$refs.list;
                    const fromIndex = this.indexOf(fromEl);
                    const toIndex = this.indexOf(toEl);

                    if (fromIndex < toIndex) {
                        list.insertBefore(fromEl, toEl.nextSibling);
                    } else {
                        list.insertBefore(fromEl, toEl);
                    }

                    this.updateBadges();
                    this.saveOrder();
                },

                updateBadges() {
                    this.getItems().forEach((el, i) => {
                        const badge = el.querySelector('.kk-order-badge');
                        if (badge) badge.textContent = '#' + (i + 1);
                    });
                },

                saveOrder() {
                    const order = this.getItems().map(el => parseInt(el.dataset.id));

                    this.saving = true;
                    this.saved = false;
                    this.failed = false;

                    fetch('{{ route("admin.banners.reorder") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ position: this.position, order }),
                    })
                    .then(r => r.json().catch(() => null).then(data => ({ ok: r.ok, data })))
                    .then(({ ok, data }) => {
                        if (!ok || !data || data.success !== true) {
                            throw new Error((data && data.message) || 'not saved');
                        }
                        this.saving = false;
                        this.failed = false;
                        this.saved = true;
                        setTimeout(() => this.saved = false, 2000);
                    })
                    .catch(err => {
                        // Silence here left the admin believing an order had been
                        // stored that the server had in fact rejected.
                        this.saving = false;
                        this.saved = false;
                        this.failedMessage = err.message === 'not saved'
                            ? 'The new order was not saved. Reload the page and try again.'
                            : err.message;
                        this.failed = true;
                    });
                },
            };
        }
    </script>
    @endpush
</x-layouts.admin>
