@php $hexValue = old('hex', $preset->hex ?? '#000000'); @endphp
<div class="card" style="padding: 1.25rem;">
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <div>
            <label for="name" class="form-label">Name <span style="color: #d72c0d;">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name', $preset->name ?? '') }}" required
                   minlength="1" maxlength="60" class="form-input" placeholder="e.g. Navy">
            @error('name') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="hex" class="form-label">Swatch <span style="color: #d72c0d;">*</span></label>
            <div style="display: flex; align-items: center; gap: 0.75rem;" x-data="{ hex: @js($hexValue) }">
                <input type="color" x-model="hex" name="hex" id="hex"
                       style="width: 3rem; height: 2.5rem; border: 1px solid #d4d4d4; border-radius: 0.375rem; padding: 0; background: none; cursor: pointer;">
                <input type="text" x-model="hex" aria-label="Hex code" maxlength="7"
                       class="form-input" style="max-width: 8rem; text-transform: uppercase;">
            </div>
            @error('hex') <p class="form-error">{{ $message }}</p> @enderror
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
