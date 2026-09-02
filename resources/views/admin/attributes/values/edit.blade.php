<x-layouts.admin>
    <x-slot name="title">Edit Value</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.attributes.edit', $value->attribute) }}" style="padding: 0.5rem; margin: -0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $value->value }}</h1>
    </div>

    <form action="{{ route('admin.values.update', $value) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card" style="max-width: 800px; padding: 1.25rem;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Value Details</h2>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label for="value" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Value <span style="color: #d72c0d;">*</span></label>
                    <input type="text" name="value" id="value" value="{{ old('value', $value->value) }}" required
                           minlength="1" maxlength="255"
                           class="form-input" style="width: 100%;">
                    @error('value')
                        <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                @if($value->attribute->type === 'color')
                    <div>
                        <label for="color_code" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Color Code</label>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="color" name="color_code" id="color_code" value="{{ old('color_code', $value->color_code ?? '#000000') }}"
                                   style="width: 2.5rem; height: 2.5rem; border-radius: 0.375rem; border: 1px solid #c9cccf; cursor: pointer; padding: 0.125rem;">
                            <input type="text" value="{{ old('color_code', $value->color_code ?? '#000000') }}" readonly
                                   class="form-input" style="flex: 1; background-color: #f6f6f7;">
                        </div>
                        @error('color_code')
                            <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label for="position" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Position</label>
                    <input type="number" name="position" id="position" value="{{ old('position', $value->position) }}" min="0" max="65535" step="1"
                           class="form-input" style="width: 100%;">
                    @error('position')
                        <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <button type="submit" form="record-delete-form" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; padding: 0.5rem 0.75rem; margin-left: -0.75rem; cursor: pointer;">Delete value</button>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.attributes.edit', $value->attribute) }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>

        <form id="record-delete-form" action="{{ route('admin.values.destroy', $value) }}" method="POST"
              onsubmit="return confirm('Delete this value?')">
            @csrf @method('DELETE')
        </form>
</x-layouts.admin>
