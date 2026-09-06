@php
    // The swatch already on the row, if any - the preview starts on it so an
    // edit shows what is saved rather than an empty dropzone.
    $currentImage = $preset->image_src ?? null;
@endphp
<div class="card" style="padding: 1.25rem;"
     x-data="{
         preview: @js($currentImage),
         remove: false,
         pick(e) {
             const file = e.target.files[0];
             if (!file) return;
             this.remove = false;
             const reader = new FileReader();
             reader.onload = ev => this.preview = ev.target.result;
             reader.readAsDataURL(file);
         },
         clear() {
             this.preview = null;
             this.remove = true;
             $refs.image.value = '';
         }
     }">
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <div>
            <label for="name" class="form-label">Name <span style="color: #d72c0d;">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name', $preset->name ?? '') }}" required
                   minlength="1" maxlength="60" class="form-input" placeholder="e.g. Matte">
            @error('name') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="form-label">Swatch image</label>
            <p style="font-size: 12px; color: #616161; margin: 0 0 0.5rem;">
                A close crop of the fabric. The home page hangs this on the shirt for this texture instead of a plain colour. Square works best; JPG, PNG or WebP up to 2 MB.
            </p>

            <div style="display: flex; align-items: flex-start; gap: 1rem;">
                {{-- The preview doubles as the file picker, so there is one thing to click. --}}
                <label style="flex: 0 0 auto; width: 6rem; height: 6rem; border: 1px dashed #b5b5b5; border-radius: 0.5rem; overflow: hidden; cursor: pointer; display: flex; align-items: center; justify-content: center; background: #fafafa; position: relative;">
                    <input type="file" name="image" x-ref="image" accept="image/jpeg,image/png,image/webp"
                           @change="pick($event)" style="position: absolute; inset: 0; opacity: 0; cursor: pointer;">
                    <template x-if="preview">
                        <img :src="preview" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                    </template>
                    <template x-if="!preview">
                        <span style="font-size: 11px; color: #8a8a8a; text-align: center; padding: 0 0.35rem;">Add image</span>
                    </template>
                </label>

                <div style="display: flex; flex-direction: column; gap: 0.4rem; padding-top: 0.15rem;">
                    <button type="button" @click="$refs.image.click()" class="btn btn-secondary" style="font-size: 12px; padding: 4px 10px;">
                        <span x-text="preview ? 'Replace image' : 'Choose image'"></span>
                    </button>
                    <button type="button" x-show="preview" @click="clear()"
                            style="font-size: 12px; color: #b71c1c; background: none; border: 0; padding: 0; cursor: pointer; text-align: left;">
                        Remove image
                    </button>
                    <p x-show="remove" x-cloak style="font-size: 11px; color: #616161; margin: 0;">The swatch is removed when you save.</p>
                </div>
            </div>

            {{-- Only meaningful on edit; harmless on create, where there is nothing to remove. --}}
            <input type="hidden" name="remove_image" :value="remove ? 1 : 0" value="0">
            @error('image') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: end;">
            <div>
                <label for="sort_order" class="form-label">Sort order</label>
                <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $preset->sort_order ?? 0) }}"
                       min="0" max="65535" step="1" class="form-input" placeholder="0">
                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Lower shows first in the picker.</p>
                @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding-bottom: 0.5rem;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" style="width: 1rem; height: 1rem; accent-color: #303030;"
                       @checked(old('is_active', $preset->is_active ?? true))>
                <span style="font-size: 13px; font-weight: 500; color: #303030;">Active</span>
            </label>
        </div>
    </div>
</div>
