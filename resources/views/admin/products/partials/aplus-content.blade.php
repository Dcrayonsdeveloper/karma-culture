@php
    $aplusImages = $product->aplusImages->map(fn ($i) => [
        'id' => $i->id,
        'url' => $i->image_url,
        'alt_text' => $i->alt_text,
    ])->values();
@endphp

<!-- A+ Content -->
<div class="card p-5"
     x-data="aplusManager({
        storeUrl: '{{ route('admin.products.aplus.store', $product) }}',
        reorderUrl: '{{ route('admin.products.aplus.reorder', $product) }}',
        itemBase: '{{ url('admin/products/aplus') }}',
        images: {{ \Illuminate\Support\Js::from($aplusImages) }},
     })">
    <h2 class="text-[13px] font-semibold mb-1" style="color: #303030;">A+ Content <span style="color:#999;font-weight:400;">(Amazon-style banners)</span></h2>
    <p class="text-xs mb-3" style="color: #616161;">Banner images shown on the product page after the description, one below another. <strong>Drag</strong> a tile (or use ↑/↓) to reorder — changes save instantly.</p>

    <div class="space-y-2 mb-3" x-ref="list">
        <template x-for="(img, index) in images" :key="img.id">
            <div class="flex items-start gap-3 border rounded-lg p-2 aplus-tile" style="border-color:#e3e3e3;"
                 :data-id="img.id" draggable="true"
                 @dragstart="onDragStart($event, index)" @dragover.prevent @drop.prevent="onDrop($event, index)" @dragend="onDragEnd()">
                <div class="pt-6 select-none" style="cursor:grab;color:#b5b5b5;font-size:16px;" aria-hidden="true">&#8942;&#8942;</div>
                <img :src="img.url" alt="" class="rounded border" style="width:120px;height:64px;object-fit:cover;flex-shrink:0;border-color:#e3e3e3;">
                <div class="flex-1 min-w-0">
                    <input type="text" class="form-input w-full text-xs mb-2" placeholder="Alt text (optional)" x-model="img.alt_text" @change="saveAlt(img)">
                    <div class="flex items-center gap-3 text-xs">
                        <button type="button" @click="move(index, -1)" :disabled="index === 0" style="color:#005bd3;" :style="index===0 ? 'opacity:.4;cursor:not-allowed' : ''">&uarr; Up</button>
                        <button type="button" @click="move(index, 1)" :disabled="index === images.length - 1" style="color:#005bd3;" :style="index===images.length-1 ? 'opacity:.4;cursor:not-allowed' : ''">&darr; Down</button>
                        <button type="button" @click="remove(img, index)" style="color:#d72c0d;">Delete</button>
                        <span class="ml-auto" style="color:#999;">#<span x-text="index + 1"></span></span>
                    </div>
                </div>
            </div>
        </template>
        <p x-show="!images.length" class="text-xs" style="color:#8a8a8a;">No A+ images yet — upload banners below.</p>
    </div>

    <div class="border border-dashed rounded-lg p-3 text-center cursor-pointer hover:border-neutral-400 transition-colors" style="border-color:#b5b5b5;"
         @click="$refs.aplusFile.click()">
        <input type="file" x-ref="aplusFile" multiple accept="image/jpeg,image/jpg,image/png,image/webp" style="display:none;" @change="upload($event)">
        <p class="text-xs font-medium" style="color:#005bd3;" x-text="uploading ? 'Uploading…' : 'Upload A+ banner images (JPG/PNG/WEBP, max 8MB each)'">Upload A+ banner images</p>
    </div>
</div>

<script>
    function aplusManager(cfg) {
        return {
            images: cfg.images || [],
            storeUrl: cfg.storeUrl,
            reorderUrl: cfg.reorderUrl,
            itemBase: cfg.itemBase,
            uploading: false,
            dragIndex: null,
            csrf() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; },
            async upload(e) {
                const files = e.target.files;
                if (!files || !files.length) return;
                const fd = new FormData();
                for (const f of files) fd.append('images[]', f);
                this.uploading = true;
                try {
                    const res = await fetch(this.storeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }, body: fd });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) { this.images.push(...data.images); if (window.toastr) toastr.success('Uploaded'); }
                    else if (window.toastr) toastr.error(data.message || 'Upload failed');
                } catch (err) { if (window.toastr) toastr.error('Upload error'); }
                finally { this.uploading = false; e.target.value = ''; }
            },
            async remove(img, index) {
                if (!confirm('Delete this A+ image?')) return;
                try {
                    const res = await fetch(this.itemBase + '/' + img.id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' } });
                    if (res.ok) this.images.splice(index, 1);
                } catch (e) {}
            },
            async saveAlt(img) {
                try {
                    await fetch(this.itemBase + '/' + img.id, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }, body: JSON.stringify({ alt_text: img.alt_text }) });
                } catch (e) {}
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
                    await fetch(this.reorderUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }, body: JSON.stringify({ order: this.images.map(i => i.id) }) });
                    if (window.toastr) toastr.success('Order saved');
                } catch (e) {}
            },
        };
    }
</script>
