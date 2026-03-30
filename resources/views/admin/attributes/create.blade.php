<x-layouts.admin>
    <x-slot name="title">Add Attribute</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.attributes.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add attribute</h1>
    </div>

    <form action="{{ route('admin.attributes.store') }}" method="POST">
        @csrf

        <div class="card" style="max-width: 800px; padding: 1.25rem;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Attribute Details</h2>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Name <span style="color: #d72c0d;">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="form-input" style="width: 100%;" placeholder="e.g. Size, Color, Material">
                    @error('name')
                        <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Type <span style="color: #d72c0d;">*</span></label>
                    <select name="type" class="form-select" style="width: 100%;" required>
                        <option value="select" @selected(old('type') === 'select')>Select (Dropdown)</option>
                        <option value="color" @selected(old('type') === 'color')>Color (Swatch)</option>
                        <option value="text" @selected(old('type') === 'text')>Text (Free Input)</option>
                    </select>
                    @error('type')
                        <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="hidden" name="is_filterable" value="0">
                        <input type="checkbox" name="is_filterable" value="1" id="is_filterable"
                               style="width: 1rem; height: 1rem; accent-color: #303030;"
                               @checked(old('is_filterable'))>
                        <label for="is_filterable" style="font-size: 13px; font-weight: 500; color: #303030;">Filterable</label>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="hidden" name="is_visible" value="0">
                        <input type="checkbox" name="is_visible" value="1" id="is_visible"
                               style="width: 1rem; height: 1rem; accent-color: #303030;"
                               @checked(old('is_visible', true))>
                        <label for="is_visible" style="font-size: 13px; font-weight: 500; color: #303030;">Visible on product page</label>
                    </div>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save attribute</button>
            </div>
    </form>
</x-layouts.admin>
