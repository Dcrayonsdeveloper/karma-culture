<x-layouts.admin>
    <x-slot name="title">Edit Page</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.pages.index') }}" class="btn-icon" style="text-decoration: none;">
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
                            {{-- for/id pairs matter beyond accessibility here: the inline validator
                                 names the field from its own <label>, so an unlabelled input reports
                                 "This field is required" instead of "Title is required". --}}
                            <label for="page-title" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Title <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="title" id="page-title" value="{{ old('title', $page->title) }}" required
                                   minlength="2" maxlength="255"
                                   class="form-input" style="width: 100%;">
                            @error('title')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="page-slug" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Slug</label>
                            <input type="text" name="slug" id="page-slug" value="{{ old('slug', $page->slug) }}"
                                   maxlength="255" pattern="[a-z0-9]+(-[a-z0-9]+)*"
                                   title="Lower-case letters, numbers and single hyphens only, for example shipping-policy."
                                   class="form-input" style="width: 100%;">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Leave empty to auto-generate from title</p>
                            @error('slug')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="page-content" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Content</label>
                            {{-- No native constraints on this one. CKEditor hides the textarea and
                                 only writes back into it as the form submits, which is after the
                                 inline validator has already read it - a `required` here would
                                 report an empty field the admin can see is full. Length and markup
                                 are enforced server-side instead. --}}
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
                            <label for="page-meta-title" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Meta Title</label>
                            <input type="text" name="seo_data[meta_title]" id="page-meta-title"
                                   value="{{ old('seo_data.meta_title', $page->seo_data['meta_title'] ?? '') }}"
                                   maxlength="255"
                                   class="form-input" style="width: 100%;" placeholder="SEO title">
                        </div>
                        <div>
                            <label for="page-meta-description" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Meta Description</label>
                            <textarea name="seo_data[meta_description]" id="page-meta-description" rows="2" maxlength="500" class="form-textarea" style="width: 100%;"
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
                {{-- Submits the delete form declared after the edit form below.
                     The two must not nest: the browser hoisted the inner form's
                     _method=DELETE into the edit form, which already sent
                     _method=PUT, and PHP keeps the last value for a repeated key
                     — so clicking Save destroyed the record. --}}
                <button type="submit" form="kk-delete-page"
                        style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer; padding: 0.5rem 0; margin: -0.5rem 0;">Delete page</button>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>

    <form id="kk-delete-page" action="{{ route('admin.pages.destroy', $page) }}" method="POST"
          onsubmit="return confirm('Delete this page?')">
        @csrf @method('DELETE')
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
                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
                { model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
                { model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' },
            ]
        }
    })
    .catch(console.error);
</script>
@endpush
</x-layouts.admin>
