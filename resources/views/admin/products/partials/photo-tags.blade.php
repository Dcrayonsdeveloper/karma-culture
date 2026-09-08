{{-- Shades and fabrics for photos that have been PICKED but not yet uploaded.

     The sibling of the saved-photo card on the edit screen, and on the create
     screen the only one there is - a product being created has no image rows to
     tag, which is why this card had to be able to work off the file input alone.
     Before it existed an admin had to save the product, reopen it, and only then
     say which shade each photograph showed.

     Included INSIDE the media component's wrapper, so `mainPreview` and
     `galleryPreviews` resolve from the parent scope. Its own x-data holds only
     what it owns: the tag for the main image, and the shade/fabric lists it
     offers.

     The per-photo tags are stored ON the preview objects themselves rather than
     in a list of their own, and that is load-bearing: removeGalleryImage()
     splices galleryPreviews and galleryFileList together, so a tag kept beside
     them in a third array would slide onto the wrong photograph the moment the
     admin removed one from the middle. Riding on the object, a tag is removed
     with its picture and the posted index always matches the file's position in
     images[].
--}}
@php
    $kkOnCreate = $kkOnCreate ?? false;
    // What the shade and fabric lists start as. The live values arrive from the
    // Colours and Textures cards further down the form, which announce
    // themselves on init as well as on change - but those cards are BELOW this
    // one in the document, so on the very first paint this is all there is.
    $kkSeedColours = collect(old('colours', $kkOnCreate ? [] : data_get($product->attributes ?? null, 'Colours', [])))
        ->map(fn ($c) => trim((string) (is_array($c) ? ($c['name'] ?? '') : $c)))
        ->filter()->unique(fn (string $n) => mb_strtolower($n))->values();
    $kkSeedTextures = collect(old('textures', $kkOnCreate ? [] : data_get($product->attributes ?? null, 'Textures', [])))
        ->map(fn ($t) => trim((string) (is_array($t) ? ($t['name'] ?? '') : $t)))
        ->filter()->unique(fn (string $n) => mb_strtolower($n))->values();
@endphp

<div class="card p-5"
     x-data="kkNewPhotoTags(@js($kkSeedColours->all()), @js($kkSeedTextures->all()))"
     @kk-colours-changed.window="if ($event.detail) colours = $event.detail"
     @kk-textures-changed.window="if ($event.detail) textures = $event.detail"
     x-show="hasChoices && (mainPreview || galleryPreviews.length)"
     x-cloak>
    <h2 class="text-[13px] font-semibold mb-1" style="color: #303030;">
        {{ $kkOnCreate ? 'Photo shades &amp; fabrics' : 'Shades &amp; fabrics for the photos you just added' }}
    </h2>
    <p class="text-xs mb-4" style="color: #616161;">
        Say which shade or fabric each photo actually shows, and the product page will change
        its gallery when a customer picks one. A photo left on <strong>Any</strong> is a shared
        shot &mdash; the size chart, a fabric close-up, a styling picture &mdash; and stays on
        screen whatever is chosen. Saved with the rest of the form, so there is no need to save
        and come back.
    </p>

    <div class="space-y-2">
        {{-- The main image, when one has been picked. Its own row because it is
             posted as main_image and not as one of images[], so the server has
             no index to line it up with. --}}
        <template x-if="mainPreview">
            <div class="flex items-center gap-3 py-2 rounded-lg">
                <div class="kk-media kk-media--cover shrink-0 rounded overflow-hidden" style="width: 44px; height: 44px; border: 2px solid #005bd3;">
                    <img :src="mainPreview" alt="Main image">
                </div>
                <div class="flex-1 min-w-0 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <select name="new_image_tags[main][colour]" x-model="main.colour"
                            aria-label="Shade shown in the main photo"
                            class="form-input form-select w-full text-[13px]">
                        <option value="">Any shade</option>
                        <template x-for="name in colours" :key="'mc'+name">
                            <option :value="name" x-text="name"></option>
                        </template>
                    </select>
                    <select name="new_image_tags[main][texture]" x-model="main.texture"
                            aria-label="Fabric shown in the main photo"
                            class="form-input form-select w-full text-[13px]">
                        <option value="">Any fabric</option>
                        <template x-for="name in textures" :key="'mt'+name">
                            <option :value="name" x-text="name"></option>
                        </template>
                    </select>
                </div>
            </div>
        </template>

        {{-- One row per picked gallery file, in the order they will be uploaded. --}}
        <template x-for="(preview, index) in galleryPreviews" :key="preview.url">
            <div class="flex items-center gap-3 py-2 rounded-lg"
                 x-init="preview.colour = preview.colour || ''; preview.texture = preview.texture || ''">
                <div class="kk-media kk-media--cover shrink-0 rounded overflow-hidden" style="width: 44px; height: 44px; border: 1px solid #e3e3e3;">
                    <img :src="preview.url" :alt="preview.name">
                </div>
                <div class="flex-1 min-w-0 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <select :name="'new_image_tags[' + index + '][colour]'" x-model="preview.colour"
                            :aria-label="'Shade shown in ' + preview.name"
                            class="form-input form-select w-full text-[13px]">
                        <option value="">Any shade</option>
                        <template x-for="name in colours" :key="'c'+index+name">
                            <option :value="name" x-text="name"></option>
                        </template>
                    </select>
                    <select :name="'new_image_tags[' + index + '][texture]'" x-model="preview.texture"
                            :aria-label="'Fabric shown in ' + preview.name"
                            class="form-input form-select w-full text-[13px]">
                        <option value="">Any fabric</option>
                        <template x-for="name in textures" :key="'t'+index+name">
                            <option :value="name" x-text="name"></option>
                        </template>
                    </select>
                </div>
            </div>
        </template>
    </div>

    @error('new_image_tags') <p class="form-error mt-2">{{ $message }}</p> @enderror
    @foreach($errors->get('new_image_tags.*') as $kkNewTagMessages)
        <p class="form-error mt-2">{{ $kkNewTagMessages[0] }}</p>
    @endforeach
</div>

@once
@push('scripts')
<script>
    function kkNewPhotoTags(seedColours, seedTextures) {
        return {
            colours: seedColours || [],
            textures: seedTextures || [],
            // The main image is posted on its own, so its tag is held here rather
            // than on a preview object.
            main: { colour: '', texture: '' },
            // Nothing to say about a photo until the product offers a shade or a
            // fabric to say it with, so the card stays out of the way until then.
            get hasChoices() {
                return this.colours.length > 0 || this.textures.length > 0;
            },
        };
    }
</script>
@endpush
@endonce
