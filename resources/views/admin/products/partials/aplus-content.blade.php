@php
    $aplusImages = $product->aplusImages->map(fn ($i) => [
        'id' => $i->id,
        'url' => $i->image_url,
        'display_width' => $i->display_width ?? '',
        'display_height' => $i->display_height ?? '',
    ])->values();
@endphp

<!-- A+ Content -->
<div class="card overflow-hidden"
     x-data="aplusManager({
        storeUrl: '{{ route('admin.products.aplus.store', $product) }}',
        reorderUrl: '{{ route('admin.products.aplus.reorder', $product) }}',
        itemBase: '{{ url('admin/products/aplus') }}',
        images: {{ \Illuminate\Support\Js::from($aplusImages) }},
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
        <p class="text-xs mb-4" style="color:#616161; line-height:1.6;">
            W and H take a bare number as pixels ("600" is 600px), or a length like 600px or 50vh. Width also accepts a percentage of the screen (50%); height does not, because the banner is what gives the row its height. Both are maximums - a banner keeps its aspect ratio and is never stretched or cropped to fit them.
        </p>

        {{-- Saved banners (drag to reorder, or use the arrows) --}}
        <div class="space-y-2 mb-4" x-ref="list">
            <template x-for="(img, index) in images" :key="img.id">
                <div class="flex items-center gap-3 border rounded-lg px-3 py-2 aplus-tile"
                     style="border-color:#e3e3e3; background:#fff;"
                     :data-id="img.id" draggable="true"
                     @dragstart="onDragStart($event, index)" @dragover.prevent @drop.prevent="onDrop($event, index)" @dragend="onDragEnd()">
                    <span class="text-xs w-4 text-center shrink-0 select-none" style="color:#8a8a8a; cursor:grab;" x-text="index + 1"></span>
                    <img :src="img.url" alt="" class="rounded border shrink-0" style="width:72px; height:44px; object-fit:cover; border-color:#e3e3e3;">

                    {{-- Display size. Both fields are caps: blank = the storefront default (up to 1120px wide, height from the image). --}}
                    <div class="flex items-center gap-1.5 shrink-0">
                        <label class="text-[11px] shrink-0" style="color:#8a8a8a;" :for="'aplus-w-' + img.id">W</label>
                        <input type="text" class="aplus-size" :id="'aplus-w-' + img.id"
                               placeholder="auto" maxlength="20" aria-label="Banner width"
                               title="Width - a bare number is pixels. e.g. 600, 600px, 50%, auto. Blank = up to 1120px wide."
                               x-model="img.display_width"
                               @change="saveSize(img)" @keydown.enter.prevent="$event.target.blur()">
                        <label class="text-[11px] shrink-0" style="color:#8a8a8a;" :for="'aplus-h-' + img.id">H</label>
                        <input type="text" class="aplus-size" :id="'aplus-h-' + img.id"
                               placeholder="auto" maxlength="20" aria-label="Banner height"
                               title="Height - a bare number is pixels. e.g. 400, 400px, 50vh, auto. A cap only; the aspect ratio is always kept."
                               x-model="img.display_height"
                               @change="saveSize(img)" @keydown.enter.prevent="$event.target.blur()">
                    </div>

                    <span class="text-[11px] font-medium px-2 py-1 rounded shrink-0"
                          style="background:#f1f1f1; color:#616161;"
                          x-text="img.saving ? 'Saving…' : 'Saved'"></span>
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
    .aplus-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border:1px solid #e3e3e3; border-radius:6px; background:#fff; color:#616161; transition:background .12s, color .12s, border-color .12s; }
    .aplus-btn:hover { background:#f6f6f6; color:#303030; }
    .aplus-btn--off { opacity:.4; pointer-events:none; }
    .aplus-btn--danger:hover { background:#fff0ee; color:#d72c0d; border-color:#f2b8b0; }
    .aplus-drop { border:1.5px dashed #c9c9c9; padding:22px 16px; background:#fcfcfc; transition:border-color .12s, background .12s; }
    .aplus-drop:hover, .aplus-drop--active { border-color:#005bd3; background:#f5f9ff; }
    .aplus-tile { transition:box-shadow .12s, border-color .12s; }
    .aplus-tile:hover { border-color:#c9c9c9; }
    .aplus-size { width:64px; padding:3px 6px; font-size:11px; border:1px solid #e3e3e3; border-radius:5px; color:#303030; background:#fff; }
    .aplus-size:focus { outline:none; border-color:#005bd3; box-shadow:0 0 0 2px rgba(0,91,211,.12); }
    .aplus-size.aplus-size--bad { border-color:#d72c0d; background:#fff5f4; }
    /* Below the Shopify-ish admin breakpoint the row runs out of width, so let it wrap */
    @media (max-width:900px) { .aplus-tile { flex-wrap:wrap; } }
</style>

<script>
    function aplusManager(cfg) {
        return {
            images: cfg.images || [],
            storeUrl: cfg.storeUrl,
            reorderUrl: cfg.reorderUrl,
            itemBase: cfg.itemBase,
            uploading: false,
            dragOver: false,
            dragIndex: null,
            csrf() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; },
            // Mirrors ProductAplusImage::DISPLAY_SIZE_REGEX - the server revalidates.
            sizeOk(v) { return v === '' || /^(auto|\d{1,5}(\.\d{1,2})?(px|%|rem|em|vw|vh)?)$/i.test(v); },
            async saveSize(img) {
                const w = (img.display_width ?? '').toString().trim();
                const h = (img.display_height ?? '').toString().trim();
                img.display_width = w;
                img.display_height = h;

                const wEl = document.getElementById('aplus-w-' + img.id);
                const hEl = document.getElementById('aplus-h-' + img.id);
                const wBad = !this.sizeOk(w), hBad = !this.sizeOk(h);
                if (wEl) wEl.classList.toggle('aplus-size--bad', wBad);
                if (hEl) hEl.classList.toggle('aplus-size--bad', hBad);
                if (wBad || hBad) {
                    if (window.toastr) toastr.error('Use a number, a length like 600px or 50%, or "auto".');
                    return;
                }

                img.saving = true;
                try {
                    const res = await fetch(this.itemBase + '/' + img.id, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' },
                        body: JSON.stringify({ display_width: w, display_height: h }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        if (data.image) { img.display_width = data.image.display_width; img.display_height = data.image.display_height; }
                        if (window.toastr) toastr.success('Size saved');
                    } else if (window.toastr) {
                        toastr.error((data && data.message) || 'Could not save size');
                    }
                } catch (e) {
                    if (window.toastr) toastr.error('Could not save size');
                } finally {
                    img.saving = false;
                }
            },
            async upload(files) {
                if (!files || !files.length) return;
                const fd = new FormData();
                for (const f of files) fd.append('images[]', f);
                this.uploading = true;
                try {
                    const res = await fetch(this.storeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }, body: fd });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) { this.images.push(...data.images.map(i => ({ display_width: '', display_height: '', saving: false, ...i }))); if (window.toastr) toastr.success('Uploaded'); }
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
