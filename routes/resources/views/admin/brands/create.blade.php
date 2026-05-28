<x-layouts.admin>
    <x-slot name="title">Add Brand</x-slot>

    <div>
        <!-- Top bar -->
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
            <a href="{{ route('admin.brands.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add brand</h1>
        </div>

        <form action="{{ route('admin.brands.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <!-- Main content -->
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Brand details</h2>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <label class="form-label">Name <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. L'Oreal">
                                @error('name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-textarea" placeholder="Brief description...">{{ old('description') }}</textarea>
                                @error('description') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Media -->
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Media</h2>
                        <div style="display: flex; align-items: center; gap: 1.25rem;">
                            <div style="width: 4rem; height: 4rem; border-radius: 0.75rem; background: #f6f6f7; display: flex; align-items: center; justify-content: center; border: 1px solid #e3e3e3; flex-shrink: 0;">
                                <svg width="24" height="24" fill="none" stroke="#c9cccf" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div style="flex: 1;">
                                <input type="file" name="logo" accept="image/*" style="font-size: 13px; color: #616161;">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">PNG, JPG or SVG. Max 2MB.</p>
                                @error('logo') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 0.75rem;">Status</h2>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1"
                                       style="width: 1rem; height: 1rem; accent-color: #303030;"
                                       @checked(old('is_active', true))>
                                <div>
                                    <span style="font-size: 13px; font-weight: 500; color: #303030;">Active</span>
                                    <p style="font-size: 12px; color: #616161;">Visible on the storefront</p>
                                </div>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                                <input type="hidden" name="is_featured" value="0">
                                <input type="checkbox" name="is_featured" value="1"
                                       style="width: 1rem; height: 1rem; accent-color: #303030;"
                                       @checked(old('is_featured', false))>
                                <div>
                                    <span style="font-size: 13px; font-weight: 500; color: #303030;">Featured</span>
                                    <p style="font-size: 12px; color: #616161;">Show in brand carousel</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save brand</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
