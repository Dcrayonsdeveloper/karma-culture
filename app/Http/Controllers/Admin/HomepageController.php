<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AboutReel;
use App\Models\Banner;
use App\Models\HomepageSection;
use App\Models\NavigationMenu;
use App\Models\Quality;
use App\Models\Setting;
use App\Models\ShopFilterExclusion;
use App\Rules\ValidationRules as V;
use App\Support\BannerMedia;
use App\Support\ShopFilterCatalogue;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class HomepageController extends Controller
{
    /**
     * Upload ceiling for videos on this screen, in kilobytes.
     *
     * This was its own 64 MB constant, justified by "the existing hero clips
     * are around 15 MB, so 64 MB leaves comfortable headroom" - reasoning from
     * the bloat rather than from what a web page can afford, and the About Us
     * reels went through this same rule: seven of them, averaging 19 MB.
     *
     * Deferred to BannerMedia so there is one number rather than two that can
     * drift, which is the fault the videoRules() docblock below already
     * describes having fixed once.
     */
    private const MAX_VIDEO_KB = \App\Support\BannerMedia::MAX_VIDEO_KB;

    /**
     * A link an admin may point a banner, button or menu item at.
     *
     * Every one of these values ends up in an `href` on the storefront. Blade
     * escapes the text, but escaping does not disarm a scheme: `javascript:...`
     * in a stored link is a click away from running as the visitor. So the
     * scheme is allow-listed instead - http, https, mailto and tel - alongside
     * the two shapes that carry no scheme at all: a site-relative path and a
     * bare fragment.
     *
     * The path branch refuses a second leading slash. `//evil.com` looks like a
     * path and passed as one, but a browser reads it as a protocol-relative URL
     * and resolves it off-site, so a banner or menu item labelled with a local
     * path could quietly send visitors to someone else's domain.
     */
    private const LINK_REGEX = '/^(?:(?:https?|mailto|tel):\S+|\/(?!\/)\S*|#\S*)$/i';

    /**
     * A video the About Us section can play: an absolute URL, or a path
     * relative to the web root such as `storage/storefront/about/reel.mp4`.
     */
    private const VIDEO_SRC_REGEX = '/^(?:https?:\/\/\S+|[A-Za-z0-9._\-\/]+\.(?:mp4|webm|mov|ogg))$/i';

    /** A six-digit CSS hex colour - what <input type="color"> submits. */
    private const HEX_COLOR_REGEX = '/^#[0-9A-Fa-f]{6}$/';

    /**
     * Locations the header and footer partials know how to render.
     *
     * footer_col4 was accepted here but the footer has three link columns and the
     * admin screen lists three, so an item filed there was saved, hidden from the
     * only page that could delete it, and rendered nowhere.
     */
    private const NAV_LOCATIONS = ['header', 'footer_col1', 'footer_col2', 'footer_col3'];

    /**
     * Section rows the homepage no longer has any markup for.
     *
     * The testimonials block was taken off the home page, so its
     * homepage_sections row now titles nothing a visitor can see. The row stays
     * in the table - whatever heading an admin typed for it is still there if
     * the block ever comes back - but it is kept off the screens that offer a
     * section as editable. A heading and a Visible switch that change nothing
     * read as broken, which is worse than not being offered at all.
     */
    private const RETIRED_SECTION_KEYS = ['testimonials'];

    /**
     * Video uploads: extension, sniffed type and size all checked.
     *
     * The rules themselves live in {@see V::video()} now, because the same five
     * were written out by hand on the banner, category and product forms and had
     * already drifted apart on the size cap. Kept as a method only because the
     * About Us reel endpoints below prepend `required` to it.
     */
    private function videoRules(): array
    {
        return V::video(required: false, maxKb: self::MAX_VIDEO_KB);
    }

    private function videoMessages(string $field): array
    {
        return V::videoMessages($field, self::MAX_VIDEO_KB);
    }

    public function index()
    {
        $sections = HomepageSection::whereNotIn('key', self::RETIRED_SECTION_KEYS)->ordered()->get();
        $banners = Banner::where('position', 'hero')->ordered()->get();

        return view('admin.homepage.index', compact('sections', 'banners'));
    }

    // Site Settings (Logo, Brand Name, etc.)
    public function siteSettings()
    {
        $settings = [
            'site_logo' => Setting::get('site_logo', ''),
            'site_favicon' => Setting::get('site_favicon', ''),
            'site_name' => Setting::get('site_name', 'Karmaa Kulture'),
            'site_tagline' => Setting::get('site_tagline', 'Unlock Your Natural Beauty'),
            'site_description' => Setting::get('site_description', ''),
            'footer_about' => Setting::get('footer_about', ''),
            'footer_copyright' => Setting::get('footer_copyright', ''),
            'social_facebook' => Setting::get('social_facebook', ''),
            'social_instagram' => Setting::get('social_instagram', ''),
            'social_twitter' => Setting::get('social_twitter', ''),
            'social_linkedin' => Setting::get('social_linkedin', ''),
            'social_youtube' => Setting::get('social_youtube', ''),
            'social_tiktok' => Setting::get('social_tiktok', ''),
            'social_pinterest' => Setting::get('social_pinterest', ''),
            'contact_email' => Setting::get('contact_email', ''),
            'contact_phone' => Setting::get('contact_phone', ''),
            'whatsapp_number' => Setting::get('whatsapp_number', ''),
            'contact_address' => Setting::get('contact_address', ''),
            'announcement_text' => Setting::get('announcement_text', ''),
        ];

        return view('admin.homepage.site-settings', compact('settings'));
    }

    public function updateSiteSettings(Request $request)
    {
        // This form had no validation whatsoever, which mattered most for the
        // four file inputs: the logo and the three About Us clips were stored
        // straight onto the public disk on trust, so any file at all - a .php
        // included - could be uploaded and then fetched back through
        // /storage. Every text field was likewise unbounded and unchecked.
        $rules = [
            // Required, unlike every other field here. The same setting is edited
            // on Settings > General where it is mandatory, and the storefront's
            // footer, page titles and schema all read it - so saving this form
            // with the box empty used to blank the shop's own name site-wide and
            // leave the General page refusing to save until someone retyped it.
            'site_name' => V::text(max: 100, min: 2),
            'site_tagline' => V::text(required: false, max: 150),
            'site_description' => V::textarea(required: false, max: 500),
            'footer_about' => V::textarea(required: false, max: 1000),
            'footer_copyright' => V::text(required: false, max: 255),
            'announcement_text' => V::text(required: false, max: 255),
            'contact_email' => V::email(required: false),
            'contact_phone' => V::mobile(required: false),
            // Read by the floating chat button on every storefront page
            // (components/layouts/app.blade.php) but editable nowhere until now.
            'whatsapp_number' => V::mobile(required: false),
            'contact_address' => V::addressLine(required: false, max: 500),
            'site_logo' => V::image(required: false, maxKb: 2048, allowGif: false),
            'site_favicon' => V::image(required: false, maxKb: 1024, allowGif: false),
        ];

        // Each field has to point at its own network. V::url() alone accepted
        // any well-formed address, and production ended up with
        // https://www.youtube.com/ saved into facebook, instagram, twitter AND
        // linkedin - four icons in the header and footer, all opening YouTube's
        // front page, and the same wrong address repeated four times in the
        // site's sameAs structured data. A URL that is valid but points at the
        // wrong service is the failure worth catching here, because nothing
        // downstream can tell it from a correct one.
        $socialHosts = [
            'facebook' => ['facebook.com', 'fb.com', 'fb.me'],
            'instagram' => ['instagram.com', 'instagr.am'],
            'twitter' => ['twitter.com', 'x.com'],
            'linkedin' => ['linkedin.com', 'lnkd.in'],
            'youtube' => ['youtube.com', 'youtu.be'],
            'tiktok' => ['tiktok.com'],
            'pinterest' => ['pinterest.com', 'pin.it'],
        ];

        foreach ($socialHosts as $network => $hosts) {
            $rules["social_{$network}"] = [
                ...V::url(required: false, max: 255),
                function (string $attribute, mixed $value, Closure $fail) use ($hosts, $network) {
                    if (blank($value)) {
                        return;
                    }

                    $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));
                    $host = preg_replace('/^www\./', '', $host);

                    foreach ($hosts as $allowed) {
                        if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                            return;
                        }
                    }

                    $fail("The {$network} link must point at ".$hosts[0].'.');
                },
            ];
        }

        // The three about_us_video_* slots used to be validated and stored here.
        // The About Us strip is a list of its own now (Homepage > About Reels),
        // so this screen no longer accepts them: a field on two screens is a
        // field whose value depends on which one you saved last.
        $validated = $request->validate($rules);

        $fields = [
            'site_name', 'site_tagline', 'site_description',
            'footer_about', 'footer_copyright',
            'social_facebook', 'social_instagram', 'social_twitter', 'social_linkedin',
            'social_youtube', 'social_tiktok', 'social_pinterest',
            'contact_email', 'contact_phone', 'whatsapp_number', 'contact_address',
            'announcement_text',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                // Clearing a field is a legitimate edit, so a validated null is
                // written back as an empty string rather than skipped.
                Setting::set($field, $validated[$field] ?? '', 'string', 'homepage');
            }
        }

        if ($request->hasFile('site_logo')) {
            // Delete the file being replaced. Every logo ever uploaded used to
            // stay on the public disk, reachable by URL, with nothing pointing
            // at it - so the storage folder only ever grew.
            $previousLogo = Setting::get('site_logo', '');
            $path = $request->file('site_logo')->store('branding', 'public');
            Setting::set('site_logo', $path, 'string', 'homepage');

            if ($previousLogo && $previousLogo !== $path && ! str_starts_with($previousLogo, 'http')) {
                Storage::disk('public')->delete($previousLogo);
            }
        }

        if ($request->hasFile('site_favicon')) {
            $previousFavicon = Setting::get('site_favicon', '');
            $path = $request->file('site_favicon')->store('branding', 'public');
            Setting::set('site_favicon', $path, 'string', 'homepage');

            if ($previousFavicon && $previousFavicon !== $path && ! str_starts_with($previousFavicon, 'http')) {
                Storage::disk('public')->delete($previousFavicon);
            }
        }

        Cache::flush();

        return back()->with('success', 'Site settings updated successfully.');
    }

    // Hero Banners
    public function heroBanners()
    {
        $banners = Banner::where('position', 'hero')->ordered()->get();

        return view('admin.homepage.hero-banners', compact('banners'));
    }

    /**
     * Rules shared by the add and edit hero banner forms.
     *
     * `$banner` is the row being edited, and is only there for the schedule: a
     * start date that has already passed must stay acceptable, or renaming a
     * banner that went live last week would demand its start be dragged forward
     * before the form would save at all.
     *
     * @return array<string, mixed>
     */
    private function heroBannerRules(bool $mediaRequired, ?Banner $banner = null): array
    {
        return [
            'name' => V::text(required: false, max: 255),
            'image' => $mediaRequired
                ? ['required_without:video', ...V::image(required: false, maxKb: BannerMedia::MAX_IMAGE_KB, allowGif: true, maxWidth: BannerMedia::MAX_IMAGE_EDGE, maxHeight: BannerMedia::MAX_IMAGE_EDGE)]
                : V::image(required: false, maxKb: BannerMedia::MAX_IMAGE_KB, allowGif: true, maxWidth: BannerMedia::MAX_IMAGE_EDGE, maxHeight: BannerMedia::MAX_IMAGE_EDGE),
            'video' => V::video(required: false, maxKb: self::MAX_VIDEO_KB),
            // The mobile pair is an override, never a requirement: a banner
            // with neither still shows its desktop media on phones, so nothing
            // here is conditional on the desktop fields being filled.
            'mobile_image' => V::image(required: false, maxKb: BannerMedia::MAX_IMAGE_KB, allowGif: true, maxWidth: BannerMedia::MAX_IMAGE_EDGE, maxHeight: BannerMedia::MAX_IMAGE_EDGE),
            'mobile_video' => V::video(required: false, maxKb: self::MAX_VIDEO_KB),
            'link' => ['nullable', 'string', 'max:255', 'regex:'.self::LINK_REGEX],
            'title' => V::text(required: false, max: 255),
            'subtitle' => V::text(required: false, max: 500),
            'button_text' => V::text(required: false, max: 100),
            // Read out in place of the artwork. Optional because a banner with
            // none falls back to its heading, which is what a screen reader was
            // given before there was a column for it.
            'alt_text' => V::text(required: false, max: 255),
            // Both ends optional and independent: "from Monday", "until the end
            // of the sale" and "from now until further notice" are all things an
            // admin means, and only the last needs no dates at all.
            'starts_at' => V::scheduleStart(required: false, current: $banner?->starts_at),
            'ends_at' => V::scheduleEnd('starts_at', required: false, current: $banner?->ends_at),
            'overlay_style' => V::option(array_keys(Banner::OVERLAY_STYLES), required: false),
            'remove_video' => V::boolean(),
            'remove_mobile_image' => V::boolean(),
            'remove_mobile_video' => V::boolean(),
        ];
    }

    private function heroBannerMessages(): array
    {
        return [
            'image.required_without' => 'Upload an image, or a video to use instead.',
            'link.regex' => 'Enter a path such as /products, or a full https:// address.',
            'ends_at.after' => 'The end date must be later than the start date.',
            'starts_at.date' => 'Enter a valid start date and time.',
            'ends_at.date' => 'Enter a valid end date and time.',
        ] + V::imageMessages('image', BannerMedia::MAX_IMAGE_KB)
          + V::imageMessages('mobile_image', BannerMedia::MAX_IMAGE_KB)
          + V::videoMessages('video', BannerMedia::MAX_VIDEO_KB)
          + V::videoMessages('mobile_video', BannerMedia::MAX_VIDEO_KB);
    }

    /** Names the schedule fields as an admin would, so the messages read as English. */
    private function heroBannerAttributes(): array
    {
        return [
            'alt_text' => 'image description',
            'starts_at' => 'start date',
            'ends_at' => 'end date',
        ];
    }

    public function storeHeroBanner(Request $request)
    {
        // A banner needs a video or an image, not necessarily both. When a video
        // is supplied the image becomes optional and acts as the poster frame.
        $request->validate(
            $this->heroBannerRules(mediaRequired: true),
            $this->heroBannerMessages(),
            $this->heroBannerAttributes(),
        );

        Banner::create([
            'name' => $request->name,
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'button_text' => $request->button_text,
            // Which directory each column's file belongs in is BannerMedia's to
            // know. Spelling it out here is how the two banner screens came to
            // file the same mobile image in two different places.
            'image_url' => $request->hasFile('image')
                ? BannerMedia::store($request->file('image'), 'image_url')
                : null,
            'video_url' => $request->hasFile('video')
                ? BannerMedia::store($request->file('video'), 'video_url')
                : null,
            'mobile_image_url' => $request->hasFile('mobile_image')
                ? BannerMedia::store($request->file('mobile_image'), 'mobile_image_url')
                : null,
            'mobile_video_url' => $request->hasFile('mobile_video')
                ? BannerMedia::store($request->file('mobile_video'), 'mobile_video_url')
                : null,
            'link' => $request->link,
            'alt_text' => $request->alt_text,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'overlay_style' => $request->overlay_style ?? 'left-dark',
            'position' => 'hero',
            'priority' => Banner::where('position', 'hero')->max('priority') + 1,
            'is_active' => true,
        ]);

        Cache::flush();

        return back()->with('success', 'Hero banner added successfully.');
    }

    public function updateHeroBanner(Request $request, Banner $banner)
    {
        $request->validate(
            $this->heroBannerRules(mediaRequired: false, banner: $banner),
            $this->heroBannerMessages(),
            $this->heroBannerAttributes(),
        );

        // A video-only banner is legitimate - the image is optional when a video
        // is supplied - so ticking "remove the video and show the image instead"
        // on one of those left a banner with no media at all, which the storefront
        // then rendered as a placeholder box. Refuse it and say why.
        $removingVideo = $request->boolean('remove_video') && ! $request->hasFile('video');
        $willHaveImage = $request->hasFile('image') || $banner->image_url;

        if ($removingVideo && ! $willHaveImage) {
            return back()
                ->withInput()
                ->withErrors(['remove_video' => 'Upload an image first - removing the video would leave this banner with nothing to show.']);
        }

        // Every field the form posts has to be named here: only() drops what it
        // is not asked for, so a field added to the Blade and forgotten in this
        // list saves cleanly and changes nothing, with no error to explain it.
        $data = $request->only([
            'name', 'title', 'subtitle', 'button_text', 'link',
            'alt_text', 'starts_at', 'ends_at', 'overlay_style',
        ]);

        if ($request->hasFile('image')) {
            $data['image_url'] = BannerMedia::replace($request->file('image'), 'image_url', $banner->image_url);
        }

        if ($request->hasFile('video')) {
            $data['video_url'] = BannerMedia::replace($request->file('video'), 'video_url', $banner->video_url);
        } elseif ($request->boolean('remove_video') && $banner->video_url) {
            // Explicit removal, so a banner can go back to being image-only.
            BannerMedia::delete($banner->video_url);
            $data['video_url'] = null;
        }

        // The mobile pair needs no equivalent of the guard above: dropping an
        // override leaves the banner showing its desktop media on phones, which
        // is what a banner without one does anyway.
        if ($request->hasFile('mobile_image')) {
            $data['mobile_image_url'] = BannerMedia::replace($request->file('mobile_image'), 'mobile_image_url', $banner->mobile_image_url);
        } elseif ($request->boolean('remove_mobile_image') && $banner->mobile_image_url) {
            BannerMedia::delete($banner->mobile_image_url);
            $data['mobile_image_url'] = null;
        }

        if ($request->hasFile('mobile_video')) {
            $data['mobile_video_url'] = BannerMedia::replace($request->file('mobile_video'), 'mobile_video_url', $banner->mobile_video_url);
        } elseif ($request->boolean('remove_mobile_video') && $banner->mobile_video_url) {
            BannerMedia::delete($banner->mobile_video_url);
            $data['mobile_video_url'] = null;
        }

        $banner->update($data);
        Cache::flush();

        return back()->with('success', 'Hero banner updated successfully.');
    }

    public function deleteHeroBanner(Banner $banner)
    {
        BannerMedia::deleteAll([
            $banner->image_url,
            $banner->video_url,
            $banner->mobile_image_url,
            $banner->mobile_video_url,
        ]);
        $banner->delete();
        Cache::flush();

        return back()->with('success', 'Hero banner deleted successfully.');
    }

    public function reorderHeroBanners(Request $request)
    {
        $request->validate([
            'order' => ['required', 'array', 'max:500'],
            // Scoped to hero. The rule used to accept any banner id in the table,
            // so this endpoint could renumber promo or sidebar banners that this
            // screen does not manage and cannot show.
            'order.*' => ['integer', Rule::exists('banners', 'id')->where('position', 'hero')],
        ]);

        foreach ($request->order as $position => $id) {
            Banner::where('id', $id)->where('position', 'hero')->update(['priority' => $position]);
        }

        Cache::flush();

        return response()->json(['success' => true]);
    }

    public function toggleHeroBanner(Banner $banner)
    {
        $banner->update(['is_active' => ! $banner->is_active]);
        Cache::flush();

        return back()->with('success', 'Banner status updated.');
    }

    /**
     * A section listed in RETIRED_SECTION_KEYS is not offered for editing.
     *
     * Nothing links to it any more, so this only catches a bookmarked or
     * hand-typed URL - but the row is still in the table and the edit form
     * would happily save a heading that no page reads.
     */
    private function abortIfRetired(HomepageSection $section): void
    {
        abort_if(in_array($section->key, self::RETIRED_SECTION_KEYS, true), 404);
    }

    // Homepage Sections
    public function sections()
    {
        $sections = HomepageSection::whereNotIn('key', self::RETIRED_SECTION_KEYS)->ordered()->get();

        return view('admin.homepage.sections', compact('sections'));
    }

    public function editSection(HomepageSection $section)
    {
        $this->abortIfRetired($section);

        return view('admin.homepage.edit-section', compact('section'));
    }

    public function updateSection(Request $request, HomepageSection $section)
    {
        $this->abortIfRetired($section);

        // background_color and text_color were written straight through from the
        // request. They are interpolated into a `style` attribute on the home
        // page, so an arbitrary string there is CSS injection; a hex colour is
        // all <input type="color"> can produce anyway. `content` was likewise
        // stored as whatever array arrived - it is the benefits repeater, and
        // its three fields are all that belongs in the JSON column.
        $validated = $request->validate([
            'title' => V::text(max: 255, min: 2),
            'subtitle' => V::textarea(required: false, max: 500),
            'button_text' => V::text(required: false, max: 100),
            'button_link' => ['nullable', 'string', 'max:255', 'regex:'.self::LINK_REGEX],
            'background_color' => ['nullable', 'string', 'regex:'.self::HEX_COLOR_REGEX],
            'text_color' => ['nullable', 'string', 'regex:'.self::HEX_COLOR_REGEX],
            'image' => V::image(required: false, maxKb: 5120, allowGif: true),
            'is_active' => V::boolean(),
            'content' => ['nullable', 'array', 'max:24'],
            'content.*' => ['array'],
            'content.*.title' => V::text(required: false, max: 120),
            'content.*.description' => V::text(required: false, max: 255),
            'content.*.icon' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/'],
        ], [
            'button_link.regex' => 'Enter a path such as /products, or a full https:// address.',
            'background_color.regex' => 'Pick a colour, or enter one as #rrggbb.',
            'text_color.regex' => 'Pick a colour, or enter one as #rrggbb.',
            'content.*.icon.regex' => 'An icon name may only contain letters, numbers, hyphens and underscores.',
        ]);

        $data = [
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
        ];
        $data['is_active'] = $request->boolean('is_active');

        // The form only renders the button, colour and repeater inputs for the
        // section types that use them, so an absent field means "this form does
        // not edit that" - not "the admin cleared it". Writing them
        // unconditionally wiped button_text and button_link off every section
        // whose form hides them, the first time anyone edited its heading.
        foreach (['button_text', 'button_link', 'background_color', 'text_color'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $validated[$field] ?? null;
            }
        }

        // The repeater posts nothing at all once its last card is removed, which
        // is indistinguishable from a form that has no repeater - so deleting
        // every benefit used to silently restore the old list on save. The
        // hidden marker says "this form did edit the repeater", making an empty
        // list a real, saveable state.
        if ($request->boolean('has_content_repeater') || $request->has('content')) {
            $data['content'] = array_values(array_map(fn (array $item): array => [
                'title' => $item['title'] ?? '',
                'description' => $item['description'] ?? '',
                'icon' => $item['icon'] ?? '',
            ], $validated['content'] ?? []));
        }

        if ($request->hasFile('image')) {
            if ($section->image_url) {
                Storage::disk('public')->delete($section->image_url);
            }
            $data['image_url'] = $request->file('image')->store('sections', 'public');
        }

        $section->update($data);
        Cache::flush();

        return back()->with('success', 'Section updated successfully.');
    }

    public function toggleSection(HomepageSection $section)
    {
        $this->abortIfRetired($section);

        $section->update(['is_active' => ! $section->is_active]);
        Cache::flush();

        return back()->with('success', 'Section visibility updated.');
    }

    // There is deliberately no reorderSections endpoint. The home page lays these
    // blocks out in hand-written markup rather than looping over the table, so
    // `homepage_sections.position` orders nothing a visitor can see; the endpoint
    // that used to be here was never called and could only ever have written a
    // number that no page reads.

    /**
     * Move a row one place up or down within $scope by swapping `position` with
     * its nearest neighbour.
     *
     * The two lists on this controller - qualities and shop filter items - both
     * stamp `position` once at creation and had no way to change it afterwards,
     * so each ran in creation order permanently. Swapping with the adjacent row
     * keeps the numbers contiguous and needs no drag surface.
     *
     * @param  Model  $row
     * @param  Builder  $scope  the list $row belongs to
     */
    private function swapPosition($row, $scope, string $direction): void
    {
        $up = $direction === 'up';

        $neighbour = (clone $scope)
            ->when(
                $up,
                fn ($q) => $q->where('position', '<', $row->position)->orderByDesc('position'),
                fn ($q) => $q->where('position', '>', $row->position)->orderBy('position'),
            )
            ->first();

        // Rows created before this existed can share a position, which the strict
        // comparison above skips entirely; fall back to id order so a tie still
        // moves rather than appearing to do nothing.
        if (! $neighbour) {
            $neighbour = (clone $scope)
                ->where('position', $row->position)
                ->when(
                    $up,
                    fn ($q) => $q->where('id', '<', $row->id)->orderByDesc('id'),
                    fn ($q) => $q->where('id', '>', $row->id)->orderBy('id'),
                )
                ->first();
        }

        if (! $neighbour) {
            return;
        }

        $original = $row->position;
        $row->update(['position' => $neighbour->position]);
        $neighbour->update(['position' => $original]);

        // Swapping two equal positions changes nothing visible, so break the tie.
        if ($original === $neighbour->position) {
            $row->update(['position' => $up ? $original - 1 : $original + 1]);
        }
    }

    // ============================================================
    // Shop It Your Way - the filter rails, derived from the catalogue
    // ============================================================

    /**
     * The rails, as the catalogue currently spells them.
     *
     * Nothing here is a stored list any more. Size comes off the variants,
     * Shade and Texture off each product's own lists, Price off the live
     * spread - so a value an admin types into a product is on the rail the
     * moment it is saved, and the last product carrying a value takes it away
     * again. What the admin owns on this screen is the other half: whether a
     * value the catalogue carries should be offered to a shopper.
     */
    public function shopFilters()
    {
        return view('admin.homepage.shop-filters', [
            // Hidden values included: the screen has to show what has been
            // taken away in order to offer it back.
            'groups' => ShopFilterCatalogue::groups(includeHidden: true),
        ]);
    }

    /**
     * Hide one derived value.
     *
     * The value is not deleted from anything - no product loses its colour,
     * its size or its texture. Only the offer is withdrawn, and it stays
     * withdrawn: a new product arriving with the same value does not quietly
     * put it back, which is the whole reason this is a stored decision rather
     * than a flag on a row that is itself derived.
     */
    public function storeShopFilterExclusion(Request $request)
    {
        $data = $request->validate([
            'type' => V::option(ShopFilterExclusion::TYPES),
            // The normalised key, not the label: it is what identifies the
            // value across every spelling of it the catalogue holds.
            'value_key' => ['required', 'string', 'max:191'],
            'label' => V::text(max: 191),
        ]);

        // firstOrCreate, not create: the unique index is the real guard, and
        // two admins hiding the same value at the same moment should both
        // succeed rather than one of them meeting a 500.
        ShopFilterExclusion::firstOrCreate(
            ['type' => $data['type'], 'value_key' => $data['value_key']],
            ['label' => $data['label']],
        );

        return back()->with('success', 'Hidden from the shop filters. The products keep the value.');
    }

    /** Offer a hidden value again. Bound on the uuid, never on a row id. */
    public function destroyShopFilterExclusion(ShopFilterExclusion $exclusion)
    {
        $exclusion->delete();

        return back()->with('success', 'Shown in the shop filters again.');
    }

    // ============================================================
    // About Us reels - the clip strip under "Crafted to Last"
    // ============================================================

    /**
     * The strip used to be three fixed settings keys, so it could only ever
     * hold three clips: a store with one left two empty cards' worth of
     * scaffolding behind, and a fourth clip had nowhere to go. Reels are rows
     * now - add one, delete one, reorder them, hide one without losing it.
     */
    public function aboutReels()
    {
        $reels = AboutReel::ordered()->get();

        return view('admin.homepage.about-reels', compact('reels'));
    }

    public function storeAboutReel(Request $request)
    {
        $request->validate(
            [
                // required_without nothing: a reel IS its clip, so unlike a hero
                // banner there is no second medium that could stand in for it.
                'video' => ['required', ...$this->videoRules()],
            ],
            $this->videoMessages('video') + ['video.required' => 'Choose a video file to add as a reel.'],
        );

        AboutReel::create([
            'video_path' => 'storage/'.$request->file('video')->store('storefront/about', 'public'),
            'position' => (AboutReel::max('position') ?? 0) + 1,
            'is_active' => true,
        ]);
        Cache::flush();

        return back()->with('success', 'Reel added.');
    }

    /** Swap a reel's clip for another, keeping its place in the strip. */
    public function updateAboutReel(Request $request, AboutReel $aboutReel)
    {
        $request->validate(
            ['video' => ['required', ...$this->videoRules()]],
            $this->videoMessages('video') + ['video.required' => 'Choose the video file to replace this reel with.'],
        );

        // Only a file this row owns: a bundled clip ships with the repo and is
        // not ours to delete just because one reel was pointed elsewhere.
        $previous = $aboutReel->ownsFile() ? $aboutReel->storagePath() : null;

        $aboutReel->update([
            'video_path' => 'storage/'.$request->file('video')->store('storefront/about', 'public'),
        ]);

        // These clips run to tens of megabytes; leaving the replaced one behind
        // on every re-upload fills the disk for no purpose.
        if ($previous) {
            Storage::disk('public')->delete($previous);
        }

        Cache::flush();

        return back()->with('success', 'Reel updated.');
    }

    public function toggleAboutReel(AboutReel $aboutReel)
    {
        $aboutReel->update(['is_active' => ! $aboutReel->is_active]);
        Cache::flush();

        return back()->with('success', 'Reel visibility updated.');
    }

    public function moveAboutReel(Request $request, AboutReel $aboutReel)
    {
        $validated = $request->validate(['direction' => V::option(['up', 'down'])]);

        $this->swapPosition($aboutReel, AboutReel::query(), $validated['direction']);
        Cache::flush();

        return back()->with('success', 'Reel order updated.');
    }

    public function deleteAboutReel(AboutReel $aboutReel)
    {
        // Only a file this row owns. A bundled clip is shipped with the repo and
        // an https:// link is somebody else's server - deleting either because a
        // card was taken off the home page would be well beyond what was asked.
        if ($aboutReel->ownsFile()) {
            Storage::disk('public')->delete($aboutReel->storagePath());
        }

        $aboutReel->delete();
        Cache::flush();

        return back()->with('success', 'Reel deleted.');
    }

    // ============================================================
    // Our Qualities - 6 quality blocks on home page dark section
    // ============================================================
    public function qualities()
    {
        $qualities = Quality::ordered()->get();

        return view('admin.homepage.qualities', compact('qualities'));
    }

    public function storeQuality(Request $request)
    {
        $data = $request->validate([
            'title' => V::text(max: 255, min: 2),
            'description' => V::textarea(max: 500, min: 3),
            'image' => V::image(required: false, maxKb: 5120, allowGif: true),
        ]);
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_url'] = $request->file('image')->store('qualities', 'public');
        }

        $data['position'] = (Quality::max('position') ?? 0) + 1;
        $data['is_active'] = true;
        Quality::create($data);
        Cache::flush();

        return back()->with('success', 'Quality added.');
    }

    public function updateQuality(Request $request, Quality $quality)
    {
        $data = $request->validate([
            'title' => V::text(max: 255, min: 2),
            'description' => V::textarea(max: 500, min: 3),
            'image' => V::image(required: false, maxKb: 5120, allowGif: true),
            'remove_image' => V::boolean(),
        ]);
        unset($data['image'], $data['remove_image']);

        // A new upload replaces the old file; the explicit remove checkbox sends
        // the card back to its no-image layout. Either way the orphan is deleted
        // rather than left behind on disk.
        if ($request->hasFile('image')) {
            if ($quality->image_url) {
                Storage::disk('public')->delete($quality->image_url);
            }
            $data['image_url'] = $request->file('image')->store('qualities', 'public');
        } elseif ($request->boolean('remove_image') && $quality->image_url) {
            Storage::disk('public')->delete($quality->image_url);
            $data['image_url'] = null;
        }

        $quality->update($data);
        Cache::flush();

        return back()->with('success', 'Quality updated.');
    }

    /**
     * Swap a quality card with its neighbour.
     *
     * The page told admins to "reorder by editing position later" and there was
     * no position field on the form and no endpoint behind it, so the order was
     * whatever order the cards happened to be created in, permanently.
     */
    public function moveQuality(Request $request, Quality $quality)
    {
        $validated = $request->validate(['direction' => V::option(['up', 'down'])]);

        $this->swapPosition($quality, Quality::query(), $validated['direction']);
        Cache::flush();

        return back()->with('success', 'Quality order updated.');
    }

    public function toggleQuality(Quality $quality)
    {
        $quality->update(['is_active' => ! $quality->is_active]);
        Cache::flush();

        return back()->with('success', 'Quality visibility updated.');
    }

    public function deleteQuality(Quality $quality)
    {
        if ($quality->image_url) {
            Storage::disk('public')->delete($quality->image_url);
        }
        $quality->delete();
        Cache::flush();

        return back()->with('success', 'Quality deleted.');
    }

    // Navigation Menus
    public function navigation()
    {
        // getByLocation() is the storefront's reader: it filters to active,
        // top-level rows. Using it here meant a hidden item, a nested one, or
        // anything left over in footer_col4 was invisible on the one screen that
        // could edit or delete it - unreachable except through the database.
        $byLocation = NavigationMenu::query()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy('location');

        $headerMenus = $byLocation->get('header', collect());
        $footerCol1 = $byLocation->get('footer_col1', collect());
        $footerCol2 = $byLocation->get('footer_col2', collect());
        $footerCol3 = $byLocation->get('footer_col3', collect());

        // Rows filed under a location no page renders, surfaced so they can be
        // moved or removed rather than sitting invisible forever.
        //
        // toBase() first: groupBy() on an Eloquent collection hands back another
        // Eloquent collection, whose except() is written for model keys and
        // calls getKey() on every entry. The entries here are the grouped
        // sub-collections, not models, so it threw BadMethodCallException and
        // this whole screen answered 500 - leaving header and footer navigation
        // with no way to edit it. A plain collection excludes by key, which is
        // what the location strings are.
        $orphanMenus = $byLocation
            ->toBase()
            ->except(self::NAV_LOCATIONS)
            ->flatten();

        return view('admin.homepage.navigation', compact(
            'headerMenus', 'footerCol1', 'footerCol2', 'footerCol3', 'orphanMenus'
        ));
    }

    public function storeNavItem(Request $request)
    {
        $validated = $request->validate([
            'location' => V::option(self::NAV_LOCATIONS),
            'label' => V::text(max: 255),
            'url' => ['required', 'string', 'max:255', 'regex:'.self::LINK_REGEX],
            'parent_id' => ['nullable', 'integer', Rule::exists('navigation_menus', 'id')],
        ], [
            'url.regex' => 'Enter a path such as /about, or a full https:// address.',
        ]);

        NavigationMenu::create([
            'location' => $validated['location'],
            'label' => $validated['label'],
            'url' => $validated['url'],
            'parent_id' => $validated['parent_id'] ?? null,
            'position' => NavigationMenu::where('location', $validated['location'])->max('position') + 1,
            'is_active' => true,
        ]);
        Cache::flush();

        return back()->with('success', 'Menu item added successfully.');
    }

    public function updateNavItem(Request $request, NavigationMenu $menu)
    {
        $validated = $request->validate([
            'label' => V::text(max: 255),
            'url' => ['required', 'string', 'max:255', 'regex:'.self::LINK_REGEX],
            // Editable so an item filed under the wrong column - or under the
            // retired footer_col4 - can be moved rather than deleted and retyped.
            'location' => V::option(self::NAV_LOCATIONS),
        ], [
            'url.regex' => 'Enter a path such as /about, or a full https:// address.',
        ]);

        $menu->update([
            'label' => $validated['label'],
            'url' => $validated['url'],
            'location' => $validated['location'],
        ]);
        Cache::flush();

        return back()->with('success', 'Menu item updated successfully.');
    }

    public function toggleNavItem(NavigationMenu $menu)
    {
        $menu->update(['is_active' => ! $menu->is_active]);
        Cache::flush();

        return back()->with('success', 'Menu item visibility updated.');
    }

    public function deleteNavItem(NavigationMenu $menu)
    {
        $menu->delete();
        Cache::flush();

        return back()->with('success', 'Menu item deleted successfully.');
    }
}
