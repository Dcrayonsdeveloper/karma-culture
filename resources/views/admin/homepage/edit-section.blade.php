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
                @switch($section->type)
                    @case('products')
                        <strong>Product Section</strong> &mdash; Controls the title and visibility of the "{{ $section->title }}" product slider on the homepage. Products are automatically loaded from the database.
                        @break
                    @case('benefits')
                        <strong>Benefits Section</strong> &mdash; Displays feature cards highlighting your brand's strengths. Add, edit, or remove benefit items below.
                        @break
                    @case('cta')
                        <strong>Promo Banner</strong> &mdash; A full-width promotional call-to-action banner displayed between product sections. Upload a background image for a visual banner, or set a background color.
                        @break
                    @case('testimonials')
                        <strong>Testimonials Section</strong> &mdash; Controls the heading and subtitle of the testimonials carousel. To manage individual reviews, go to <a href="{{ route('admin.homepage.testimonials') }}" style="color: #005bd3; text-decoration: underline; font-weight: 500;">Testimonials Management</a>.
                        @break
                    @case('newsletter')
                        <strong>Newsletter Section</strong> &mdash; Controls the heading and subtitle of the email subscription section at the bottom of the homepage.
                        @break
                    @case('categories')
                        <strong>Categories Section</strong> &mdash; Controls visibility of the category collection grids on the homepage. Category names and images are managed from <a href="{{ route('admin.categories.index') }}" style="color: #005bd3; text-decoration: underline; font-weight: 500;">Categories Management</a>.
                        @break
                    @default
                        <strong>Content Section</strong> &mdash; Controls the display of this content block on the homepage.
                @endswitch
            </div>
        </div>
    </div>

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
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Title <span style="color: #d72c0d;">*</span></label>
                        <input type="text" name="title" value="{{ $section->title }}" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Subtitle</label>
                        <textarea name="subtitle" rows="2" class="form-textarea">{{ $section->subtitle }}</textarea>
                    </div>
                    @if($section->image_url !== null || in_array($section->type, ['cta', 'promo']))
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Background Image</label>
                            @if($section->image_url)
                                <div style="margin-bottom: 0.5rem;">
                                    <img src="{{ asset('storage/' . $section->image_url) }}" alt="{{ $section->title }}" style="height: 8rem; object-fit: cover; border-radius: 0.5rem;">
                                </div>
                            @endif
                            <input type="file" name="image" accept="image/*" class="form-input">
                        </div>
                    @endif
                </div>
            </div>

            <!-- Display Options -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Display Options</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    @if(in_array($section->type, ['products', 'benefits', 'cta']))
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Button Text</label>
                            <input type="text" name="button_text" value="{{ $section->button_text }}" class="form-input" placeholder="e.g. View All, Shop Now">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Button Link</label>
                            <input type="text" name="button_link" value="{{ $section->button_link }}" class="form-input" placeholder="e.g. /products, /categories/boys">
                        </div>
                    @endif

                    @if($section->type === 'cta')
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Background Color</label>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="color" name="background_color" value="{{ $section->background_color ?? '#6F9CA2' }}" style="width: 2.5rem; height: 2.5rem; border-radius: 0.375rem; border: 1px solid #c9cccf; cursor: pointer; padding: 0.125rem;">
                                <input type="text" value="{{ $section->background_color ?? '#6F9CA2' }}" class="form-input" style="flex: 1;" readonly>
                            </div>
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Used when no background image is set</p>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Text Color</label>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="color" name="text_color" value="{{ $section->text_color ?? '#ffffff' }}" style="width: 2.5rem; height: 2.5rem; border-radius: 0.375rem; border: 1px solid #c9cccf; cursor: pointer; padding: 0.125rem;">
                                <input type="text" value="{{ $section->text_color ?? '#ffffff' }}" class="form-input" style="flex: 1;" readonly>
                            </div>
                        </div>
                    @endif

                    <div style="display: flex; align-items: center; gap: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e3e3e3;">
                        <input type="checkbox" name="is_active" value="1" {{ $section->is_active ? 'checked' : '' }} style="width: 1rem; height: 1rem; accent-color: #005bd3;">
                        <label style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">Section is visible on homepage</label>
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
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;" x-data="{ items: {{ json_encode($section->content ?? []) }} }">
                            <template x-for="(item, index) in items" :key="index">
                                <div style="padding: 1rem; background: #f6f6f7; border-radius: 0.5rem; position: relative;">
                                    <button type="button" @click="items.splice(index, 1)" style="position: absolute; top: 0.5rem; right: 0.5rem; color: #d72c0d; background: none; border: none; cursor: pointer; padding: 0.25rem;" title="Remove item">
                                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem; padding-right: 1.5rem;">
                                        <input type="text" :name="'content['+index+'][title]'" x-model="item.title" class="form-input" placeholder="Title">
                                        <input type="text" :name="'content['+index+'][description]'" x-model="item.description" class="form-input" placeholder="Description">
                                        <input type="text" :name="'content['+index+'][icon]'" x-model="item.icon" class="form-input" placeholder="Icon name (e.g. shield, heart)">
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
