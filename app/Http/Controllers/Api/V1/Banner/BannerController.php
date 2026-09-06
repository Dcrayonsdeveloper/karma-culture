<?php

namespace App\Http\Controllers\Api\V1\Banner;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Rules\ValidationRules as V;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * The placements the storefront actually renders.
     *
     * The same five the admin's own banner form offers. They are repeated here
     * rather than shared because the admin holds them in a private constant on
     * a controller in another namespace; the list is a storefront fact, so if
     * it ever gains a sixth placement both copies have to learn it.
     */
    private const POSITIONS = ['hero', 'sidebar', 'footer', 'category', 'popup'];

    public function index(Request $request): JsonResponse
    {
        // An unrecognised ?position= is rejected rather than passed to the
        // query. Filtering on a typo would answer 200 with an empty list, which
        // a client cannot tell apart from "the shop has no footer banners" - so
        // a misspelt placement would look like a content problem for as long as
        // it took someone to read the source.
        $filters = $request->validate([
            'position' => V::option(self::POSITIONS, required: false),
        ]);

        // `visible` is the model's one definition of what a shopper should be
        // seeing right now - switched on, started, not yet ended. Reading the
        // scope rather than re-testing is_active here is what stops a campaign
        // scheduled for Friday being served to the app on Thursday while the
        // website correctly withholds it.
        //
        // `priority` is the sort order the admin set. `position` is a placement
        // and never a sort key.
        $banners = Banner::query()
            ->visible()
            ->when($filters['position'] ?? null, fn ($query, $position) => $query->position($position))
            ->orderBy('priority')
            ->get()
            ->filter(fn (Banner $banner) => $banner->has_media)
            ->map(fn (Banner $banner) => $this->payload($banner))
            // Without this the filtered-out rows leave gaps in the keys and the
            // list encodes as a JSON object, which every typed client rejects.
            ->values();

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }

    /**
     * One banner as the app should receive it.
     *
     * Every media key is resolved to something a browser or a native client can
     * actually fetch. The `/api/v1/home` payload historically handed back the
     * raw `image_url` disk key, which is a path on the public disk and not a
     * URL - no client outside the web app could load it.
     *
     * @return array<string, mixed>
     */
    private function payload(Banner $banner): array
    {
        return [
            'id' => $banner->id,
            'title' => $banner->title,
            'subtitle' => $banner->subtitle,
            'position' => $banner->position,
            'link' => $banner->link,
            'button_text' => $banner->button_text,
            // The resolved alt text, not the raw column: the model falls back to
            // the title for the banners created before there was a column for
            // it, so a client is never left with nothing to announce.
            'alt_text' => $banner->alt,
            // `priority` named for what it means. The column keeps its name.
            'display_order' => $banner->priority,

            // The four assets as URLs. The image accessors carry the model's
            // own placeholder fallback, exactly as the website sees them; the
            // video accessors answer '' for "this banner has no clip", which is
            // normalised to null so a client can test the key rather than
            // compare it against an empty string.
            'desktop_image' => $banner->image,
            'mobile_image' => $banner->mobile_image,
            'desktop_video' => $banner->video ?: null,
            'mobile_video' => $banner->mobile_video ?: null,

            // What each screen should actually draw, after the fallbacks - the
            // same decision the home page makes, made once in the model. A
            // native client that reimplemented the chain would drift from the
            // website the first time the order of preference changed; reading
            // these two keys, it cannot.
            'desktop' => $banner->frameFor('desktop'),
            'mobile' => $banner->frameFor('mobile'),
        ];
    }
}
