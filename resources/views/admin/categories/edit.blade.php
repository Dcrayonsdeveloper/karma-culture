<x-layouts.admin>
    <x-slot name="title">Edit {{ $category->name }}</x-slot>

    <div x-data="categoryForm()">
        <!-- Top bar -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <a href="{{ route('admin.categories.index') }}" class="btn-icon" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $category->name }}</h1>
                <span class="badge {{ $category->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $category->is_active ? 'Active' : 'Draft' }}</span>
            </div>
        </div>

        <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <!-- Main content -->
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <!-- Category details -->
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Category details</h2>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <label for="name" class="form-label">Title <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required
                                       minlength="2" maxlength="255"
                                       class="form-input"
                                       @input="if(!slugManual) slug = toSlug($event.target.value)">
                                <x-field-error field="name" />
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label for="slug" class="form-label">URL handle</label>
                                    <input type="text" name="slug" id="slug" x-model="slug"
                                           maxlength="255" pattern="[a-z0-9]+(-[a-z0-9]+)*"
                                           title="Lower-case letters, digits and single hyphens, e.g. girls-dresses."
                                           class="form-input"
                                           @input="slugManual = ($event.target.value.trim() !== '')">
                                    <x-field-error field="slug" />
                                </div>
                                <div>
                                    {{-- Named `position` to match the column and the controller rule: as
                                         `sort_order` the value was validated by nothing, read a property
                                         that does not exist, and saved nowhere. --}}
                                    <label for="position" class="form-label">Sort order</label>
                                    <input type="number" name="position" id="position" value="{{ old('position', $category->position) }}" min="0" max="65535" step="1"
                                           class="form-input">
                                    <x-field-error field="position" />
                                </div>
                            </div>

                            <div>
                                <label for="parent_id" class="form-label">Parent category</label>
                                <select name="parent_id" id="parent_id" class="form-select">
                                    <option value="">None (Root Category)</option>
                                    @foreach($parentCategories as $parent)
                                        <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-field-error field="parent_id" />
                            </div>

                            <div>
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" rows="3" maxlength="2000"
                                          class="form-textarea">{{ old('description', $category->description) }}</textarea>
                                <x-field-error field="description" />
                            </div>
                        </div>
                    </div>

                    <!-- Media -->
                    <div class="card" style="padding: 1.25rem;" x-data="{ preview: null, removing: false }">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Media</h2>
                        <div style="display: flex; align-items: flex-start; gap: 1rem;">
                            <div style="width: 5rem; height: 5rem; border-radius: 0.75rem; background: #f6f6f7; border: 2px dashed #c9cccf; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                {{-- Contained: this well is the only look the admin gets at the
                                     tile they are publishing, and a cover crop of a wide banner
                                     or a tall poster hid exactly the edges that later went
                                     missing on the storefront. --}}
                                <template x-if="preview">
                                    <div class="kk-media" style="width: 100%; height: 100%; border-radius: 0.5rem;">
                                        <img class="kk-media__fill" :src="preview" alt="" aria-hidden="true">
                                        <img :src="preview" alt="Selected category image preview">
                                    </div>
                                </template>
                                <template x-if="!preview && !removing">
                                    @if($category->image_url)
                                        {{-- The admin layout has no delegated media-error handler, so a
                                             saved image whose file has gone marks its own frame. --}}
                                        <div class="kk-media" style="width: 100%; height: 100%; border-radius: 0.5rem;">
                                            <img class="kk-media__fill" src="{{ asset_v('storage/' . $category->image_url) }}" alt="" aria-hidden="true" onerror="this.remove()">
                                            <img src="{{ asset_v('storage/' . $category->image_url) }}" alt="Current category image"
                                                 onerror="this.closest('.kk-media').classList.add('is-broken')">
                                            <span class="kk-media__fallback" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                                                    <circle cx="8.5" cy="9.5" r="1.5"/>
                                                    <path d="M21 15l-5-5L5 20"/>
                                                </svg>
                                            </span>
                                        </div>
                                    @else
                                        <svg width="32" height="32" fill="none" stroke="#c9cccf" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                </template>
                                <template x-if="!preview && removing">
                                    <svg width="32" height="32" fill="none" stroke="#c9cccf" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </template>
                            </div>
                            <div style="flex: 1;">
                                <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
                                       style="font-size: 13px; color: #616161;"
                                       @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null; removing = false">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">JPG, PNG or WebP. Max 2MB.</p>
                                <x-field-error field="image" />
                                @if($category->image_url)
                                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; cursor: pointer;">
                                        <input type="checkbox" name="remove_image" value="1"
                                               style="width: 1rem; height: 1rem; accent-color: #d72c0d;"
                                               x-model="removing" @change="if(removing) { preview = null; }">
                                        <span style="font-size: 13px; color: #d72c0d;">Remove current image</span>
                                    </label>
                                @endif
                            </div>
                        </div>

                        {{-- Video --}}
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                            <label for="video_url_text" class="form-label">Video</label>
                            @if($category->video_url)
                                <video src="{{ str_starts_with($category->video_url, 'http') ? $category->video_url : asset_v($category->video_url) }}"
                                       controls muted controlsList="nodownload noplaybackrate noremoteplayback" disablepictureinpicture
                                       style="max-width: 220px; max-height: 140px; border-radius: 10px; margin: 0.5rem 0;"></video>
                            @endif
                            <input type="text" name="video_url_text" id="video_url_text" value="{{ old('video_url_text', $category->video_url) }}"
                                   maxlength="500" pattern="(https?://\S+|storage/[A-Za-z0-9._/\-]+)"
                                   title="A full https:// address, or a storage/… path from an upload here."
                                   class="form-input" placeholder="https://… or storage/categories/videos/file.mp4">
                            <x-field-error field="video_url_text" />
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Paste a URL above OR upload an mp4/webm below. Used in the home page bento tile when shown.</p>
                            <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime" class="form-input" style="margin-top: 0.5rem;">
                            <x-field-error field="video_file" />
                            @if($category->video_url)
                                <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" name="remove_video" value="1" style="width: 1rem; height: 1rem; accent-color: #d72c0d;">
                                    <span style="font-size: 13px; color: #d72c0d;">Remove current video</span>
                                </label>
                            @endif
                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Search engine listing</h2>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <label for="meta_title" class="form-label">Page title</label>
                                <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title', $category->meta_title) }}" maxlength="255"
                                       class="form-input">
                                <x-field-error field="meta_title" />
                            </div>
                            <div>
                                <label for="meta_description" class="form-label">Meta description</label>
                                <textarea name="meta_description" id="meta_description" rows="2" maxlength="500"
                                          class="form-textarea">{{ old('meta_description', $category->meta_description) }}</textarea>
                                <x-field-error field="meta_description" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <!-- Status -->
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 0.75rem;">Status</h2>
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}
                                   style="width: 1rem; height: 1rem; accent-color: #303030;">
                            <div>
                                <span style="font-size: 13px; font-weight: 500; color: #303030;">Active</span>
                                <p style="font-size: 12px; color: #616161;">Visible on the storefront</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <button type="submit" form="record-delete-form" class="btn" style="padding: 0; font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete category</button>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
        </form>

        <form id="record-delete-form" action="{{ route('admin.categories.destroy', $category) }}" method="POST"
              onsubmit="return confirm('Delete &quot;{{ addslashes($category->name) }}&quot;? This cannot be undone.')">
            @csrf
            @method('DELETE')
        </form>
    </div>

    @push('scripts')
    <script>
        function categoryForm() {
            return {
                slug: '{{ old("slug", $category->slug) }}',
                slugManual: true,
                toSlug(text) {
                    return text
                        .toLowerCase()
                        .trim()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/[\s_]+/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-+|-+$/g, '');
                }
            };
        }
    </script>
    @endpush
</x-layouts.admin>
