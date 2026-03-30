<x-layouts.admin>
    <x-slot name="title">Edit Banner</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.banners.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $banner->name }}</h1>
        @if($banner->is_active)
            <span class="badge badge-success">Active</span>
        @else
            <span class="badge badge-warning">Inactive</span>
        @endif
    </div>

    <form action="{{ route('admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Banner Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Name <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $banner->name) }}" required
                                   class="form-input" style="width: 100%;">
                            @error('name')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Link URL</label>
                            <input type="url" name="link" value="{{ old('link', $banner->link) }}"
                                   class="form-input" style="width: 100%;" placeholder="https://example.com/page">
                            @error('link')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Images</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Banner Image</label>
                            @if($banner->image_url)
                                <div style="margin-bottom: 0.5rem;">
                                    <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->name }}"
                                         style="max-width: 100%; height: 8rem; object-fit: cover; border-radius: 0.5rem; border: 1px solid #e3e3e3;">
                                </div>
                            @endif
                            <input type="file" name="image" accept="image/*" style="font-size: 13px; color: #616161;">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Leave empty to keep current image. Max 5MB.</p>
                            @error('image')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Mobile Image</label>
                            @if($banner->mobile_image_url)
                                <div style="margin-bottom: 0.5rem;">
                                    <img src="{{ asset('storage/' . $banner->mobile_image_url) }}" alt="{{ $banner->name }} (mobile)"
                                         style="max-width: 100%; height: 6rem; object-fit: cover; border-radius: 0.5rem; border: 1px solid #e3e3e3;">
                                </div>
                            @endif
                            <input type="file" name="mobile_image" accept="image/*" style="font-size: 13px; color: #616161;">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Optional. Leave empty to keep current.</p>
                            @error('mobile_image')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Placement</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Position <span style="color: #d72c0d;">*</span></label>
                            <select name="position" required class="form-select" style="width: 100%;">
                                <option value="">Select position</option>
                                <option value="hero" @selected(old('position', $banner->position) == 'hero')>Hero</option>
                                <option value="sidebar" @selected(old('position', $banner->position) == 'sidebar')>Sidebar</option>
                                <option value="footer" @selected(old('position', $banner->position) == 'footer')>Footer</option>
                                <option value="category" @selected(old('position', $banner->position) == 'category')>Category</option>
                                <option value="popup" @selected(old('position', $banner->position) == 'popup')>Popup</option>
                            </select>
                            @error('position')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Priority</label>
                            <input type="number" name="priority" value="{{ old('priority', $banner->priority) }}" min="0"
                                   class="form-input" style="width: 100%;">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Lower number = higher priority</p>
                            @error('priority')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Schedule</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Starts At</label>
                            <input type="datetime-local" name="starts_at"
                                   value="{{ old('starts_at', $banner->starts_at?->format('Y-m-d\TH:i')) }}" class="form-input" style="width: 100%;">
                            @error('starts_at')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Ends At</label>
                            <input type="datetime-local" name="ends_at"
                                   value="{{ old('ends_at', $banner->ends_at?->format('Y-m-d\TH:i')) }}" class="form-input" style="width: 100%;">
                            @error('ends_at')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Status</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                   style="width: 1rem; height: 1rem; accent-color: #303030;"
                                   @checked(old('is_active', $banner->is_active))>
                            <label for="is_active" style="font-size: 13px; font-weight: 500; color: #303030;">Active</label>
                        </div>
                        <div style="padding-top: 0.5rem; border-top: 1px solid #e3e3e3; font-size: 13px;">
                            @if($banner->isActive())
                                <span class="badge badge-success">Currently Active</span>
                            @else
                                <span class="badge badge-warning">Inactive</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Info</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Created</span>
                            <span style="font-weight: 500; color: #303030;">{{ $banner->created_at->format('M d, Y') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Updated</span>
                            <span style="font-weight: 500; color: #303030;">{{ $banner->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST"
                      onsubmit="return confirm('Delete this banner?')" style="display: inline;">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete banner</button>
                </form>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>
</x-layouts.admin>
