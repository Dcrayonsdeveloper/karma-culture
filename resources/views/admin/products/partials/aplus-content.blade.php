@php
    $aplusImages = $product->aplusImages->map(fn ($i) => [
        'id' => $i->id,
        'url' => $i->image_url,
    ])->values();
    $aplusBannerSize = old('aplus_banner_size', $product->aplus_banner_size ?? 'fit');
    $aplusMaxHeight = old('aplus_banner_max_height', $product->aplus_banner_max_height);
@endphp

<!-- A+ Content -->
<div class="card overflow-hidden"
     x-data="aplusManager({
        storeUrl: '{{ route('admin.products.aplus.store', $product) }}',
        reorderUrl: '{{ route('admin.products.aplus.reorder', $product) }}',
        itemBase: '{{ url('admin/products/aplus') }}',
        images: {{ \Illuminate\Support\Js::from($aplusImages) }},
        bannerSize: '{{ $aplusBannerSize }}',
        maxHeight: '{{ $aplusMaxHeight }}',
     })">

    {{-- Card header --}}
    <div class="flex items-center gap-2 px-5 py-3 border-b" style="border-color:#e3e3e3; background:#fafafa;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#005bd3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
            <circle cx="8.5" cy="8.5" r="1.5"></circle>
            <path d="M21 15l-5-5L5 21"></path>
        </svg>
        <h2 class="text-[13px] font-semibold" style="color:#303030;">A+ Content</h2>
    </div>

    <div class="p-5">
        <p class="text-xs mb-4" style="color:#616161; line-height:1.6;">
            Rich banner images shown stacked at the bottom of the product's <span class="font-semibold" style="color:#005bd3;">Description</span> tab (like Amazon A+ content). They display top-to-bottom in the order below. Max 20 images, 5MB each.
        </p>

        {{-- Banner size on the product page (saved when you click "Update product") --}}
        <div class="rounded-lg border p-3 mb-4" style="border-color:#e3e3e3; background:#fafafa;">
            <label class="block text-xs font-semibold mb-1" style="color:#303030;">Banner size on product page</label>
            <div class="flex flex-wrap items-center gap-2">
                <select name="aplus_banner_size" x-model="bannerSize" class="form-input text-xs" style="max-width:220px;">
                    <option value="fit">Fit to screen (one banner per screen)</option>
                    <option value="full">Full width (edge-to-edge)</option>
                    <option value="custom">Custom max-height…</option>
                </select>
                <div class="flex items-center gap-1" x-show="bannerSize === 'custom'" x-cloak>
                    <input type="number" name="aplus_banner_max_height" x-model="maxHeight" min="100" max="6000" step="10"
                           class="form-input text-xs" style="width:110px;" placeholder="e.g. 800">
                    <span class="text-xs" style="color:#616161;">px tall</span>
                </div>
            </div>
            <p class="text-[11px] mt-2" style="color:#8a8a8a;">
                <span x-show="bannerSize === 'fit'">Each banner is scaled so the whole banner fits within one screen on desktop and mobile.</span>
                <span x-show="bannerSize === 'full'" x-cloak>Banners span the full content width; a tall banner may extend beyond one screen.</span>
                <span x-show="bannerSize === 'custom'" x-cloak>Banners are capped to the height you set (and never exceed one screen on mobile).</span>
            </p>
        </div>

        {{-- Saved banners (drag to reorder, or use the arrows) --}}
        <div class="space-y-2 mb-4" x-ref="list">
            <template x-for="(img, index) in images" :key="img.id">
                <div class="flex items-center gap-3 border rounded-lg px-3 py-2 aplus-tile"
                     style="border-color:#e3e3e3; background:#fff;"
                     :data-id="img.id" draggable="true"
                     @dragstart="onDragStart($event, index)" @dragover.prevent @drop.prevent="onDrop($event, index)" @dragend="onDragEnd()">
                    <span class="text-xs w-4 text-center shrink-0 select-none" style="color:#8a8a8a; cursor:grab;" x-text="index + 1"></span>
                    <img :src="img.url" alt="" class="rounded border shrink-0" style="width:72px; height:44px; object-fit:cover; border-color:#e3e3e3;">
                    <span class="text-[11px] font-medium px-2 py-1 rounded" style="background:#f1f1f1; color:#616161;">Saved</span>
                    <div class="flex items-center gap-1 ml-auto">
                        <button type="button" @click="move(index, -1)" :disabled="index === 0"
                                class="aplus-btn" :class="index === 0 ? 'aplus-btn--off' : ''" title="Move up" aria-label="Move up">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 15l-6-6-6 6"/></svg>
                        </button>
                        <button type="button" @click="move(index, 1)" :disabled="index === images.length - 1"
                                class="aplus-btn" :class="index === images.length - 1 ? 'aplus-btn--off' : ''" title="Move down" aria-label="Move down">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <button type="button" @click="remove(img, index)" class="aplus-btn aplus-btn--danger" title="Delete" aria-label="Delete">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Upload dropzone --}}
        <div class="rounded-lg text-center cursor-pointer aplus-drop"
             :class="dragOver ? 'aplus-drop--active' : ''"
             @click="$refs.aplusFile.click()"
             @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false" @drop.prevent="onFilesDrop($event)">
            <input type="file" x-ref="aplusFile" multiple accept="image/jpeg,image/jpg,image/png,image/webp,image/gif" style="display:none;" @change="upload($event.target.files)">
            <div class="mb-1" style="color:#005bd3;" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto;"><path d="M12 5v14M5 12h14"/></svg>
            </div>
            <p class="text-[13px] font-semibold" style="color:#005bd3;" x-text="uploading ? 'Uploading…' : 'Click to upload or drag & drop A+ banner images'"></p>
            <p class="text-[11px] mt-1" style="color:#8a8a8a;">JPEG, PNG, WebP, GIF up to 5MB &middot; shown in the order below</p>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    .aplus-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border:1px solid #e3e3e3; border-radius:6px; background:#fff; color:#616161; transition:background .12s, color .12s, border-color .12s; }
    .aplus-btn:hover { background:#f6f6f6; color:#303030; }
    .aplus-btn--off { opacity:.4; pointer-events:none; }
    .aplus-btn--danger:hover { background:#fff0ee; color:#d72c0d; border-color:#f2b8b0; }
    .aplus-drop { border:1.5px dashed #c9c9c9; padding:22px 16px; background:#fcfcfc; transition:border-color .12s, background .12s; }
    .aplus-drop:hover, .aplus-drop--active { border-color:#005bd3; background:#f5f9ff; }
    .aplus-tile { transition:box-shadow .12s, border-color .12s; }
    .aplus-tile:hover { border-color:#c9c9c9; }
</style>

<script>
    function aplusManager(cfg) {
        return {
            images: cfg.images || [],
            storeUrl: cfg.storeUrl,
            reorderUrl: cfg.reorderUrl,
            itemBase: cfg.itemBase,
            bannerSize: cfg.bannerSize || 'fit',
            maxHeight: cfg.maxHeight || '',
            uploading: false,
            dragOver: false,
            dragIndex: null,
            csrf() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; },
            async upload(files) {
                if (!files || !files.length) return;
                const fd = new FormData();
                for (const f of files) fd.append('images[]', f);
                this.uploading = true;
                try {
                    const res = await fetch(this.storeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }, body: fd });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) { this.images.push(...data.images); if (window.toastr) toastr.success('Uploaded'); }
                    else if (window.toastr) toastr.error((data && data.message) || 'Upload failed');
                } catch (err) { if (window.toastr) toastr.error('Upload error'); }
                finally { this.uploading = false; this.dragOver = false; if (this.$refs.aplusFile) this.$refs.aplusFile.value = ''; }
            },
            onFilesDrop(e) {
                this.dragOver = false;
                const files = e.dataTransfer && e.dataTransfer.files;
                if (files && files.length) this.upload(files);
            },
            async remove(img, index) {
                if (!confirm('Delete this A+ image?')) return;
                try {
                    const res = await fetch(this.itemBase + '/' + img.id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' } });
                    if (res.ok) { this.images.splice(index, 1); }
                    else if (window.toastr) { toastr.error('Delete failed'); }
                } catch (e) { if (window.toastr) toastr.error('Delete failed'); }
            },
            move(index, dir) {
                const ni = index + dir;
                if (ni < 0 || ni >= this.images.length) return;
                const [it] = this.images.splice(index, 1);
                this.images.splice(ni, 0, it);
                this.saveOrder();
            },
            onDragStart(e, index) { this.dragIndex = index; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', String(index)); },
            onDrop(e, index) {
                if (this.dragIndex === null || this.dragIndex === index) return;
                const [it] = this.images.splice(this.dragIndex, 1);
                this.images.splice(index, 0, it);
                this.dragIndex = null;
                this.saveOrder();
            },
            onDragEnd() { this.dragIndex = null; },
            async saveOrder() {
                try {
                    const res = await fetch(this.reorderUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }, body: JSON.stringify({ order: this.images.map(i => i.id) }) });
                    if (window.toastr) { res.ok ? toastr.success('Order saved') : toastr.error('Could not save order'); }
                } catch (e) { if (window.toastr) toastr.error('Could not save order'); }
            },
        };
    }
</script>
