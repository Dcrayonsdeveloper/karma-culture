<x-layouts.admin>
    <x-slot name="title">Edit Page</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.pages.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $page->title }}</h1>
        @if($page->is_published)
            <span class="badge badge-success">Published</span>
        @else
            <span class="badge badge-warning">Draft</span>
        @endif
    </div>

    <form action="{{ route('admin.pages.update', $page) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Page Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Title <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="title" value="{{ old('title', $page->title) }}" required
                                   class="form-input" style="width: 100%;">
                            @error('title')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $page->slug) }}"
                                   class="form-input" style="width: 100%;">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Leave empty to auto-generate from title</p>
                            @error('slug')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Content</label>
                            <textarea name="content" id="page-content" rows="12" class="form-textarea" style="width: 100%;">{{ old('content', $page->content) }}</textarea>
                            @error('content')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">SEO</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Meta Title</label>
                            <input type="text" name="seo_data[meta_title]"
                                   value="{{ old('seo_data.meta_title', $page->seo_data['meta_title'] ?? '') }}"
                                   class="form-input" style="width: 100%;" placeholder="SEO title">
                        </div>
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Meta Description</label>
                            <textarea name="seo_data[meta_description]" rows="2" class="form-textarea" style="width: 100%;"
                                      placeholder="SEO description">{{ old('seo_data.meta_description', $page->seo_data['meta_description'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Status</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" id="is_published"
                                   style="width: 1rem; height: 1rem; accent-color: #303030;"
                                   @checked(old('is_published', $page->is_published))>
                            <label for="is_published" style="font-size: 13px; font-weight: 500; color: #303030;">Published</label>
                        </div>
                        <div style="padding-top: 0.5rem; border-top: 1px solid #e3e3e3; font-size: 13px;">
                            @if($page->is_published)
                                <span class="badge badge-success">Published</span>
                            @else
                                <span class="badge badge-warning">Draft</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Info</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Slug</span>
                            <span style="font-weight: 500; font-family: monospace;">/{{ $page->slug }}</span>
                        </div>
                        @if($page->published_at)
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #616161;">Published</span>
                                <span style="font-weight: 500; color: #303030;">{{ $page->published_at->format('M d, Y') }}</span>
                            </div>
                        @endif
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Created</span>
                            <span style="font-weight: 500; color: #303030;">{{ $page->created_at->format('M d, Y') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Updated</span>
                            <span style="font-weight: 500; color: #303030;">{{ $page->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <form action="{{ route('admin.pages.destroy', $page) }}" method="POST"
                      onsubmit="return confirm('Delete this page?')" style="display: inline;">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete page</button>
                </form>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
ClassicEditor
    .create(document.querySelector('#page-content'), {
        toolbar: ['heading','|','bold','italic','underline','|','link','bulletedList','numberedList','|','blockQuote','insertTable','|','undo','redo'],
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
            ]
        }
    })
    .catch(console.error);
</script>
@endpush
</x-layouts.admin>
