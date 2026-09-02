<x-layouts.admin>
    <x-slot name="title">Edit: {{ $blogPost->title }}</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.blog-posts.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $blogPost->title }}</h1>
        @if($blogPost->is_published)
            <span class="badge badge-success">Published</span>
        @else
            <span class="badge badge-warning">Draft</span>
        @endif
    </div>

    <form action="{{ route('admin.blog-posts.update', $blogPost) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">

            {{-- Main Content --}}
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Post Content</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            {{-- for/id pairs matter beyond accessibility here: the inline validator
                                 names the field from its own <label>, so an unlabelled input reports
                                 "This field is required" instead of "Title is required". --}}
                            <label for="post-title" class="form-label">Title <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="title" id="post-title" value="{{ old('title', $blogPost->title) }}" required
                                   minlength="2" maxlength="255"
                                   class="form-input">
                            @error('title')<p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="post-slug" class="form-label">Slug</label>
                            <input type="text" name="slug" id="post-slug" value="{{ old('slug', $blogPost->slug) }}"
                                   maxlength="255" pattern="[a-z0-9]+(-[a-z0-9]+)*"
                                   title="Lower-case letters, numbers and single hyphens only, for example summer-style-guide."
                                   class="form-input">
                            @error('slug')<p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="post-excerpt" class="form-label">Excerpt</label>
                            <textarea name="excerpt" id="post-excerpt" rows="3" maxlength="500" class="form-textarea"
                                      placeholder="Short description shown in blog listing...">{{ old('excerpt', $blogPost->excerpt) }}</textarea>
                            @error('excerpt')<p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="content" class="form-label">Content</label>
                            {{-- No native constraints on this one. CKEditor hides the textarea and
                                 only writes back into it as the form submits, which is after the
                                 inline validator has already read it - a `required` here would
                                 report an empty field the admin can see is full. Length and markup
                                 are enforced server-side instead. --}}
                            <textarea name="content" id="content">{{ old('content', $blogPost->content) }}</textarea>
                            @error('content')<p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- SEO --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">SEO</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label for="post-meta-title" class="form-label">Meta Title</label>
                            <input type="text" name="seo_data[meta_title]" id="post-meta-title"
                                   value="{{ old('seo_data.meta_title', $blogPost->seo_data['meta_title'] ?? '') }}"
                                   maxlength="255"
                                   class="form-input">
                        </div>
                        <div>
                            <label for="post-meta-description" class="form-label">Meta Description</label>
                            <textarea name="seo_data[meta_description]" id="post-meta-description" rows="2" maxlength="500" class="form-textarea">{{ old('seo_data.meta_description', $blogPost->seo_data['meta_description'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                {{-- Status --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Status</h2>
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" name="is_published" value="1" id="is_published"
                               style="width: 1rem; height: 1rem; accent-color: #303030;"
                               @checked(old('is_published', $blogPost->is_published))>
                        <div>
                            <span style="font-size: 13px; font-weight: 500; color: #303030;">Published</span>
                            @if($blogPost->published_at)
                                <p style="font-size: 12px; color: #616161;">Published {{ $blogPost->published_at->format('M d, Y') }}</p>
                            @endif
                        </div>
                    </label>
                </div>

                {{-- Featured Image --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Featured Image</h2>
                    @if($blogPost->featured_image)
                        <div style="margin-bottom: 0.75rem;">
                            <img src="{{ asset('storage/' . $blogPost->featured_image) }}" alt="{{ $blogPost->title }}"
                                 style="width: 100%; height: 8rem; object-fit: cover; border-radius: 0.5rem;">
                        </div>
                    @endif
                    <input type="file" name="featured_image" id="post-featured-image" aria-label="Featured image"
                           accept="image/jpeg,image/png,image/webp,image/gif" style="font-size: 13px; color: #616161;">
                    <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Upload new to replace existing. JPG, PNG, WebP or GIF. Max 2MB.</p>
                    @error('featured_image')<p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>@enderror
                </div>

                {{-- Category & Tags --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Classification</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label for="post-category" class="form-label">Category</label>
                            <input type="text" name="category" id="post-category" value="{{ old('category', $blogPost->category) }}"
                                   maxlength="100"
                                   class="form-input" placeholder="e.g. Fashion, Parenting Tips">
                        </div>
                        <div>
                            <label for="post-tags" class="form-label">Tags</label>
                            <input type="text" name="tags" id="post-tags"
                                   value="{{ old('tags', $blogPost->tags ? implode(', ', $blogPost->tags) : '') }}"
                                   maxlength="500"
                                   class="form-input" placeholder="tag1, tag2, tag3">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Comma separated</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <button type="submit" form="record-delete-form" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete post</button>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.blog-posts.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>

        <form id="record-delete-form" action="{{ route('admin.blog-posts.destroy', $blogPost) }}" method="POST"
              onsubmit="return confirm('Delete this post?')">
            @csrf @method('DELETE')
        </form>

    @push('styles')
    <style>
        .ck-editor__editable { min-height: 400px; }
        .ck.ck-editor__main>.ck-editor__editable:not(.ck-focused) { border-color: #d4d4d4; border-radius: 0 0 6px 6px; }
        .ck.ck-editor__main>.ck-editor__editable.ck-focused { border-color: var(--color-primary-500, #7c3aed); box-shadow: 0 0 0 1px var(--color-primary-500, #7c3aed); }
        .ck.ck-toolbar { border-radius: 6px 6px 0 0 !important; border-color: #d4d4d4 !important; background: #f9f9f9 !important; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#content'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'underline', '|', 'bulletedList', 'numberedList', '|', 'link', 'blockQuote', 'horizontalLine', '|', 'undo', 'redo'],
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
            .catch(error => console.error(error));
    </script>
    @endpush
</x-layouts.admin>
