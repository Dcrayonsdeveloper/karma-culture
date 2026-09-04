<x-layouts.admin>
    <x-slot name="title">Add Banner</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.banners.index') }}" class="btn-icon" style="flex-shrink: 0; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add banner</h1>
    </div>

    <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Banner Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            {{-- for/id pairs matter beyond accessibility here: the inline validator
                                 names the field from its own <label>, so an unlabelled input reports
                                 "This field is required" instead of "Name is required". --}}
                            <label for="banner-name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Name <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="name" id="banner-name" value="{{ old('name') }}" required
                                   minlength="2" maxlength="255"
                                   class="form-input" style="width: 100%;" placeholder="e.g. Summer Sale Hero Banner">
                            <x-field-error field="name" />
                        </div>

                        <div>
                            <label for="banner-link" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Link URL</label>
                            <input type="url" name="link" id="banner-link" value="{{ old('link') }}"
                                   maxlength="255" pattern="https?://.+" title="Enter a full web address starting with http:// or https://"
                                   class="form-input" style="width: 100%;" placeholder="https://example.com/page">
                            <x-field-error field="link" />
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Images</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label for="banner-image" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Banner Image <span style="color: #d72c0d;">*</span></label>
                            {{-- accept lists the formats the server rule takes, rather than image/*,
                                 which offers the admin an SVG or a TIFF the upload will then refuse. --}}
                            <input type="file" name="image" id="banner-image" required
                                   accept="image/jpeg,image/png,image/webp,image/gif" style="font-size: 13px; color: #616161;">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">JPG, PNG, WebP or GIF. Max 5MB. Recommended: 1920x600px</p>
                            <x-field-error field="image" />
                        </div>

                        <div>
                            <label for="banner-mobile-image" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Mobile Image</label>
                            <input type="file" name="mobile_image" id="banner-mobile-image"
                                   accept="image/jpeg,image/png,image/webp,image/gif" style="font-size: 13px; color: #616161;">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Optional. JPG, PNG, WebP or GIF. Max 5MB. Recommended: 768x400px</p>
                            <x-field-error field="mobile_image" />
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Placement</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label for="banner-position" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Position <span style="color: #d72c0d;">*</span></label>
                            <select name="position" id="banner-position" required class="form-select" style="width: 100%;">
                                <option value="">Select position</option>
                                <option value="hero" @selected(old('position') == 'hero')>Hero</option>
                                <option value="sidebar" @selected(old('position') == 'sidebar')>Sidebar</option>
                                <option value="footer" @selected(old('position') == 'footer')>Footer</option>
                                <option value="category" @selected(old('position') == 'category')>Category</option>
                                <option value="popup" @selected(old('position') == 'popup')>Popup</option>
                            </select>
                            <x-field-error field="position" />
                        </div>

                        <div>
                            <label for="banner-priority" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Priority</label>
                            <input type="number" name="priority" id="banner-priority" value="{{ old('priority', 0) }}"
                                   min="0" max="65535" step="1" inputmode="numeric"
                                   title="Enter a whole number between 0 and 65535."
                                   class="form-input" style="width: 100%;">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Lower number = higher priority</p>
                            <x-field-error field="priority" />
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Status</h2>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                               style="width: 1rem; height: 1rem; accent-color: #303030;"
                               @checked(old('is_active', true))>
                        <label for="is_active" style="font-size: 13px; font-weight: 500; color: #303030;">Active</label>
                    </div>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save banner</button>
            </div>
    </form>
</x-layouts.admin>
