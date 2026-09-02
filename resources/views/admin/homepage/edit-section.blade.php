<x-layouts.admin>
    <x-slot name="title">Edit Section: {{ $section->title }}</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Edit: {{ $section->title }}</h1>
            <a href="{{ route('admin.homepage.sections') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Sections</a>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.homepage.sections') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Homepage
        </a>
    </div>

    {{-- Type-specific help text --}}
    <div style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #f0f5ff; border: 1px solid #d0e0fc; border-radius: 0.5rem;">
        <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
            <svg style="width: 1.25rem; height: 1.25rem; color: #005bd3; flex-shrink: 0; margin-top: 0.125rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div style="font-size: 13px; color: #303030;">
                @switch($section->key === 'about_us' ? 'about_us' : $section->type)
                    @case('about_us')
                        <strong>About Us Section</strong> - The video block partway down the home page. The title, subtitle and button below are what visitors read; the three videos themselves are set in <a href="{{ route('admin.homepage.site-settings') }}" style="color: #005bd3; text-decoration: underline; font-weight: 500;">Site Settings</a>. Untick "visible" to remove the whole block from the home page.
                        @break
                    @case('products')
                        <strong>Product Section</strong> - Controls the title and visibility of the "{{ $section->title }}" product slider on the homepage. Products are automatically loaded from the database.
                        @break
                    @case('benefits')
                        <strong>Benefits Section</strong> - Displays feature cards highlighting your brand's strengths. Add, edit, or remove benefit items below.
                        @break
                    @case('cta')
                        <strong>Promo Banner</strong> - A full-width promotional call-to-action banner displayed between product sections. Upload a background image for a visual banner, or set a background color.
                        @break
                    @case('testimonials')
                        <strong>Testimonials Section</strong> - Controls the heading and subtitle of the testimonials carousel. To manage individual reviews, go to <a href="{{ route('admin.homepage.testimonials') }}" style="color: #005bd3; text-decoration: underline; font-weight: 500;">Testimonials Management</a>.
                        @break
                    @case('newsletter')
                        <strong>Newsletter Section</strong> - Controls the heading and subtitle of the email subscription section at the bottom of the homepage.
                        @break
                    @case('categories')
                        <strong>Categories Section</strong> - Controls visibility of the category collection grids on the homepage. Category names and images are managed from <a href="{{ route('admin.categories.index') }}" style="color: #005bd3; text-decoration: underline; font-weight: 500;">Categories Management</a>.
                        @break
                    @default
                        <strong>Content Section</strong> - Controls the display of this content block on the homepage.
                @endswitch
            </div>
        </div>
    </div>

    {{-- A rejected save used to bounce straight back to this page with no
         explanation and every field reset to what was in the database, so the
         admin's edit was gone and nothing said why. --}}
    @if($errors->any())
        <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fff0f0; border: 1px solid #f0c2bd; border-radius: 0.5rem;">
            <div style="font-size: 13px; font-weight: 600; color: #8e1f0b; margin-bottom: 0.25rem;">This section was not saved</div>
            <ul style="margin: 0; padding-left: 1.1rem; font-size: 13px; color: #8e1f0b;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #eafdf0; border: 1px solid #a9e3bf; border-radius: 0.5rem; font-size: 13px; color: #1a7a2e;">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.homepage.sections.update', $section) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <!-- Section Content -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Section Content</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">Type: {{ ucfirst($section->type) }} &middot; Key: {{ $section->key }}</p>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    {{-- for/id pairs matter beyond accessibility here: the inline validator
                         names the field from its own <label>, so an unlabelled input reports
                         "This field is required" instead of "Title is required". --}}
                    <div>
                        <label for="section-title" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Title <span style="color: #d72c0d;">*</span></label>
                        <input type="text" name="title" id="section-title" value="{{ old('title', $section->title) }}" required minlength="2" maxlength="255" class="form-input">
                    </div>
                    <div>
                        <label for="section-subtitle" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Subtitle</label>
                        <textarea name="subtitle" id="section-subtitle" rows="2" maxlength="500" class="form-textarea">{{ old('subtitle', $section->subtitle) }}</textarea>
                    </div>
                    @if($section->image_url !== null || in_array($section->type, ['cta', 'promo']))
                        <div>
                            <label for="section-image" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Background Image</label>
                            @if($section->image_url)
                                <div style="margin-bottom: 0.5rem;">
                                    <img src="{{ asset_v('storage/' . $section->image_url) }}" alt="{{ $section->title }}" style="height: 8rem; object-fit: cover; border-radius: 0.5rem;">
                                </div>
                            @endif
                            <input type="file" name="image" id="section-image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">JPG, PNG, WebP or GIF. Max 5MB.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Display Options -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Display Options</h2>
                </div>
                <div x-data style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    {{-- 'content' belongs here: the About Us block on the home page
                         renders button_text and button_link as its "Our Story" link,
                         so gating these two fields out of a content section left a
                         live storefront link with no way to edit it. --}}
                    @if(in_array($section->type, ['products', 'benefits', 'cta', 'content']))
                        <div>
                            <label for="section-button-text" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Button Text</label>
                            <input type="text" name="button_text" id="section-button-text" value="{{ old('button_text', $section->button_text) }}" maxlength="100" class="form-input" placeholder="e.g. View All, Shop Now">
                        </div>
                        <div>
                            <label for="section-button-link" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Button Link</label>
                            {{-- A relative path, a full http(s) address, mailto:, tel: or a #anchor.
                                 The About Us section renders this straight into an href, so
                                 `javascript:` here would be stored XSS on the home page. --}}
                            <input type="text" name="button_link" id="section-button-link" value="{{ old('button_link', $section->button_link) }}" maxlength="255"
                                   pattern="(https?://|mailto:|tel:)\S+|/(?!/)\S*|#\S*"
                                   title="Enter a path such as /products, or a full https:// address."
                                   class="form-input" placeholder="e.g. /products, /categories/boys">
                        </div>
                    @endif

                    @if($section->type === 'cta')
                        <div>
                            <label for="section-background-color" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Background Color</label>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="color" name="background_color" id="section-background-color" @input="$refs.background_colorHex.value = $event.target.value" value="{{ old('background_color', $section->background_color ?? '#6F9CA2') }}" style="width: 2.5rem; height: 2.5rem; border-radius: 0.375rem; border: 1px solid #c9cccf; cursor: pointer; padding: 0.125rem;">
                                <input type="text" value="{{ old('background_color', $section->background_color ?? '#6F9CA2') }}" x-ref="background_colorHex" aria-label="Background colour hex" class="form-input" style="flex: 1;" readonly>
                            </div>
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Used when no background image is set</p>
                        </div>
                        <div>
                            <label for="section-text-color" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Text Color</label>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="color" name="text_color" id="section-text-color" @input="$refs.text_colorHex.value = $event.target.value" value="{{ old('text_color', $section->text_color ?? '#ffffff') }}" style="width: 2.5rem; height: 2.5rem; border-radius: 0.375rem; border: 1px solid #c9cccf; cursor: pointer; padding: 0.125rem;">
                                <input type="text" value="{{ old('text_color', $section->text_color ?? '#ffffff') }}" x-ref="text_colorHex" aria-label="Text colour hex" class="form-input" style="flex: 1;" readonly>
                            </div>
                        </div>
                    @endif

                    <div style="display: flex; align-items: center; gap: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e3e3e3;">
                        <input type="checkbox" name="is_active" id="section-is-active" value="1" {{ old('is_active', $section->is_active) ? 'checked' : '' }} style="width: 1rem; height: 1rem; accent-color: #005bd3;">
                        <label for="section-is-active" style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">Section is visible on homepage</label>
                    </div>
                </div>
            </div>

            @if($section->type === 'benefits')
                <div class="card" style="grid-column: span 2;">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Benefits Items</h2>
                        <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">Available icons: shield, comfort, wash, colors, tagless, heart, shipping, return</p>
                    </div>
                    <div style="padding: 1rem;">
                        {{-- Tells updateSection that this form edits the repeater, so
                             removing the last card saves as an empty list instead of
                             looking like a form that has no repeater at all. --}}
                        <input type="hidden" name="has_content_repeater" value="1">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;" x-data="{ items: {{ json_encode(old('content', $section->content ?? [])) }} }">
                            <template x-for="(item, index) in items" :key="index">
                                <div style="padding: 1rem; background: #f6f6f7; border-radius: 0.5rem; position: relative;">
                                    <button type="button" @click="items.splice(index, 1)" style="position: absolute; top: 0.5rem; right: 0.5rem; color: #d72c0d; background: none; border: none; cursor: pointer; padding: 0.25rem;" title="Remove item">
                                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem; padding-right: 1.5rem;">
                                        <input type="text" :name="'content['+index+'][title]'" x-model="item.title" maxlength="120"
                                               aria-label="Benefit title" class="form-input" placeholder="Title">
                                        <input type="text" :name="'content['+index+'][description]'" x-model="item.description" maxlength="255"
                                               aria-label="Benefit description" class="form-input" placeholder="Description">
                                        {{-- The icon name selects a template partial, so it is a key
                                             rather than free text - keep it to the characters a key
                                             can have. --}}
                                        <input type="text" :name="'content['+index+'][icon]'" x-model="item.icon" maxlength="40"
                                               pattern="[A-Za-z0-9_\-]+" title="Letters, numbers, hyphens and underscores only."
                                               aria-label="Benefit icon name" class="form-input" placeholder="Icon name (e.g. shield, heart)">
                                    </div>
                                </div>
                            </template>
                            <div style="padding: 1rem; background: #f6f6f7; border-radius: 0.5rem; border: 2px dashed #c9cccf; display: flex; align-items: center; justify-content: center; min-height: 120px;">
                                <button type="button" @click="items.push({title: '', description: '', icon: ''})" class="btn btn-secondary" style="font-size: 12px;">
                                    + Add Item
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="{{ route('admin.homepage.sections') }}" class="btn btn-secondary" style="font-size: 13px;">Cancel</a>
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Changes</button>
        </div>
    </form>
</x-layouts.admin>
