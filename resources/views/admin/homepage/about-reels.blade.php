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

    {{-- Worth stating up front, because it changes what everything below this
         screen is FOR: while Instagram is connected the strip is not a list
         anybody curates here. It is a random handful of the account's reels,
         drawn again on every visit. The upload list is the fallback. --}}
    <p style="font-size: 12px; color: #616161; margin: 0 0 1rem 0;">
        The clip strip in the "Crafted to Last" section on the home page. With Instagram connected it shows a random handful of the
        account's reels, reshuffled on every visit, so it keeps itself current without anybody uploading anything.
        The clips below are the fallback &mdash; what the strip shows before an account is connected, and what it drops back to if
        Instagram is unreachable. Clips play muted and on loop, so they are for showing cloth and cut &mdash; not for sound.
        MP4, WebM or MOV, up to 64MB each. Portrait clips suit the strip best; a landscape one is shown whole rather than cropped.
    </p>

    {{-- Instagram.

         Sitting above the list because it is the source most of the strip will
         come from once it is connected; the upload form below stays for clips
         that were never posted to Instagram. --}}
    @php $ig = $instagramState; @endphp
    <div class="card" style="margin-bottom: 1.25rem;">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Instagram</h2>
            @if($ig['configured'] && $ig['username'])
                <span class="badge badge-success">Connected to &#64;{{ $ig['username'] }}</span>
            @elseif($ig['configured'])
                <span class="badge badge-warning">Token saved, not verified</span>
            @else
                <span class="badge badge-neutral">Not connected</span>
            @endif
        </div>

        <div style="padding: 1rem;">
            @unless($ig['configured'])
                <p style="font-size: 12px; color: #616161; margin: 0 0 1rem 0;">
                    Instagram stopped letting anyone read an account's posts without permission, so the reels cannot be
                    fetched from a handle alone &mdash; it needs an access token from Instagram itself. The steps are in
                    <code style="font-size: 11px;">doc/instagram-reels.md</code>: switch the account to Professional, create a Meta app,
                    add the "Instagram API with Instagram Login" product, and generate a long-lived token. About ten minutes, once.
                    A token from Meta Business or a system user works too &mdash; paste it in the same box, and the linked
                    Instagram account is found through the Facebook Page it belongs to.
                </p>
            @endunless

            <form action="{{ route('admin.homepage.about-reels.instagram') }}" method="POST" style="display: grid; gap: 0.75rem; grid-template-columns: 1fr; max-width: 640px;">
                @csrf @method('PUT')

                <div>
                    <label for="ig-token" class="form-label" style="font-size: 12px;">Access token</label>
                    {{-- The saved token is never rendered back. It is a credential:
                         printing it into the page would put it in every browser
                         cache and screen share that ever opens this screen. --}}
                    <input type="password" name="access_token" id="ig-token" class="form-input" autocomplete="off"
                           placeholder="{{ $ig['configured'] ? 'Saved - leave blank to keep it' : 'Paste the long-lived Instagram token' }}">
                    <p style="font-size: 11px; color: #616161; margin-top: 0.25rem;">
                        @if($ig['token_expires_at'])
                            Expires {{ $ig['token_expires_at']->format('M d, Y') }}
                            ({{ $ig['token_expires_at']->isPast() ? 'already expired - paste a new one' : $ig['token_expires_at']->diffForHumans() }}).
                            An Instagram-Login token lasts 60 days and "Refresh token" below buys another 60, so it need never be reissued.
                        @else
                            An Instagram-Login token lasts 60 days and can be refreshed from here before it runs out.
                        @endif
                        A Meta Business or system-user token is reissued in Meta's own settings instead, not from here.
                    </p>
                </div>

                <div>
                    <label for="ig-limit" class="form-label" style="font-size: 12px;">How many reels to show</label>
                    <input type="number" name="reel_limit" id="ig-limit" min="1" max="20" value="{{ old('reel_limit', $ig['limit']) }}"
                           class="form-input" style="max-width: 8rem;">
                    <p style="font-size: 11px; color: #616161; margin-top: 0.25rem;">
                        How many tiles the strip holds. They are picked at random from the account's recent reels, so a visitor
                        rarely sees the same strip twice. Photos, carousels and stories are ignored &mdash; only reels.
                    </p>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary btn-sm" style="font-size: 12px;">Save &amp; connect</button>
                </div>
            </form>

            @if($ig['configured'])
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f1f1f1; display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                    <form action="{{ route('admin.homepage.about-reels.instagram.sync') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm" style="font-size: 12px;">Sync reels now</button>
                    </form>

                    <form action="{{ route('admin.homepage.about-reels.instagram.refresh') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm" style="font-size: 12px;">Refresh token</button>
                    </form>

                    <form action="{{ route('admin.homepage.about-reels.instagram.disconnect') }}" method="POST"
                          onsubmit="return confirm('Disconnect Instagram? The home page strip stops showing reels from the account and falls back to the clips uploaded below. The copies synced onto this server are deleted; clips you uploaded yourself are kept.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" style="font-size: 12px;">Disconnect</button>
                    </form>

                    <span style="font-size: 11px; color: #616161;">
                        Strip is live from Instagram &middot;
                        {{ $ig['synced_count'] }} reel(s) copied here as a fallback &middot;
                        {{ $ig['last_synced_at'] ? 'last synced '.$ig['last_synced_at']->diffForHumans() : 'never synced' }}
                    </span>
                </div>

                {{-- The old note here said new reels never appear on their own,
                     which was true while the strip was rows on this server and
                     nothing ran on a timer. The live strip is the opposite, and
                     leaving that note up would have admins pressing Sync for an
                     effect it no longer has. --}}
                <p style="font-size: 11px; color: #999; margin: 0.75rem 0 0 0;">
                    The strip picks up new reels on its own, within about twenty minutes of posting.
                    "Sync reels now" is a separate job: it copies the reels onto this server so the section still has something to
                    show if Instagram goes down. It also refreshes the live strip straight away, which is the quick way to see a
                    brand-new reel without waiting.
                </p>
            @endif
        </div>
    </div>

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
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                        <span class="badge {{ $reel->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $reel->is_active ? 'On the home page' : 'Hidden' }}</span>
                        @if($reel->isFromInstagram())
                            <span class="badge badge-info">From Instagram</span>
                        @endif
                        <span style="font-size: 11px; color: #616161;">Position {{ $loop->iteration }}</span>
                    </div>

                    @if($reel->isFromInstagram())
                        {{-- No replace button: this row is owned by the sync, and
                             the next one would put the Instagram clip straight
                             back over anything uploaded here. Hide it or delete
                             it instead, or change what is posted on Instagram. --}}
                        <p style="font-size: 12px; color: #616161; margin: 0;">
                            Pulled from Instagram{{ $reel->synced_at ? ' '.$reel->synced_at->diffForHumans() : '' }}.
                            @if($reel->permalink)
                                <a href="{{ $reel->permalink }}" target="_blank" rel="noopener noreferrer">View the reel on Instagram</a>.
                            @endif
                        </p>
                        <p style="font-size: 11px; color: #999; margin: 0.35rem 0 0 0;">
                            Deleting it here removes it until the next sync. To take it off for good, delete it on Instagram or hide it.
                        </p>
                    @else
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
                    @endif
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
