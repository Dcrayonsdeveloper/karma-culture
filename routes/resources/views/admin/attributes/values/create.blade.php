<x-layouts.admin>
    <x-slot name="title">Add Value - {{ $attribute->name }}</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.attributes.edit', $attribute) }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add value</h1>
    </div>

    <form action="{{ route('admin.attributes.values.store', $attribute) }}" method="POST">
        @csrf

        <div class="card" style="max-width: 800px; padding: 1.25rem;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Value Details</h2>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Value <span style="color: #d72c0d;">*</span></label>
                    <input type="text" name="value" value="{{ old('value') }}" required
                           class="form-input" style="width: 100%;" placeholder="e.g. Red, XL, Cotton">
                    @error('value')
                        <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                @if($attribute->type === 'color')
                    <div>
                        <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Color Code</label>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="color" name="color_code" value="{{ old('color_code', '#000000') }}"
                                   style="width: 2.5rem; height: 2.5rem; border-radius: 0.375rem; border: 1px solid #c9cccf; cursor: pointer; padding: 0.125rem;">
                            <input type="text" value="{{ old('color_code', '#000000') }}" readonly
                                   class="form-input" style="flex: 1; background-color: #f6f6f7;" id="color_text">
                        </div>
                        @error('color_code')
                            <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Position</label>
                    <input type="number" name="position" value="{{ old('position', 0) }}" min="0"
                           class="form-input" style="width: 100%;">
                    @error('position')
                        <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <a href="{{ route('admin.attributes.edit', $attribute) }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save value</button>
            </div>
    </form>
</x-layouts.admin>
