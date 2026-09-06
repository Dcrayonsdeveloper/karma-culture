<div class="card" style="padding: 1.25rem;">
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <div>
            <label for="name" class="form-label">Name <span style="color: #d72c0d;">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name', $preset->name ?? '') }}" required
                   minlength="1" maxlength="60" class="form-input" placeholder="e.g. Matte">
            @error('name') <p class="form-error">{{ $message }}</p> @enderror
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
