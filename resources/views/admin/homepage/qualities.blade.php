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
        The grid of quality blocks on the home page's dark "Our Qualities" section. Each row is a title + short description, plus an optional background image — cards with an image render as a tall 4:5 photo tile, cards without one stay compact. Use the arrows on each row to change the order they appear in.
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
                        {{-- The picked file used to leave no trace: the native control
                             showed a clipped filename and nothing else, so there was no
                             way to tell an image had been chosen, let alone which one.
                             Same tile-as-picker pattern as the texture-preset form. --}}
                        <div x-data="{
                                preview: null,
                                fileName: '',
                                pick(e) {
                                    const file = e.target.files[0];
                                    if (!file) return;
                                    this.fileName = file.name;
                                    const reader = new FileReader();
                                    reader.onload = ev => this.preview = ev.target.result;
                                    reader.readAsDataURL(file);
                                },
                                clear() {
                                    this.preview = null;
                                    this.fileName = '';
                                    $refs.image.value = '';
                                }
                             }">
                            <span class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030;">Background image</span>
                            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                {{-- The tile doubles as the file picker, so there is one thing to click. --}}
                                <label style="flex: 0 0 auto; width: 96px; height: 120px; border: 1px dashed #b5b5b5; border-radius: 6px; overflow: hidden; cursor: pointer; display: flex; align-items: center; justify-content: center; background: #fafafa; position: relative;">
                                    <input type="file" name="image" id="quality-new-image" x-ref="image" aria-label="Background image"
                                           accept="image/jpeg,image/png,image/webp,image/gif"
                                           @change="pick($event)" style="position: absolute; inset: 0; opacity: 0; cursor: pointer;">
                                    <template x-if="preview">
                                        <img :src="preview" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    </template>
                                    <template x-if="!preview">
                                        <span style="font-size: 11px; color: #8a8a8a; text-align: center; padding: 0 0.35rem;">Add image</span>
                                    </template>
                                </label>
                                <div style="display: flex; flex-direction: column; gap: 0.4rem; min-width: 0; padding-top: 0.15rem;">
                                    <button type="button" @click="$refs.image.click()" class="btn btn-secondary" style="font-size: 12px; padding: 4px 10px;">
                                        <span x-text="preview ? 'Replace image' : 'Choose image'">Choose image</span>
                                    </button>
                                    {{-- The name is what tells the admin WHICH file they picked;
                                         the native control clipped it to nothing at this width. --}}
                                    <p x-show="fileName" x-cloak x-text="fileName"
                                       style="font-size: 11px; color: #303030; margin: 0; overflow-wrap: anywhere;"></p>
                                    <button type="button" x-show="preview" x-cloak @click="clear()"
                                            style="font-size: 12px; color: #b71c1c; background: none; border: 0; padding: 0; cursor: pointer; text-align: left;">
                                        Remove image
                                    </button>
                                </div>
                            </div>
                            {{-- The ratio here said 3:4, which is not the shape the card
                                 is. .kk-quality is aspect-ratio 4/5 and the photo fills
                                 it, so anyone who followed the old advice had the top and
                                 bottom quietly cropped off. 1200x1500 is 4:5 at twice the
                                 widest the card is ever drawn (582px on a phone), so it
                                 stays sharp on a retina screen without being wasteful. --}}
                            <p style="font-size: 11px; color: #616161; margin: 0.35rem 0 0 0;">
                                Optional. JPG, PNG or WebP.
                                <strong style="color: #303030; font-weight: 600;">Best at 1200 &times; 1500px (4:5 portrait)</strong>
                                &mdash; the shape the card is cropped to. The text sits over a dark gradient at the
                                bottom, so avoid busy detail there. Max 5 MB.
                            </p>
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
                            <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-start;">
                                {{-- `saved` is what the row started with, so Remove can put the
                                     old picture back if it is clicked by mistake and the row has
                                     not been saved yet. The tile is 4:5, the shape the card
                                     crops to, so the preview is not lying about the framing. --}}
                                <div style="flex: 0 0 88px;"
                                     x-data="{
                                        saved: @js($quality->image ?: null),
                                        preview: @js($quality->image ?: null),
                                        fileName: '',
                                        remove: false,
                                        pick(e) {
                                            const file = e.target.files[0];
                                            if (!file) return;
                                            this.remove = false;
                                            this.fileName = file.name;
                                            const reader = new FileReader();
                                            reader.onload = ev => this.preview = ev.target.result;
                                            reader.readAsDataURL(file);
                                        },
                                        clear() {
                                            this.preview = null;
                                            this.fileName = '';
                                            this.remove = !!this.saved;
                                            $refs.image.value = '';
                                        },
                                        undo() {
                                            this.preview = this.saved;
                                            this.remove = false;
                                        }
                                     }">
                                    <span class="form-label" style="display: block; font-size: 12px; color: #616161;">Image</span>
                                    <label style="width: 88px; height: 110px; border-radius: 6px; overflow: hidden; background: #f1f1f1; border: 1px solid #e3e3e3; cursor: pointer; display: flex; align-items: center; justify-content: center; position: relative;">
                                        <input type="file" name="image" id="quality-{{ $quality->id }}-image" x-ref="image"
                                               aria-label="Image for {{ $quality->title }}"
                                               accept="image/jpeg,image/png,image/webp,image/gif"
                                               @change="pick($event)" style="position: absolute; inset: 0; opacity: 0; cursor: pointer;">
                                        <template x-if="preview">
                                            <img :src="preview" alt="{{ $quality->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        </template>
                                        <template x-if="!preview">
                                            <span style="font-size: 10px; color: #8a8a8a; text-align: center; padding: 0 4px;">No image</span>
                                        </template>
                                    </label>
                                    <button type="button" @click="$refs.image.click()" class="btn btn-secondary pointer-coarse:min-h-9"
                                            style="font-size: 11px; padding: 3px 8px; margin-top: 0.4rem; width: 88px;">
                                        <span x-text="preview ? 'Replace' : 'Choose'">Choose</span>
                                    </button>
                                    {{-- Which file was picked, and that it is not on the server
                                         yet. Without this the admin had no way to tell a chosen
                                         image from an already-saved one. --}}
                                    <p x-show="fileName" x-cloak style="font-size: 10px; color: #303030; margin: 0.3rem 0 0 0; line-height: 1.3; width: 88px; overflow-wrap: anywhere;">
                                        <span x-text="fileName"></span><br>
                                        <span style="color: #8a6d00;">Not saved yet &mdash; press Save.</span>
                                    </p>
                                    <p x-show="remove" x-cloak style="font-size: 10px; color: #8a6d00; margin: 0.3rem 0 0 0; line-height: 1.3; width: 88px;">
                                        Removed on Save. <button type="button" @click="undo()" style="font-size: 10px; color: #005bd3; background: none; border: 0; padding: 0; cursor: pointer; text-decoration: underline;">Undo</button>
                                    </p>
                                    {{-- Same guidance as the add form. Someone replacing a
                                         picture needs the size as much as someone adding
                                         one, and this column is where they are looking. --}}
                                    <p style="font-size: 10px; color: #8a8a8a; margin: 0.3rem 0 0 0; line-height: 1.3;">
                                        1200 &times; 1500px<br>(4:5 portrait)
                                    </p>
                                    <button type="button" x-show="preview" x-cloak @click="clear()"
                                            style="font-size: 11px; color: #b71c1c; background: none; border: 0; padding: 0; margin-top: 0.35rem; cursor: pointer; text-align: left;">
                                        Remove image
                                    </button>
                                    {{-- Was a checkbox rendered only when a picture existed; the
                                         hidden field always posts so the controller's
                                         $request->boolean('remove_image') reads a real value. --}}
                                    <input type="hidden" name="remove_image" :value="remove ? 1 : 0" value="0">
                                </div>
                                <div style="flex: 1 1 200px; display: flex; flex-direction: column; gap: 0.75rem;">
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
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; margin-top: 0.75rem;">
                            <button type="submit" form="kk-quality-{{ $quality->id }}" class="btn btn-sm btn-primary pointer-coarse:min-h-9" style="font-size: 12px;">Save</button>
                            <form action="{{ route('admin.homepage.qualities.toggle', $quality) }}" method="POST" style="display: inline;">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-sm btn-secondary pointer-coarse:min-h-9" style="font-size: 12px;">{{ $quality->is_active ? 'Hide' : 'Show' }}</button>
                            </form>
                            <form action="{{ route('admin.homepage.qualities.destroy', $quality) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this quality?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger pointer-coarse:min-h-9" style="font-size: 12px;">Delete</button>
                            </form>
                            {{-- position was stamped once at creation and nothing could
                                 change it afterwards, so the order on the home page was
                                 fixed by the order the rows happened to be added in. --}}
                            @if(! $loop->first)
                                <form action="{{ route('admin.homepage.qualities.move', $quality) }}" method="POST" style="display: inline;">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" class="btn btn-sm btn-secondary pointer-coarse:min-h-9 pointer-coarse:min-w-9" style="font-size: 12px;" aria-label="Move up" title="Move up">&uarr;</button>
                                </form>
                            @endif
                            @if(! $loop->last)
                                <form action="{{ route('admin.homepage.qualities.move', $quality) }}" method="POST" style="display: inline;">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" class="btn btn-sm btn-secondary pointer-coarse:min-h-9 pointer-coarse:min-w-9" style="font-size: 12px;" aria-label="Move down" title="Move down">&darr;</button>
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
