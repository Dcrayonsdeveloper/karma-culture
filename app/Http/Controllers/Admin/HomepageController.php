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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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
            'site_name' => Setting::get('site_name', 'ForeverKids'),
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
        ];

        return view('admin.homepage.site-settings', compact('settings'));
    }

    public function updateSiteSettings(Request $request)
    {
        $fields = [
            'site_name', 'site_tagline', 'site_description',
            'footer_about', 'footer_copyright',
            'social_facebook', 'social_instagram', 'social_twitter', 'social_linkedin',
            'social_youtube', 'social_tiktok', 'social_pinterest',
            'contact_email', 'contact_phone', 'contact_address',
            'announcement_text',
            'about_us_video_url',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                Setting::set($field, $request->input($field), 'string', 'homepage');
            }
        }

        if ($request->hasFile('site_logo')) {
            $path = $request->file('site_logo')->store('branding', 'public');
            Setting::set('site_logo', $path, 'string', 'homepage');
        }

        // About Us video upload — saved path overrides the URL field.
        if ($request->hasFile('about_us_video_file')) {
            $videoPath = $request->file('about_us_video_file')->store('storefront/about', 'public');
            Setting::set('about_us_video_url', 'storage/' . $videoPath, 'string', 'homepage');
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

    public function storeHeroBanner(Request $request)
    {
        // A banner needs a video or an image, not necessarily both. When a video
        // is supplied the image becomes optional and acts as the poster frame.
        $request->validate([
            'name' => 'nullable|string|max:255',
            'image' => 'required_without:video|nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
            'video' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime|max:'.self::MAX_VIDEO_KB,
            'link' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'overlay_style' => 'nullable|string|in:' . implode(',', array_keys(Banner::OVERLAY_STYLES)),
        ], [
            'image.required_without' => 'Upload an image, or a video to use instead.',
            'video.mimetypes' => 'The video must be an MP4, WebM or MOV file.',
            'video.max' => 'The video may not be larger than '.(self::MAX_VIDEO_KB / 1024).' MB.',
        ]);

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
        $request->validate([
            'name' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
            'video' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime|max:'.self::MAX_VIDEO_KB,
            'link' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'overlay_style' => 'nullable|string|in:' . implode(',', array_keys(Banner::OVERLAY_STYLES)),
        ], [
            'video.mimetypes' => 'The video must be an MP4, WebM or MOV file.',
            'video.max' => 'The video may not be larger than '.(self::MAX_VIDEO_KB / 1024).' MB.',
        ]);

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
            'order' => 'required|array',
            'order.*' => 'integer|exists:banners,id',
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
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $data = $request->only(['title', 'subtitle', 'button_text', 'button_link']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->has('background_color')) {
            $data['background_color'] = $request->input('background_color');
        }

        if ($request->has('text_color')) {
            $data['text_color'] = $request->input('text_color');
        }

        if ($request->has('content')) {
            $data['content'] = $request->input('content');
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

    public function reorderSections(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:homepage_sections,id',
        ]);

        foreach ($request->order as $position => $id) {
            HomepageSection::where('id', $id)->update(['position' => $position]);
        }

        Cache::flush();
        return response()->json(['success' => true]);
    }

    // Testimonials
    public function testimonials()
    {
        $testimonials = Testimonial::ordered()->get();
        return view('admin.homepage.testimonials', compact('testimonials'));
    }

    public function storeTestimonial(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
            'product_name' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:2048',
        ]);

        $data = $request->only(['name', 'title', 'content', 'rating', 'product_name']);
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
        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
            'product_name' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:2048',
        ]);

        $data = $request->only(['name', 'title', 'content', 'rating', 'product_name']);

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
    // Shop It Your Way — Size / Price / Shade filter items
    // ============================================================
    public function shopFilters()
    {
        $items = ShopFilterItem::ordered()->get()->groupBy('type');
        return view('admin.homepage.shop-filters', compact('items'));
    }

    public function storeShopFilter(Request $request)
    {
        $data = $request->validate([
            'type'         => 'required|in:size,price,shade',
            'label'        => 'required|string|max:120',
            'sub_label'    => 'nullable|string|max:120',
            'shade_hex'    => 'nullable|string|max:9',
            'query_string' => 'nullable|string|max:255',
        ]);
        $data['position']  = (ShopFilterItem::where('type', $data['type'])->max('position') ?? 0) + 1;
        $data['is_active'] = true;
        ShopFilterItem::create($data);
        Cache::flush();
        return back()->with('success', 'Filter item added.');
    }

    public function updateShopFilter(Request $request, ShopFilterItem $shopFilter)
    {
        $data = $request->validate([
            'type'         => 'required|in:size,price,shade',
            'label'        => 'required|string|max:120',
            'sub_label'    => 'nullable|string|max:120',
            'shade_hex'    => 'nullable|string|max:9',
            'query_string' => 'nullable|string|max:255',
        ]);
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
    // Our Qualities — 6 quality blocks on home page dark section
    // ============================================================
    public function qualities()
    {
        $qualities = Quality::ordered()->get();
        return view('admin.homepage.qualities', compact('qualities'));
    }

    public function storeQuality(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string|max:500',
        ]);
        $data['position']  = (Quality::max('position') ?? 0) + 1;
        $data['is_active'] = true;
        Quality::create($data);
        Cache::flush();
        return back()->with('success', 'Quality added.');
    }

    public function updateQuality(Request $request, Quality $quality)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string|max:500',
        ]);
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
        $request->validate([
            'location' => 'required|string',
            'label' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:navigation_menus,id',
        ]);

        NavigationMenu::create([
            'location' => $request->location,
            'label' => $request->label,
            'url' => $request->url,
            'parent_id' => $request->parent_id,
            'position' => NavigationMenu::where('location', $request->location)->max('position') + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Menu item added successfully.');
    }

    public function updateNavItem(Request $request, NavigationMenu $menu)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'url' => 'required|string|max:255',
        ]);

        $menu->update($request->only(['label', 'url']));

        return back()->with('success', 'Menu item updated successfully.');
    }

    public function deleteNavItem(NavigationMenu $menu)
    {
        $menu->delete();
        return back()->with('success', 'Menu item deleted successfully.');
    }
}
