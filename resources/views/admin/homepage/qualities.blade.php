<x-layouts.admin>
    <x-slot name="title">Our Qualities</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Our Qualities</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <x-admin.form-errors title="The quality was not saved" />

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.homepage.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Homepage
        </a>
    </div>

    <p style="font-size: 12px; color: #616161; margin: 0 0 1rem 0;">
        The grid of quality blocks on the home page's dark "Our Qualities" section. Each row is a title + short description, plus an optional background image — cards with an image render as a tall 3:4 photo tile, cards without one stay compact. Use the arrows on each row to change the order they appear in.
    </p>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
        <!-- Add Quality -->
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Add Quality</h2>
            </div>
            <div style="padding: 1rem;">
                <form action="{{ route('admin.homepage.qualities.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        {{-- for/id pairs matter beyond accessibility here: the inline validator
                             names the field from its own <label>, so an unlabelled input reports
                             "This field is required" instead of "Title is required". --}}
                        <div>
                            <label for="quality-new-title" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Title <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="title" id="quality-new-title" required minlength="2" maxlength="255" class="form-input" placeholder="e.g. Premium Fabrics">
                        </div>
                        <div>
                            <label for="quality-new-description" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Description <span style="color: #d72c0d;">*</span></label>
                            <textarea name="description" id="quality-new-description" rows="4" required minlength="3" maxlength="500" class="form-textarea" placeholder="Short 1-2 sentence description that appears under the title."></textarea>
                        </div>
                        <div>
                            <label for="quality-new-image" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Background image</label>
                            <input type="file" name="image" id="quality-new-image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input" style="font-size: 12px; padding: 0.35rem;">
                            <p style="font-size: 11px; color: #616161; margin: 0.35rem 0 0 0;">Optional. Portrait crops work best (3:4). The text sits over a dark gradient at the bottom, so avoid busy detail there. Max 5 MB.</p>
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
                        {{-- The edit form closes before the Hide/Delete forms: forms cannot nest,
                             and the action buttons are pulled back into one row via form="". --}}
                        <form id="kk-quality-{{ $quality->id }}" action="{{ route('admin.homepage.qualities.update', $quality) }}" method="POST" enctype="multipart/form-data">
                            @csrf @method('PUT')
                            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                                <div style="flex: 0 0 88px;">
                                    <label for="quality-{{ $quality->id }}-image" class="form-label" style="font-size: 12px; color: #616161;">Image</label>
                                    <div style="width: 88px; height: 117px; border-radius: 6px; overflow: hidden; background: #f1f1f1; border: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                                        @if($quality->image_url)
                                            <img src="{{ $quality->image }}" alt="{{ $quality->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <span style="font-size: 10px; color: #8a8a8a; text-align: center; padding: 0 4px;">No image</span>
                                        @endif
                                    </div>
                                    <input type="file" name="image" id="quality-{{ $quality->id }}-image"
                                           accept="image/jpeg,image/png,image/webp,image/gif" style="font-size: 11px; margin-top: 0.4rem; width: 88px;">
                                    @if($quality->image_url)
                                        <label style="display: flex; align-items: center; gap: 0.25rem; font-size: 11px; color: #616161; margin-top: 0.35rem;">
                                            <input type="checkbox" name="remove_image" value="1"> Remove
                                        </label>
                                    @endif
                                </div>
                                <div style="flex: 1; display: flex; flex-direction: column; gap: 0.75rem;">
                                    <div>
                                        <label for="quality-{{ $quality->id }}-title" class="form-label" style="font-size: 12px; color: #616161;">Title</label>
                                        <input type="text" name="title" id="quality-{{ $quality->id }}-title" value="{{ $quality->title }}" required minlength="2" maxlength="255" class="form-input" style="font-size: 13px;">
                                    </div>
                                    <div>
                                        <label for="quality-{{ $quality->id }}-description" class="form-label" style="font-size: 12px; color: #616161;">Description</label>
                                        <textarea name="description" id="quality-{{ $quality->id }}-description" rows="3" required minlength="3" maxlength="500" class="form-textarea" style="font-size: 13px;">{{ $quality->description }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div style="display: flex; gap: 0.5rem; align-items: center; margin-top: 0.75rem;">
                            <button type="submit" form="kk-quality-{{ $quality->id }}" class="btn btn-sm btn-primary" style="font-size: 12px;">Save</button>
                            <form action="{{ route('admin.homepage.qualities.toggle', $quality) }}" method="POST" style="display: inline;">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 12px;">{{ $quality->is_active ? 'Hide' : 'Show' }}</button>
                            </form>
                            <form action="{{ route('admin.homepage.qualities.destroy', $quality) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this quality?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" style="font-size: 12px;">Delete</button>
                            </form>
                            {{-- position was stamped once at creation and nothing could
                                 change it afterwards, so the order on the home page was
                                 fixed by the order the rows happened to be added in. --}}
                            @if(! $loop->first)
                                <form action="{{ route('admin.homepage.qualities.move', $quality) }}" method="POST" style="display: inline;">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 12px;" aria-label="Move up" title="Move up">&uarr;</button>
                                </form>
                            @endif
                            @if(! $loop->last)
                                <form action="{{ route('admin.homepage.qualities.move', $quality) }}" method="POST" style="display: inline;">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 12px;" aria-label="Move down" title="Move down">&darr;</button>
                                </form>
                            @endif
                            <span class="badge {{ $quality->is_active ? 'badge-success' : 'badge-neutral' }}" style="margin-left: auto;">{{ $quality->is_active ? 'Active' : 'Hidden' }}</span>
                        </div>
                    </div>
                @empty
                    <div style="padding: 2rem; text-align: center; color: #616161; font-size: 13px;">No qualities yet. Add one on the left.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.admin>
