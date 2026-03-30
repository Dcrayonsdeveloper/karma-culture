<x-layouts.admin>
    <x-slot name="title">Hero Banners</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Hero Banners</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.homepage.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Homepage
        </a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
        <!-- Add New Banner -->
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Add New Banner</h2>
            </div>
            <div style="padding: 1rem;">
                <form action="{{ route('admin.homepage.hero-banners.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Name</label>
                            <input type="text" name="name" class="form-input" placeholder="Banner name">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Image <span style="color: #d72c0d;">*</span></label>
                            <input type="file" name="image" accept="image/*" required class="form-input">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Recommended: 1920x700px, JPG/PNG</p>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Heading Text</label>
                            <input type="text" name="title" class="form-input" placeholder="Banner heading">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Subtitle</label>
                            <input type="text" name="subtitle" class="form-input" placeholder="Banner subtitle">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Button Text</label>
                            <input type="text" name="button_text" class="form-input" placeholder="Shop Now">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Link URL</label>
                            <input type="text" name="link" class="form-input" placeholder="/products">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Overlay Style</label>
                            <select name="overlay_style" class="form-select">
                                @foreach(\App\Models\Banner::OVERLAY_STYLES as $key => $label)
                                    <option value="{{ $key }}" {{ $key === 'left-dark' ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="font-size: 13px; width: 100%;">Add Banner</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Existing Banners -->
        <div x-data="bannerSorter()">
            <div style="display: flex; flex-direction: column; gap: 0.75rem;" x-ref="list" x-on:dragover.prevent>
                @forelse($banners as $index => $banner)
                    <div class="card" style="padding: 1rem; transition: all 0.15s;"
                         x-data="{ editing: false }"
                         draggable="true"
                         data-id="{{ $banner->id }}"
                         x-on:dragstart="onDragStart($event)"
                         x-on:dragover.prevent="onDragOver($event)"
                         x-on:dragend="onDragEnd()"
                         :style="draggingId == {{ $banner->id }} ? 'opacity: 0.5; transform: scale(0.98);' : (dropTargetId == {{ $banner->id }} && draggingId != {{ $banner->id }} ? 'border-top: 2px solid #005bd3;' : '')">

                        <!-- Display Mode -->
                        <div x-show="!editing">
                            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                <!-- Drag Handle + Position -->
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem; flex-shrink: 0; padding-top: 0.25rem;">
                                    <span class="pos-badge" style="font-size: 12px; font-weight: 700; color: #616161; width: 1.5rem; height: 1.5rem; display: flex; align-items: center; justify-content: center; background: #f6f6f7; border-radius: 0.25rem;">#{{ $index + 1 }}</span>
                                    <div style="cursor: grab; color: #616161;" title="Drag to reorder">
                                        <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                        </svg>
                                    </div>
                                    <button type="button" @click="moveUp($el)" style="color: #616161; background: none; border: none; cursor: pointer; padding: 0.125rem;" title="Move up">
                                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    </button>
                                    <button type="button" @click="moveDown($el)" style="color: #616161; background: none; border: none; cursor: pointer; padding: 0.125rem;" title="Move down">
                                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                </div>

                                <!-- Thumbnail -->
                                <div style="width: 11rem; height: 6rem; background: #f6f6f7; border-radius: 0.5rem; overflow: hidden; flex-shrink: 0;">
                                    <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>

                                <!-- Info -->
                                <div style="flex: 1; min-width: 0;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem;">
                                        <span style="font-size: 13px; font-weight: 600; color: #303030;">{{ $banner->name ?: $banner->title ?: 'Banner #' . $banner->id }}</span>
                                        @if($banner->is_active)
                                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
                                        @else
                                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ebebeb; color: #616161;">Hidden</span>
                                        @endif
                                    </div>
                                    @if($banner->title)
                                        <p style="font-size: 13px; color: #303030; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $banner->title }}</p>
                                    @endif
                                    @if($banner->subtitle)
                                        <p style="font-size: 12px; color: #616161; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $banner->subtitle }}</p>
                                    @endif
                                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.25rem; font-size: 12px; color: #616161;">
                                        @if($banner->button_text)
                                            <span>Button: {{ $banner->button_text }}</span>
                                        @endif
                                        @if($banner->link)
                                            <span>Link: {{ $banner->link }}</span>
                                        @endif
                                        <span>Overlay: {{ \App\Models\Banner::OVERLAY_STYLES[$banner->overlay_style] ?? 'Default' }}</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.75rem;">
                                        <button @click="editing = true" type="button" class="btn btn-primary" style="font-size: 12px; padding: 0.25rem 0.5rem;">Edit</button>
                                        <form action="{{ route('admin.homepage.hero-banners.toggle', $banner) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 0.25rem 0.5rem;">
                                                {{ $banner->is_active ? 'Hide' : 'Show' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.homepage.hero-banners.destroy', $banner) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this banner?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="font-size: 12px; padding: 0.25rem 0.5rem; background: none; border: 1px solid #d72c0d; color: #d72c0d; border-radius: 0.375rem; cursor: pointer;">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Mode -->
                        <div x-show="editing" x-cloak>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                                <span style="font-size: 13px; font-weight: 600; color: #303030;">Edit Banner</span>
                                <button @click="editing = false" type="button" style="color: #616161; background: none; border: none; cursor: pointer; padding: 0.25rem;">
                                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <form action="{{ route('admin.homepage.hero-banners.update', $banner) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Name</label>
                                        <input type="text" name="name" value="{{ $banner->name }}" class="form-input">
                                    </div>
                                    <div>
                                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Link URL</label>
                                        <input type="text" name="link" value="{{ $banner->link }}" class="form-input" placeholder="/products">
                                    </div>
                                    <div>
                                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Heading Text</label>
                                        <input type="text" name="title" value="{{ $banner->title }}" class="form-input" placeholder="Banner heading">
                                    </div>
                                    <div>
                                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Subtitle</label>
                                        <input type="text" name="subtitle" value="{{ $banner->subtitle }}" class="form-input" placeholder="Banner subtitle">
                                    </div>
                                    <div>
                                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Button Text</label>
                                        <input type="text" name="button_text" value="{{ $banner->button_text }}" class="form-input" placeholder="Shop Now">
                                    </div>
                                    <div>
                                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Overlay Style</label>
                                        <select name="overlay_style" class="form-select">
                                            @foreach(\App\Models\Banner::OVERLAY_STYLES as $key => $label)
                                                <option value="{{ $key }}" {{ $banner->overlay_style === $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div style="grid-column: span 2;">
                                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Replace Image</label>
                                        <input type="file" name="image" accept="image/*" class="form-input">
                                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Leave empty to keep current image</p>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Changes</button>
                                    <button @click="editing = false" type="button" class="btn btn-secondary" style="font-size: 13px;">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="card" style="padding: 3rem; text-align: center;">
                        <p style="font-size: 13px; color: #616161; margin: 0;">No hero banners yet. Add your first banner.</p>
                    </div>
                @endforelse
            </div>

            <!-- Status indicators -->
            <div x-show="saving" x-cloak style="margin-top: 0.75rem; font-size: 13px; color: #005bd3; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                <svg style="width: 1rem; height: 1rem; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24"><circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Saving order...
            </div>
            <div x-show="saved" x-cloak x-transition style="margin-top: 0.75rem; font-size: 13px; color: #1a7a2e; font-weight: 500;">Order saved.</div>
        </div>
    </div>

    @push('scripts')
    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
    <script>
        function bannerSorter() {
            return {
                draggingId: null,
                dropTargetId: null,
                saving: false,
                saved: false,

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
                    e.dataTransfer.effectAllowed = 'move';
                },

                onDragOver(e) {
                    const card = this.getCard(e.target);
                    if (!card || this.draggingId === null || card.dataset.id === this.draggingId) {
                        return;
                    }
                    this.dropTargetId = card.dataset.id;
                },

                onDragEnd() {
                    if (this.draggingId && this.dropTargetId && this.draggingId !== this.dropTargetId) {
                        const items = this.getItems();
                        const fromEl = items.find(el => el.dataset.id === this.draggingId);
                        const toEl = items.find(el => el.dataset.id === this.dropTargetId);
                        if (fromEl && toEl) {
                            this.moveDom(fromEl, toEl);
                        }
                    }
                    this.draggingId = null;
                    this.dropTargetId = null;
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
                        const badge = el.querySelector('.pos-badge');
                        if (badge) badge.textContent = '#' + (i + 1);
                    });
                },

                saveOrder() {
                    const order = this.getItems().map(el => parseInt(el.dataset.id));

                    this.saving = true;
                    this.saved = false;

                    fetch('{{ route("admin.homepage.hero-banners.reorder") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ order }),
                    })
                    .then(r => r.json())
                    .then(() => {
                        this.saving = false;
                        this.saved = true;
                        setTimeout(() => this.saved = false, 2000);
                    })
                    .catch(() => {
                        this.saving = false;
                    });
                },
            };
        }
    </script>
    @endpush
</x-layouts.admin>
