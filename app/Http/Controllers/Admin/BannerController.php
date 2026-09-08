<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\FestivalSale;
use App\Rules\NoHtml;
use App\Rules\ValidationRules as V;
use App\Support\BannerMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Marketing > Banners: the full banner CRUD for every placement.
 *
 * Homepage > Hero Banners edits the same table through a different screen, so
 * everything the two share lives somewhere they both reach - the upload paths
 * in {@see BannerMedia}, the visibility rules and the link pattern on
 * {@see Banner}. This screen used to file mobile images into the desktop
 * directory, keep every file it replaced, and never flush the cached home
 * payload, which is how a banner edited here could stay visibly wrong on the
 * storefront for fifteen minutes.
 *
 * There is no admin API in this project: these routes are the authenticated
 * admin surface. So every write here answers JSON to a client that asks for it
 * and a redirect to a browser, off one set of rules, rather than growing a
 * second controller that could validate differently.
 */
class BannerController extends Controller
{
    /** The placements the storefront actually renders. */
    private const POSITIONS = ['hero', 'sidebar', 'footer', 'category', 'popup'];

    /**
     * How many rows a page holds by default.
     *
     * Deliberately generous. Reordering rewrites a whole placement at once (see
     * {@see reorder()}), so a placement split across two pages cannot be
     * dragged - and ten rows a page split them constantly.
     */
    private const PER_PAGE = 25;

    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'position' => ['nullable', 'string', Rule::in(self::POSITIONS)],
            'trashed' => ['nullable', 'boolean'],
        ]);

        $trashed = $request->boolean('trashed');

        $banners = Banner::query()
            // Soft deletes made an undo possible, so the deleted rows need
            // somewhere to be seen; without this filter they are invisible and
            // restore() has no caller.
            ->when($trashed, fn ($query) => $query->onlyTrashed())
            ->when($filters['position'] ?? null, fn ($query, $position) => $query->where('position', $position))
            // Grouped by placement, then by the sort order within it: the list
            // is read one placement at a time, and priority only ever means
            // anything against the other banners in the same spot.
            ->orderBy('position')
            ->orderBy('priority')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? self::PER_PAGE)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'data' => collect($banners->items())->map(fn (Banner $banner) => $this->payload($banner))->all(),
                'meta' => [
                    'current_page' => $banners->currentPage(),
                    'last_page' => $banners->lastPage(),
                    'per_page' => $banners->perPage(),
                    'total' => $banners->total(),
                    'trashed' => $trashed,
                ],
            ]);
        }

        // How many banners each placement holds in total, so the view can tell
        // a placement it is showing whole from one cut in half by pagination.
        // Dragging half a placement would renumber it against rows the page
        // cannot see, so the handles are only offered on a complete one.
        $positionTotals = Banner::query()
            ->when($trashed, fn ($query) => $query->onlyTrashed())
            ->selectRaw('position, COUNT(*) as aggregate')
            ->groupBy('position')
            ->pluck('aggregate', 'position');

        $festivalBanners = $this->festivalBanners($trashed, $filters['position'] ?? null, $banners->currentPage());

        // Which of them the home page is actually leading with. More than one
        // sale may be switched on, but only the most recently updated one wins
        // the hero, so the rest are reachable from their sale page alone and
        // the list has to be able to say which is which.
        $festivalHeroId = $festivalBanners->isEmpty() ? null : (FestivalSale::liveSummary()['id'] ?? null);

        return view('admin.banners.index', compact('banners', 'trashed', 'positionTotals', 'festivalBanners', 'festivalHeroId'));
    }

    /**
     * The artwork belonging to the festival sales that are running right now.
     *
     * A festival sale carries its banner in its own two columns rather than as
     * a banners row, and the home page draws it as slide 0 of the very hero
     * carousel this table fills. So an admin who uploaded one saw it on the
     * shop and not on the screen whose whole job is to list the banners the
     * shop is showing, and the only way back to it was remembering which sale
     * owned it.
     *
     * Listed, not copied. Mirroring each one into a banners row would put a
     * second copy of the same picture in the hero behind the first, and leave
     * two records of one image free to drift apart - so these rows are
     * read-only and send an admin to the sale that owns them.
     *
     * Three things suppress them, all for the same reason: they would be
     * answering a question the page is not asking. The bin holds deleted
     * banners and a sale cannot be in it; filtering to any placement but the
     * hero is asking about somewhere these do not appear; and page two of a
     * paginated list would otherwise repeat them under every page.
     *
     * @return Collection<int, FestivalSale>
     */
    private function festivalBanners(bool $trashed, ?string $position, int $page): Collection
    {
        if ($trashed || ($position !== null && $position !== 'hero') || $page > 1) {
            return collect();
        }

        return FestivalSale::query()
            ->active()
            // Either column counts. A sale given only phone artwork still shows
            // it to every phone, and leaving it off this list would hide the
            // one banner a mobile-first shop is most likely to have uploaded.
            ->where(fn ($query) => $query->whereNotNull('banner_path')->orWhereNotNull('banner_mobile_path'))
            ->latest('updated_at')
            ->get();
    }

    public function create(): View
    {
        return view('admin.banners.create');
    }

    /**
     * The rule set shared by store() and update().
     *
     * `$mediaRequired` is the create form. The image is NOT hard-required even
     * then: a banner carried entirely by a video is legitimate and the hero
     * screen has always allowed one, so the requirement is on the pair. The
     * message names that, because "The image field is required" is a lie when a
     * video would have done.
     *
     * The image stays optional on update: an empty file input means "keep the
     * picture already on disk", which is the only way to edit a caption without
     * re-uploading the artwork.
     *
     * `link` is checked against {@see Banner::LINK_REGEX} rather than V::url().
     * V::url() demands a full http(s) address, so `/products` - the shape most
     * banners actually use - was refused here while the hero screen accepted
     * it. The regex allows a site-relative path, mailto:, tel: and a bare
     * fragment, and refuses `javascript:` and the protocol-relative `//evil.com`
     * that a plain path check reads as local.
     *
     * @return array<string, mixed>
     */
    private function rules(bool $mediaRequired, ?Banner $banner = null): array
    {
        return [
            'name' => V::text(max: 255, min: 2),
            'position' => V::option(self::POSITIONS),
            'image' => $mediaRequired
                ? ['required_without:video', ...V::image(required: false, maxKb: BannerMedia::MAX_IMAGE_KB, allowGif: true, maxWidth: BannerMedia::MAX_IMAGE_EDGE, maxHeight: BannerMedia::MAX_IMAGE_EDGE)]
                : V::image(required: false, maxKb: BannerMedia::MAX_IMAGE_KB, allowGif: true, maxWidth: BannerMedia::MAX_IMAGE_EDGE, maxHeight: BannerMedia::MAX_IMAGE_EDGE),
            'video' => V::video(maxKb: BannerMedia::MAX_VIDEO_KB),
            // The mobile pair is an override, never a requirement: a banner
            // without one shows its desktop media on phones, so nothing here is
            // conditional on the desktop fields being filled.
            'mobile_image' => V::image(required: false, maxKb: BannerMedia::MAX_IMAGE_KB, allowGif: true, maxWidth: BannerMedia::MAX_IMAGE_EDGE, maxHeight: BannerMedia::MAX_IMAGE_EDGE),
            'mobile_video' => V::video(maxKb: BannerMedia::MAX_VIDEO_KB),
            'title' => V::text(required: false, max: 255),
            'subtitle' => V::text(required: false, max: 500),
            'button_text' => V::text(required: false, max: 100),
            'link' => ['nullable', 'string', 'max:255', 'regex:'.Banner::LINK_REGEX],
            // Read to a screen reader in place of the artwork, so markup in it
            // would be read aloud as text at best and smuggled into an href at
            // worst.
            'alt_text' => ['nullable', 'string', 'max:255', new NoHtml],
            'overlay_style' => V::option(array_keys(Banner::OVERLAY_STYLES), required: false),
            // priority is an unsignedSmallInteger.
            'priority' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => V::boolean(),
            // The same schedule contract the coupon and flash sale forms use,
            // down to the datetime-local 'Y-m-d\TH:i' the inputs submit: the end
            // must be later than the start, neither may be moved into the past,
            // and a date the row already holds stays acceptable so a running
            // campaign can still have its caption edited.
            'starts_at' => V::scheduleStart(required: false, current: $banner?->starts_at),
            'ends_at' => V::scheduleEnd('starts_at', required: false, current: $banner?->ends_at),
            'remove_image' => V::boolean(),
            'remove_video' => V::boolean(),
            'remove_mobile_image' => V::boolean(),
            'remove_mobile_video' => V::boolean(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'image.required_without' => 'Add a desktop image or a desktop video - a banner with no artwork cannot be shown.',
            'link.regex' => 'Enter a path such as /products, or a full https:// address.',
        ] + V::imageMessages('image', BannerMedia::MAX_IMAGE_KB)
          + V::imageMessages('mobile_image', BannerMedia::MAX_IMAGE_KB)
          + V::videoMessages('video', BannerMedia::MAX_VIDEO_KB)
          + V::videoMessages('mobile_video', BannerMedia::MAX_VIDEO_KB);
    }

    /**
     * The column values a request carries, excluding the four uploads.
     *
     * Every field is guarded by has() rather than read straight off the
     * request. The browser forms always post all of them, but a JSON client
     * sending only the field it wants changed must not have the rest reset to
     * their empty defaults: an absent is_active would switch the banner off and
     * an absent starts_at would erase a campaign's start date.
     *
     * @return array<string, mixed>
     */
    private function attributes(Request $request): array
    {
        $data = $request->only(['name', 'position', 'title', 'subtitle', 'button_text', 'link', 'overlay_style']);

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        foreach (['starts_at', 'ends_at'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field) ?: null;
            }
        }

        if ($request->has('priority') && $request->filled('priority')) {
            $data['priority'] = (int) $request->input('priority');
        }

        // A blank box means "this banner has no alt text of its own", not
        // "this artwork is decorative". Stored as '' the model reads it as a
        // deliberate empty alt and the storefront hides the image from screen
        // readers; null is what makes it fall back to the title, which is the
        // description every banner had before the column existed.
        if ($request->has('alt_text')) {
            $data['alt_text'] = $request->filled('alt_text') ? trim($request->input('alt_text')) : null;
        }

        return $data;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate($this->rules(mediaRequired: true), $this->messages());

        $data = $this->attributes($request) + [
            'is_active' => true,
            'overlay_style' => 'left-dark',
            // The end of its own placement, not the front. Defaulting to 0 put
            // every new banner ahead of every existing one and left a table
            // full of ties for the sort to break arbitrarily. Deleted rows are
            // counted too: one of them restored later would otherwise land on
            // a number that has since been handed out again.
            'priority' => (int) Banner::withTrashed()->where('position', $request->input('position'))->max('priority') + 1,
        ];

        foreach (BannerMedia::FIELDS as $field => $column) {
            if ($request->hasFile($field)) {
                $data[$column] = BannerMedia::store($request->file($field), $column);
            }
        }

        $banner = Banner::create($data);

        Cache::flush();

        return $this->respond($request, $banner, 'Banner created successfully', 201);
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner): RedirectResponse|JsonResponse
    {
        $request->validate($this->rules(mediaRequired: false, banner: $banner), $this->messages());

        // Either half of the desktop pair may be removed - a banner led by a
        // video and one led by a still are the same row with the other file
        // taken off it - but not both, because a row with no artwork is drawn
        // on the storefront as a placeholder box.
        //
        // Only a removal that would actually take something away is checked. A
        // banner carrying nothing but phone artwork has neither of these
        // columns filled already, and a stricter test would refuse to let its
        // caption be edited.
        $removingImage = $request->boolean('remove_image') && $banner->image_url;
        $removingVideo = $request->boolean('remove_video') && $banner->video_url;

        // An upload in the same request replaces what the tick removes, so it
        // counts as keeping that side.
        $keepsImage = $request->hasFile('image') || ($banner->image_url && ! $request->boolean('remove_image'));
        $keepsVideo = $request->hasFile('video') || ($banner->video_url && ! $request->boolean('remove_video'));

        if (($removingImage || $removingVideo) && ! $keepsImage && ! $keepsVideo) {
            [$field, $error] = match (true) {
                $removingImage && $removingVideo => ['remove_image', 'A banner has to keep an image or a video. Untick one of the two, or upload a replacement for it.'],
                $removingImage => ['remove_image', 'Upload a video first - removing the image would leave this banner with nothing to show.'],
                default => ['remove_video', 'Upload an image first - removing the video would leave this banner with nothing to show.'],
            };

            if ($request->wantsJson()) {
                return response()->json(['message' => $error, 'errors' => [$field => [$error]]], 422);
            }

            return back()->withInput()->withErrors([$field => $error]);
        }

        $data = $this->attributes($request);

        foreach (BannerMedia::FIELDS as $field => $column) {
            if ($request->hasFile($field)) {
                // replace(), not store(): the file being superseded goes with
                // it. Uploading a new picture used to leave the old one on the
                // disk forever, so a banner edited weekly kept a year of them.
                $data[$column] = BannerMedia::replace($request->file($field), $column, $banner->{$column});
            } elseif ($request->boolean("remove_{$field}") && $banner->{$column}) {
                // All four columns can be emptied from here now. The desktop
                // still used to be the exception - it is the last link in both
                // fallback chains, so the only way to be rid of one was to
                // upload another over it, which left a video-led banner stuck
                // with a still it no longer wanted. The guard above is what
                // makes removing it safe: it refuses the tick that would empty
                // the pair.
                BannerMedia::delete($banner->{$column});
                $data[$column] = null;
            }
        }

        $banner->update($data);

        Cache::flush();

        return $this->respond($request, $banner->refresh(), 'Banner updated successfully');
    }

    /**
     * Soft-delete a banner and take its uploaded files with it.
     *
     * Deleting used to leave all four files on the public disk forever. They go
     * now, while the row stays: the copy, the schedule and the placement are
     * what an undo is actually for, and keeping the media of every banner the
     * shop has ever deleted is what filled the disk.
     *
     * The columns are left pointing at the paths on purpose. Not every one of
     * them was deleted - {@see BannerMedia::delete()} leaves absolute URLs and
     * web-root paths alone, because they are not this disk's to remove - so
     * blanking the columns would throw away a still-valid reference to a file
     * that is still there.
     */
    public function destroy(Request $request, Banner $banner): RedirectResponse|JsonResponse
    {
        BannerMedia::deleteAll([
            $banner->image_url,
            $banner->video_url,
            $banner->mobile_image_url,
            $banner->mobile_video_url,
        ]);

        $banner->delete();

        Cache::flush();

        return $this->respond($request, $banner, 'Banner deleted successfully');
    }

    /** Bring a soft-deleted banner back. Its uploads are gone; its copy is not. */
    public function restore(Request $request, Banner $banner): RedirectResponse|JsonResponse
    {
        $banner->restore();

        Cache::flush();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Banner restored', 'data' => $this->payload($banner)]);
        }

        return back()->with('success', 'Banner restored - upload its artwork again before switching it on.');
    }

    /**
     * The Active switch on its own.
     *
     * Routed separately rather than through update(): that form posts the whole
     * record, so flipping the switch through it would blank every field this
     * row-level button does not carry - the schedule above all.
     */
    public function toggle(Request $request, Banner $banner): RedirectResponse|JsonResponse
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        Cache::flush();

        if ($request->wantsJson()) {
            return response()->json(['data' => $this->payload($banner)]);
        }

        return back()->with('success', 'Banner status updated');
    }

    /**
     * Rewrite the display order of one placement.
     *
     * Scoped to a single position throughout. The rule used to accept any id in
     * the table, so this screen could renumber the hero rows that Homepage >
     * Hero Banners owns and orders by the same column.
     *
     * The whole placement has to arrive at once. A partial list - one page of a
     * placement split by pagination - would number its rows 0..n while the rows
     * it could not see kept theirs, putting the two halves on top of each other
     * in an order nobody chose.
     */
    public function reorder(Request $request): RedirectResponse|JsonResponse
    {
        $position = $request->input('position');

        $validated = $request->validate([
            'position' => V::option(self::POSITIONS),
            'order' => ['required', 'array', 'max:500'],
            'order.*' => ['integer', Rule::exists('banners', 'id')->where('position', $position)->whereNull('deleted_at')],
        ]);

        $ids = array_values(array_unique($validated['order']));
        $total = Banner::where('position', $validated['position'])->count();

        if (count($ids) !== $total) {
            $error = 'The whole placement has to be reordered at once. Reload the list and try again.';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }

            return back()->withErrors(['order' => $error]);
        }

        foreach ($ids as $index => $id) {
            Banner::where('id', $id)->where('position', $validated['position'])->update(['priority' => $index]);
        }

        Cache::flush();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Banners reordered');
    }

    /** One answer, two shapes: JSON for a client, a redirect for a browser. */
    private function respond(Request $request, Banner $banner, string $message, int $status = 200): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'data' => $this->payload($banner)], $status);
        }

        return redirect()->route('admin.banners.index')->with('success', $message);
    }

    /**
     * One banner as JSON.
     *
     * `frames` carries the result of the same fallback chain the storefront
     * runs, so a client does not have to reimplement "which file does a phone
     * actually get" and then disagree with the website about the answer.
     *
     * @return array<string, mixed>
     */
    private function payload(Banner $banner): array
    {
        return [
            'id' => $banner->id,
            'name' => $banner->name,
            'title' => $banner->title,
            'subtitle' => $banner->subtitle,
            'button_text' => $banner->button_text,
            'position' => $banner->position,
            'priority' => $banner->priority,
            'link' => $banner->link,
            'alt' => $banner->alt,
            'alt_text' => $banner->alt_text,
            'overlay_style' => $banner->overlay_style,
            'is_active' => (bool) $banner->is_active,
            'state' => $banner->state,
            'state_label' => $banner->state_label,
            'starts_at' => $banner->starts_at?->toIso8601String(),
            'ends_at' => $banner->ends_at?->toIso8601String(),
            'media' => [
                'image' => $banner->image_url ? $banner->image : null,
                'mobile_image' => $banner->mobile_image_url ? $banner->mobile_image : null,
                'video' => $banner->video_url ? $banner->video : null,
                'mobile_video' => $banner->mobile_video_url ? $banner->mobile_video : null,
            ],
            'frames' => [
                'desktop' => $banner->frameFor('desktop'),
                'mobile' => $banner->frameFor('mobile'),
            ],
            'created_at' => $banner->created_at?->toIso8601String(),
            'updated_at' => $banner->updated_at?->toIso8601String(),
            'deleted_at' => $banner->deleted_at?->toIso8601String(),
        ];
    }
}
