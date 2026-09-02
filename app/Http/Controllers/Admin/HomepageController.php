<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\HomepageSection;
use App\Models\NavigationMenu;
use App\Models\Quality;
use App\Models\Setting;
use App\Models\ShopFilterItem;
use App\Models\Testimonial;
use App\Rules\ValidationRules as V;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class HomepageController extends Controller
{
    /**
     * Upload ceiling for hero videos, in kilobytes.
     *
     * The existing hero clips are around 15 MB, so 64 MB leaves comfortable
     * headroom. The server allows 256 MB per upload, so PHP will not reject a
     * file before this rule can report a readable error.
     */
    private const MAX_VIDEO_KB = 65536;

    /**
     * A link an admin may point a banner, button or menu item at.
     *
     * Every one of these values ends up in an `href` on the storefront. Blade
     * escapes the text, but escaping does not disarm a scheme: `javascript:...`
     * in a stored link is a click away from running as the visitor. So the
     * scheme is allow-listed instead - http, https, mailto and tel - alongside
     * the two shapes that carry no scheme at all: a site-relative path and a
     * bare fragment.
     */
    private const LINK_REGEX = '/^(?:(?:https?|mailto|tel):\S+|\/\S*|#\S*)$/i';

    /**
     * A video the About Us section can play: an absolute URL, or a path
     * relative to the web root such as `storage/storefront/about/reel.mp4`.
     */
    private const VIDEO_SRC_REGEX = '/^(?:https?:\/\/\S+|[A-Za-z0-9._\-\/]+\.(?:mp4|webm|mov|ogg))$/i';

    /** A six-digit CSS hex colour - what <input type="color"> submits. */
    private const HEX_COLOR_REGEX = '/^#[0-9A-Fa-f]{6}$/';

    /** Locations the header and footer partials know how to render. */
    private const NAV_LOCATIONS = ['header', 'footer_col1', 'footer_col2', 'footer_col3', 'footer_col4'];

    /** Video uploads: extension, sniffed type and size all checked. */
    private function videoRules(): array
    {
        return [
            'nullable', 'file',
            'mimes:mp4,webm,mov',
            'mimetypes:video/mp4,video/webm,video/quicktime',
            'max:'.self::MAX_VIDEO_KB,
        ];
    }

    private function videoMessages(string $field): array
    {
        return [
            "{$field}.mimes" => 'The video must be an MP4, WebM or MOV file.',
            "{$field}.mimetypes" => 'The video must be an MP4, WebM or MOV file.',
            "{$field}.max" => 'The video may not be larger than '.(self::MAX_VIDEO_KB / 1024).' MB.',
        ];
    }

    public function index()
    {
        $sections = HomepageSection::ordered()->get();
        $banners = Banner::where('position', 'hero')->ordered()->get();
        $testimonials = Testimonial::ordered()->get();

        return view('admin.homepage.index', compact('sections', 'banners', 'testimonials'));
    }

    // Site Settings (Logo, Brand Name, etc.)
    public function siteSettings()
    {
        $settings = [
            'site_logo' => Setting::get('site_logo', ''),
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
            'contact_address' => Setting::get('contact_address', ''),
            'announcement_text' => Setting::get('announcement_text', ''),
            'about_us_video_url' => Setting::get('about_us_video_url', ''),
            'about_us_video_url_2' => Setting::get('about_us_video_url_2', ''),
            'about_us_video_url_3' => Setting::get('about_us_video_url_3', ''),
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
            'site_name' => V::text(required: false, max: 100),
            'site_tagline' => V::text(required: false, max: 150),
            'site_description' => V::textarea(required: false, max: 500),
            'footer_about' => V::textarea(required: false, max: 1000),
            'footer_copyright' => V::text(required: false, max: 255),
            'announcement_text' => V::text(required: false, max: 255),
            'contact_email' => V::email(required: false),
            'contact_phone' => V::mobile(required: false),
            'contact_address' => V::addressLine(required: false, max: 500),
            'site_logo' => V::image(required: false, maxKb: 2048, allowGif: false),
        ];

        foreach (['facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok', 'pinterest'] as $network) {
            $rules["social_{$network}"] = V::url(required: false, max: 255);
        }

        $messages = [];

        foreach ([1, 2, 3] as $slot) {
            $urlField = $slot === 1 ? 'about_us_video_url' : "about_us_video_url_{$slot}";
            $fileField = $slot === 1 ? 'about_us_video_file' : "about_us_video_file_{$slot}";

            $rules[$urlField] = ['nullable', 'string', 'max:255', 'regex:'.self::VIDEO_SRC_REGEX];
            $rules[$fileField] = $this->videoRules();

            $messages["{$urlField}.regex"] = 'Enter a full https:// address, or a path to an .mp4, .webm or .mov file.';
            $messages += $this->videoMessages($fileField);
        }

        $validated = $request->validate($rules, $messages);

        $fields = [
            'site_name', 'site_tagline', 'site_description',
            'footer_about', 'footer_copyright',
            'social_facebook', 'social_instagram', 'social_twitter', 'social_linkedin',
            'social_youtube', 'social_tiktok', 'social_pinterest',
            'contact_email', 'contact_phone', 'contact_address',
            'announcement_text',
            // The About Us section renders three videos; all three are editable.
            'about_us_video_url', 'about_us_video_url_2', 'about_us_video_url_3',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                // Clearing a field is a legitimate edit, so a validated null is
                // written back as an empty string rather than skipped.
                Setting::set($field, $validated[$field] ?? '', 'string', 'homepage');
            }
        }

        if ($request->hasFile('site_logo')) {
            $path = $request->file('site_logo')->store('branding', 'public');
            Setting::set('site_logo', $path, 'string', 'homepage');
        }

        // About Us video uploads - an uploaded file overrides that slot's URL field.
        foreach ([
            'about_us_video_file' => 'about_us_video_url',
            'about_us_video_file_2' => 'about_us_video_url_2',
            'about_us_video_file_3' => 'about_us_video_url_3',
        ] as $fileField => $urlSetting) {
            if ($request->hasFile($fileField)) {
                $videoPath = $request->file($fileField)->store('storefront/about', 'public');
                Setting::set($urlSetting, 'storage/'.$videoPath, 'string', 'homepage');
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
     * @return array<string, mixed>
     */
    private function heroBannerRules(bool $mediaRequired): array
    {
        return [
            'name' => V::text(required: false, max: 255),
            'image' => $mediaRequired
                ? ['required_without:video', ...V::image(required: false, maxKb: 5120, allowGif: true)]
                : V::image(required: false, maxKb: 5120, allowGif: true),
            'video' => $this->videoRules(),
            'link' => ['nullable', 'string', 'max:255', 'regex:'.self::LINK_REGEX],
            'title' => V::text(required: false, max: 255),
            'subtitle' => V::text(required: false, max: 500),
            'button_text' => V::text(required: false, max: 100),
            'overlay_style' => V::option(array_keys(Banner::OVERLAY_STYLES), required: false),
            'remove_video' => V::boolean(),
        ];
    }

    private function heroBannerMessages(): array
    {
        return [
            'image.required_without' => 'Upload an image, or a video to use instead.',
            'link.regex' => 'Enter a path such as /products, or a full https:// address.',
        ] + $this->videoMessages('video');
    }

    public function storeHeroBanner(Request $request)
    {
        // A banner needs a video or an image, not necessarily both. When a video
        // is supplied the image becomes optional and acts as the poster frame.
        $request->validate($this->heroBannerRules(mediaRequired: true), $this->heroBannerMessages());

        Banner::create([
            'name' => $request->name,
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'button_text' => $request->button_text,
            'image_url' => $request->hasFile('image')
                ? $request->file('image')->store('banners', 'public')
                : null,
            'video_url' => $request->hasFile('video')
                ? $request->file('video')->store('banners/video', 'public')
                : null,
            'link' => $request->link,
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
        $request->validate($this->heroBannerRules(mediaRequired: false), $this->heroBannerMessages());

        $data = $request->only(['name', 'title', 'subtitle', 'button_text', 'link', 'overlay_style']);

        if ($request->hasFile('image')) {
            if ($banner->image_url) {
                Storage::disk('public')->delete($banner->image_url);
            }
            $data['image_url'] = $request->file('image')->store('banners', 'public');
        }

        if ($request->hasFile('video')) {
            if ($banner->video_url) {
                Storage::disk('public')->delete($banner->video_url);
            }
            $data['video_url'] = $request->file('video')->store('banners/video', 'public');
        } elseif ($request->boolean('remove_video') && $banner->video_url) {
            // Explicit removal, so a banner can go back to being image-only.
            Storage::disk('public')->delete($banner->video_url);
            $data['video_url'] = null;
        }

        $banner->update($data);
        Cache::flush();

        return back()->with('success', 'Hero banner updated successfully.');
    }

    public function deleteHeroBanner(Banner $banner)
    {
        if ($banner->image_url) {
            Storage::disk('public')->delete($banner->image_url);
        }
        if ($banner->video_url) {
            Storage::disk('public')->delete($banner->video_url);
        }
        $banner->delete();
        Cache::flush();

        return back()->with('success', 'Hero banner deleted successfully.');
    }

    public function reorderHeroBanners(Request $request)
    {
        $request->validate([
            'order' => ['required', 'array', 'max:500'],
            'order.*' => ['integer', Rule::exists('banners', 'id')],
        ]);

        foreach ($request->order as $position => $id) {
            Banner::where('id', $id)->update(['priority' => $position]);
        }

        Cache::flush();

        return response()->json(['success' => true]);
    }

    public function toggleHeroBanner(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);
        Cache::flush();

        return back()->with('success', 'Banner status updated.');
    }

    // Homepage Sections
    public function sections()
    {
        $sections = HomepageSection::ordered()->get();

        return view('admin.homepage.sections', compact('sections'));
    }

    public function editSection(HomepageSection $section)
    {
        return view('admin.homepage.edit-section', compact('section'));
    }

    public function updateSection(Request $request, HomepageSection $section)
    {
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
            'button_text' => $validated['button_text'] ?? null,
            'button_link' => $validated['button_link'] ?? null,
        ];
        $data['is_active'] = $request->boolean('is_active');

        if ($request->has('background_color')) {
            $data['background_color'] = $validated['background_color'] ?? null;
        }

        if ($request->has('text_color')) {
            $data['text_color'] = $validated['text_color'] ?? null;
        }

        if ($request->has('content')) {
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
        $section->update(['is_active' => !$section->is_active]);
        Cache::flush();

        return back()->with('success', 'Section visibility updated.');
    }

    // There is deliberately no reorderSections endpoint. The home page lays these
    // blocks out in hand-written markup rather than looping over the table, so
    // `homepage_sections.position` orders nothing a visitor can see; the endpoint
    // that used to be here was never called and could only ever have written a
    // number that no page reads.

    // Testimonials

    /** @return array<string, mixed> */
    private function testimonialRules(): array
    {
        return [
            // A real customer name: O'Connor, Mary-Anne, रवि कुमार all pass,
            // "346@#$!@fdf sf" does not.
            'name' => V::name(),
            'title' => V::text(required: false, max: 255),
            'content' => V::textarea(max: 1000, min: 3),
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'product_name' => V::text(required: false, max: 255),
            'avatar' => V::image(required: false, maxKb: 2048, allowGif: true),
        ];
    }

    public function testimonials()
    {
        $testimonials = Testimonial::ordered()->get();

        return view('admin.homepage.testimonials', compact('testimonials'));
    }

    public function storeTestimonial(Request $request)
    {
        $validated = $request->validate($this->testimonialRules());

        $data = [
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'rating' => $validated['rating'],
            'product_name' => $validated['product_name'] ?? null,
        ];
        $data['position'] = Testimonial::max('position') + 1;
        $data['is_active'] = true;

        if ($request->hasFile('avatar')) {
            $data['avatar_url'] = $request->file('avatar')->store('testimonials', 'public');
        }

        Testimonial::create($data);

        return back()->with('success', 'Testimonial added successfully.');
    }

    public function updateTestimonial(Request $request, Testimonial $testimonial)
    {
        $validated = $request->validate($this->testimonialRules());

        $data = [
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'rating' => $validated['rating'],
            'product_name' => $validated['product_name'] ?? null,
        ];

        if ($request->hasFile('avatar')) {
            if ($testimonial->avatar_url) {
                Storage::disk('public')->delete($testimonial->avatar_url);
            }
            $data['avatar_url'] = $request->file('avatar')->store('testimonials', 'public');
        }

        $testimonial->update($data);

        return back()->with('success', 'Testimonial updated successfully.');
    }

    public function deleteTestimonial(Testimonial $testimonial)
    {
        if ($testimonial->avatar_url) {
            Storage::disk('public')->delete($testimonial->avatar_url);
        }
        $testimonial->delete();

        return back()->with('success', 'Testimonial deleted successfully.');
    }

    public function toggleTestimonial(Testimonial $testimonial)
    {
        $testimonial->update(['is_active' => !$testimonial->is_active]);

        return back()->with('success', 'Testimonial visibility updated.');
    }

    // ============================================================
    // Shop It Your Way - Size / Price / Shade filter items
    // ============================================================

    /**
     * shade_hex is interpolated into `style="color: ..."` on the home page.
     * Blade escapes the value, so the attribute cannot be broken out of, but
     * anything that is not a colour is still arbitrary CSS in the page. Hex
     * only - which is all the field was ever meant to hold.
     *
     * @return array<string, mixed>
     */
    private function shopFilterRules(): array
    {
        return [
            'type' => V::option(ShopFilterItem::TYPES),
            'label' => V::text(max: 120),
            'sub_label' => V::text(required: false, max: 120),
            'shade_hex' => ['nullable', 'string', 'max:9', 'regex:/^#(?:[0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/'],
            'query_string' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\-=&%.+,\[\]]+$/'],
        ];
    }

    private function shopFilterMessages(): array
    {
        return [
            'shade_hex.regex' => 'Enter a hex colour such as #b8895a.',
            'query_string.regex' => 'Enter a query string such as size=M or price_min=1000&price_max=2000.',
        ];
    }

    public function shopFilters()
    {
        $items = ShopFilterItem::ordered()->get()->groupBy('type');

        return view('admin.homepage.shop-filters', compact('items'));
    }

    public function storeShopFilter(Request $request)
    {
        $data = $request->validate($this->shopFilterRules(), $this->shopFilterMessages());

        $data['position'] = (ShopFilterItem::where('type', $data['type'])->max('position') ?? 0) + 1;
        $data['is_active'] = true;
        ShopFilterItem::create($data);
        Cache::flush();

        return back()->with('success', 'Filter item added.');
    }

    public function updateShopFilter(Request $request, ShopFilterItem $shopFilter)
    {
        $data = $request->validate($this->shopFilterRules(), $this->shopFilterMessages());

        $shopFilter->update($data);
        Cache::flush();

        return back()->with('success', 'Filter item updated.');
    }

    public function toggleShopFilter(ShopFilterItem $shopFilter)
    {
        $shopFilter->update(['is_active' => !$shopFilter->is_active]);
        Cache::flush();

        return back()->with('success', 'Filter visibility updated.');
    }

    public function deleteShopFilter(ShopFilterItem $shopFilter)
    {
        $shopFilter->delete();
        Cache::flush();

        return back()->with('success', 'Filter item deleted.');
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

    public function toggleQuality(Quality $quality)
    {
        $quality->update(['is_active' => !$quality->is_active]);
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
        $headerMenus = NavigationMenu::getByLocation('header');
        $footerCol1 = NavigationMenu::getByLocation('footer_col1');
        $footerCol2 = NavigationMenu::getByLocation('footer_col2');
        $footerCol3 = NavigationMenu::getByLocation('footer_col3');

        return view('admin.homepage.navigation', compact('headerMenus', 'footerCol1', 'footerCol2', 'footerCol3'));
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

        return back()->with('success', 'Menu item added successfully.');
    }

    public function updateNavItem(Request $request, NavigationMenu $menu)
    {
        $validated = $request->validate([
            'label' => V::text(max: 255),
            'url' => ['required', 'string', 'max:255', 'regex:'.self::LINK_REGEX],
        ], [
            'url.regex' => 'Enter a path such as /about, or a full https:// address.',
        ]);

        $menu->update([
            'label' => $validated['label'],
            'url' => $validated['url'],
        ]);

        return back()->with('success', 'Menu item updated successfully.');
    }

    public function deleteNavItem(NavigationMenu $menu)
    {
        $menu->delete();

        return back()->with('success', 'Menu item deleted successfully.');
    }
}
