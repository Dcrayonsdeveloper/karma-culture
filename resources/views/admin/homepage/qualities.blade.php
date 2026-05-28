<x-layouts.admin>
    <x-slot name="title">Our Qualities</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Our Qualities</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.homepage.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Homepage
        </a>
    </div>

    <p style="font-size: 12px; color: #616161; margin: 0 0 1rem 0;">
        The grid of quality blocks on the home page's dark "Our Qualities" section. Each row is a title + short description; reorder by editing position later.
    </p>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
        <!-- Add Quality -->
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Add Quality</h2>
            </div>
            <div style="padding: 1rem;">
                <form action="{{ route('admin.homepage.qualities.store') }}" method="POST">
                    @csrf
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Title <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="title" required class="form-input" placeholder="e.g. Premium Fabrics">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Description <span style="color: #d72c0d;">*</span></label>
                            <textarea name="description" rows="4" required class="form-textarea" placeholder="Short 1–2 sentence description that appears under the title."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="font-size: 13px; width: 100%;">Add Quality</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Existing Qualities -->
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Existing Qualities ({{ $qualities->count() }})</h2>
            </div>
            <div style="padding: 0;">
                @forelse($qualities as $quality)
                    <div style="padding: 1rem; border-bottom: 1px solid #e3e3e3;">
                        <form action="{{ route('admin.homepage.qualities.update', $quality) }}" method="POST">
                            @csrf @method('PUT')
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <div>
                                    <label class="form-label" style="font-size: 12px; color: #616161;">Title</label>
                                    <input type="text" name="title" value="{{ $quality->title }}" required class="form-input" style="font-size: 13px;">
                                </div>
                                <div>
                                    <label class="form-label" style="font-size: 12px; color: #616161;">Description</label>
                                    <textarea name="description" rows="3" required class="form-textarea" style="font-size: 13px;">{{ $quality->description }}</textarea>
                                </div>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <button type="submit" class="btn btn-sm btn-primary" style="font-size: 12px;">Save</button>
                            </div>
                        </form>
                                    <form action="{{ route('admin.homepage.qualities.toggle', $quality) }}" method="POST" style="display: inline;">
                                        @csrf @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 12px;">{{ $quality->is_active ? 'Hide' : 'Show' }}</button>
                                    </form>
                                    <form action="{{ route('admin.homepage.qualities.destroy', $quality) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this quality?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" style="font-size: 12px;">Delete</button>
                                    </form>
                                    <span class="badge {{ $quality->is_active ? 'badge-success' : 'badge-neutral' }}" style="margin-left: auto;">{{ $quality->is_active ? 'Active' : 'Hidden' }}</span>
                    </div>
                @empty
                    <div style="padding: 2rem; text-align: center; color: #616161; font-size: 13px;">No qualities yet. Add one on the left.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.admin>
