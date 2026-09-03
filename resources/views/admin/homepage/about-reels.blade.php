<x-layouts.admin>
    <x-slot name="title">About Us Reels</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>About Us Reels</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <x-admin.form-errors title="The reel was not saved" />

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.homepage.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Homepage
        </a>
    </div>

    {{-- The strip was three fixed slots until now, which is worth saying once
         here: the count is no longer part of the design. --}}
    <p style="font-size: 12px; color: #616161; margin: 0 0 1rem 0;">
        The clip strip in the "Crafted to Last" section on the home page. Add as many as you like, delete the ones you do not want,
        and set the order with the arrows. Clips play muted and on loop, so they are for showing cloth and cut &mdash; not for sound.
        MP4, WebM or MOV, up to 64MB each. Portrait clips suit the strip best; a landscape one is shown whole rather than cropped.
    </p>

    <div class="card" style="margin-bottom: 1.25rem;">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Reels</h2>
            <span style="font-size: 11px; color: #616161;">
                {{ $reels->count() }} total &middot; {{ $reels->where('is_active', true)->count() }} on the home page
            </span>
        </div>

        @forelse($reels as $reel)
            <div style="padding: 1rem; border-bottom: 1px solid #e3e3e3; display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-start;">
                {{-- The clip itself, not a filename: which reel this is, is a
                     question only the picture answers. --}}
                <video src="{{ $reel->url }}" controls muted preload="metadata"
                       controlsList="nodownload noplaybackrate noremoteplayback" disablepictureinpicture
                       style="flex: none; width: 150px; max-height: 200px; border-radius: 10px; background: #1a1a1a;"></video>

                <div style="flex: 1 1 260px; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span class="badge {{ $reel->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $reel->is_active ? 'On the home page' : 'Hidden' }}</span>
                        <span style="font-size: 11px; color: #616161;">Position {{ $loop->iteration }}</span>
                    </div>

                    <form action="{{ route('admin.homepage.about-reels.update', $reel) }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <label for="reel-video-{{ $reel->id }}" class="form-label" style="font-size: 11px; color: #616161;">Replace the clip</label>
                        <input type="file" name="video" id="reel-video-{{ $reel->id }}" required accept="video/mp4,video/webm,video/quicktime"
                               class="form-input" style="font-size: 13px;">
                        <p style="font-size: 11px; color: #616161; margin-top: 0.25rem;">
                            The reel keeps its place in the strip; the old file is deleted.
                        </p>

                        <button type="submit" class="btn btn-sm btn-primary" style="font-size: 11px; margin-top: 0.6rem;">Replace</button>
                    </form>
                </div>

                <div style="flex: none; display: flex; flex-direction: column; gap: 0.35rem; align-items: stretch;">
                    <form action="{{ route('admin.homepage.about-reels.toggle', $reel) }}" method="POST">
                        @csrf @method('PUT')
                        <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 11px; width: 100%;">{{ $reel->is_active ? 'Hide' : 'Show' }}</button>
                    </form>

                    <div style="display: flex; gap: 0.35rem;">
                        {{-- A missing arrow at either end is the honest control:
                             the top reel has nowhere further up to go. --}}
                        @if(! $loop->first)
                            <form action="{{ route('admin.homepage.about-reels.move', $reel) }}" method="POST" style="flex: 1;">
                                @csrf @method('PUT')
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 11px; width: 100%;" aria-label="Move up" title="Move up">&uarr;</button>
                            </form>
                        @endif
                        @if(! $loop->last)
                            <form action="{{ route('admin.homepage.about-reels.move', $reel) }}" method="POST" style="flex: 1;">
                                @csrf @method('PUT')
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 11px; width: 100%;" aria-label="Move down" title="Move down">&darr;</button>
                            </form>
                        @endif
                    </div>

                    {{-- Deleting takes the uploaded file with it, which is the
                         point: the clips are large and nothing else points at
                         them. Hide is the reversible one, so both are offered. --}}
                    <form action="{{ route('admin.homepage.about-reels.destroy', $reel) }}" method="POST"
                          onsubmit="return confirm('Delete this reel? The video file is deleted too.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" style="font-size: 11px; width: 100%;">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div style="padding: 1.5rem; text-align: center; color: #616161; font-size: 12px;">
                No reels yet. The "Crafted to Last" section renders its heading, text and button without the strip until you add one.
            </div>
        @endforelse

        {{-- Add --}}
        <div style="padding: 0.75rem 1rem; background: #fafafa;">
            <form action="{{ route('admin.homepage.about-reels.store') }}" method="POST" enctype="multipart/form-data"
                  style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.5rem; align-items: end;">
                @csrf
                <div>
                    <label for="new-reel-video" class="form-label" style="font-size: 11px; color: #616161;">Video *</label>
                    <input type="file" name="video" id="new-reel-video" required accept="video/mp4,video/webm,video/quicktime"
                           class="form-input" style="font-size: 13px;">
                    <p style="font-size: 11px; color: #616161; margin-top: 0.25rem;">MP4, WebM or MOV, up to 64MB. It joins the end of the strip.</p>
                </div>
                <button type="submit" class="btn btn-primary" style="font-size: 12px;">+ Add Reel</button>
            </form>
        </div>
    </div>
</x-layouts.admin>
