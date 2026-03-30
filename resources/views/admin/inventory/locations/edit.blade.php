<x-layouts.admin>
    <x-slot name="title">Edit Location</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.inventory.locations.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $location->name }}</h1>
        @if($location->is_active)
            <span class="badge badge-success">Active</span>
        @else
            <span class="badge badge-warning">Inactive</span>
        @endif
    </div>

    <form action="{{ route('admin.inventory.locations.update', $location) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card" style="max-width: 640px; margin-top: 1rem; padding: 1.25rem;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Location Details</h2>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label class="form-label">Name <span style="color: #d72c0d;">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $location->name) }}" required
                           class="form-input">
                    @error('name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Code <span style="color: #d72c0d;">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $location->code) }}" required
                           class="form-input">
                    @error('code')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Address</label>
                    <textarea name="address" rows="2" class="form-textarea">{{ old('address', $location->address) }}</textarea>
                    @error('address')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           style="accent-color: #005bd3;"
                           @checked(old('is_active', $location->is_active))>
                    <label for="is_active" style="font-size: 13px; font-weight: 500; color: #303030;">Active</label>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <form action="{{ route('admin.inventory.locations.destroy', $location) }}" method="POST"
                      onsubmit="return confirm('Delete this location?')" style="display: inline;">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete location</button>
                </form>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.inventory.locations.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>
</x-layouts.admin>
