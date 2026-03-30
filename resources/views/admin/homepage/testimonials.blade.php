<x-layouts.admin>
    <x-slot name="title">Testimonials</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Testimonials</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.homepage.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Homepage
        </a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
        <!-- Add Testimonial -->
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Add Testimonial</h2>
            </div>
            <div style="padding: 1rem;">
                <form action="{{ route('admin.homepage.testimonials.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Customer Name <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="name" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Title/Role</label>
                            <input type="text" name="title" class="form-input" placeholder="e.g. Happy Parent">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Review <span style="color: #d72c0d;">*</span></label>
                            <textarea name="content" rows="4" required class="form-textarea"></textarea>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Rating <span style="color: #d72c0d;">*</span></label>
                            <select name="rating" class="form-select">
                                <option value="5">5 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="3">3 Stars</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Product Name</label>
                            <input type="text" name="product_name" class="form-input" placeholder="e.g. Velvet Matte Lipstick">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Avatar Photo</label>
                            <input type="file" name="avatar" accept="image/*" class="form-input">
                        </div>
                        <button type="submit" class="btn btn-primary" style="font-size: 13px; width: 100%;">Add Testimonial</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Existing Testimonials -->
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @forelse($testimonials as $testimonial)
                <div class="card" style="padding: 1rem;">
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                        <div style="width: 2.5rem; height: 2.5rem; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;">
                            @if($testimonial->avatar_url)
                                <img src="{{ asset('storage/' . $testimonial->avatar_url) }}" alt="{{ $testimonial->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <span style="color: #616161; font-weight: 600; font-size: 14px;">{{ substr($testimonial->name, 0, 1) }}</span>
                            @endif
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem;">
                                <span style="font-size: 13px; font-weight: 600; color: #303030;">{{ $testimonial->name }}</span>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    @if($testimonial->is_active)
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
                                    @else
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ebebeb; color: #616161;">Hidden</span>
                                    @endif
                                </div>
                            </div>
                            @if($testimonial->title)
                                <p style="font-size: 12px; color: #616161; margin: 0;">{{ $testimonial->title }}</p>
                            @endif
                            <div style="display: flex; align-items: center; gap: 0.125rem; margin: 0.25rem 0;">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg style="width: 1rem; height: 1rem; color: {{ $i <= $testimonial->rating ? '#b98900' : '#e3e3e3' }};" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <p style="font-size: 13px; color: #303030; margin: 0.25rem 0 0 0;">"{{ $testimonial->content }}"</p>
                            @if($testimonial->product_name)
                                <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">Product: {{ $testimonial->product_name }}</p>
                            @endif
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.75rem;">
                                <form action="{{ route('admin.homepage.testimonials.toggle', $testimonial) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 0.25rem 0.5rem;">
                                        {{ $testimonial->is_active ? 'Hide' : 'Show' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.homepage.testimonials.destroy', $testimonial) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this testimonial?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="font-size: 12px; padding: 0.25rem 0.5rem; background: none; border: 1px solid #d72c0d; color: #d72c0d; border-radius: 0.375rem; cursor: pointer;">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card" style="padding: 3rem; text-align: center;">
                    <p style="font-size: 13px; color: #616161; margin: 0;">No testimonials yet. Add your first customer review.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.admin>
