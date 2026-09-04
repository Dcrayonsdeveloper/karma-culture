<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\View\View;

/**
 * Shows a banner the way a shopper will actually get it, before it is published.
 *
 * Both banner editors take a desktop file and a phone file and show each one as
 * an untouched thumbnail, but the storefront draws neither at its own shape: it
 * crops both to the hero's two boxes and prints the caption over the top. A
 * model's face cropped out at 3:2, or a heading landing across the artwork, was
 * therefore only discoverable by publishing the banner and looking at the live
 * site on a phone. This screen is that look, taken before the publish.
 *
 * Read-only by design - it has no store or update sibling. Everything it shows
 * comes from the same accessors the storefront reads, so a preview cannot
 * flatter a banner that the home page will render differently.
 */
class BannerPreviewController extends Controller
{
    /**
     * Resolve both breakpoints here rather than in the template.
     *
     * frameFor() walks a fallback chain and asks the public disk whether a WebP
     * twin exists, so it is a query and not a free accessor. The view needs each
     * answer more than once - to decide whether there is anything to draw, to
     * draw it, and to compare the two devices - and reaching for the model each
     * time would repeat that disk work on every use.
     */
    public function __invoke(Banner $banner): View
    {
        return view('admin.banners.preview', [
            'banner' => $banner,
            'desktopFrame' => $banner->frameFor('desktop'),
            'mobileFrame' => $banner->frameFor('mobile'),
        ]);
    }
}
