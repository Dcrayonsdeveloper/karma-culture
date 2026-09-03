<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Setting;
use App\Rules\ValidationRules as V;
use App\Support\PopupSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * The keys this screen owns, and the ONE key each field really writes.
     *
     * The contact three are the reason this list exists. This screen used to
     * save site_email / site_phone / site_address, which nothing on the
     * storefront reads - the footer, the contact page and the WhatsApp button
     * all read contact_email / contact_phone / contact_address, which are
     * written by Online Store -> Site Settings. So filling this form in changed
     * nothing a customer could see, and the two screens disagreed in silence.
     */
    private const GENERAL_KEYS = [
        'site_name' => 'site_name',
        'site_tagline' => 'site_tagline',
        'site_email' => 'contact_email',
        'site_phone' => 'contact_phone',
        'site_address' => 'contact_address',
        'timezone' => 'timezone',
        'date_format' => 'date_format',
        'currency' => 'currency',
        'currency_symbol' => 'currency_symbol',
        'currency_position' => 'currency_position',
    ];

    public function general(): View
    {
        // By key, not by group. site_name is written by the other settings
        // screen under group "homepage", so a group query came back without it
        // and the field rendered empty next to a storefront that was already
        // showing the name - which reads as "the setting is not saved".
        $settings = collect(self::GENERAL_KEYS)
            ->map(fn ($key) => Setting::get($key, ''))
            ->all();

        return view('admin.settings.general', compact('settings'));
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_tagline' => 'nullable|string|max:255',
            'site_email' => 'required|email',
            'site_phone' => 'nullable|string|max:20',
            'site_address' => 'nullable|string|max:500',
            // Nullable, not required: the Regional Settings card is gone from
            // this form, so these keys no longer arrive with a save. Absent
            // means "leave what is stored" - the loop below only writes the
            // fields that were actually submitted.
            'timezone' => 'nullable|string',
            'date_format' => 'nullable|string',
            'currency' => 'nullable|string|size:3',
            'currency_symbol' => 'nullable|string|max:5',
            'currency_position' => 'nullable|in:before,after',
        ]);

        foreach ($validated as $field => $value) {
            // The field name is the form's; the setting key is the site's.
            $key = self::GENERAL_KEYS[$field] ?? $field;

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'general']
            );
            Cache::forget("setting.{$key}");
        }

        Cache::forget('currency_config');

        // The footer, the header and the assistant all read these through
        // Setting::get(), which caches per key for an hour, and the group
        // caches are keyed separately again.
        Cache::forget('settings.group.general');
        Cache::forget('settings.group.homepage');

        return back()->with('success', 'General settings updated successfully.');
    }

    public function shipping(): View
    {
        $settings = Setting::where('group', 'shipping')->pluck('value', 'key');

        return view('admin.settings.shipping', compact('settings'));
    }

    public function updateShipping(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shiprocket_auth_mode'       => 'nullable|in:token,credentials',
            'shiprocket_api_token'       => 'nullable|string|max:1000',
            'shiprocket_email'           => 'nullable|email|max:255',
            'shiprocket_password'        => 'nullable|string|max:255',
            'shiprocket_pickup_location' => 'nullable|string|max:255',
            'shiprocket_channel_id'      => 'nullable|string|max:50',
            'free_shipping_threshold'    => 'nullable|numeric|min:0',
            'return_window_days'         => 'nullable|integer|min:0|max:365',
            'return_min_hours'           => 'nullable|integer|min:0|max:168',
            'flat_rate_amount'           => 'nullable|numeric|min:0',
            'local_pickup_address'       => 'nullable|string|max:500',
            'shipping_origin_country'    => 'required|string|size:2',
            'shipping_origin_state'      => 'nullable|string',
            'shipping_origin_zip'        => 'nullable|string|max:20',
        ]);

        // Only one Shiprocket credential set can be in play: getToken() returns
        // a stored API token if there is one and never looks at the email and
        // password. Hiding the other panel with x-show still submitted its
        // fields, so picking "Email & Password" left the old token in place and
        // silently kept using it. Clear whichever set the admin did not choose.
        if (($validated['shiprocket_auth_mode'] ?? null) === 'credentials') {
            $validated['shiprocket_api_token'] = '';
        } elseif (($validated['shiprocket_auth_mode'] ?? null) === 'token') {
            $validated['shiprocket_email'] = '';
            $validated['shiprocket_password'] = '';
        }

        // Boolean toggles
        foreach (['shiprocket_enabled', 'free_shipping_enabled', 'flat_rate_enabled', 'local_pickup_enabled'] as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0', 'group' => 'shipping']
            );
            Cache::forget("setting.{$key}");
        }

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'shipping']
            );
            Cache::forget("setting.{$key}");
        }
        Cache::forget('settings.group.shipping');

        // Blanking a credential to disconnect is as much a change as setting
        // one, and so is switching auth mode - filled() alone missed both and
        // left a working token cached for up to nine days.
        if ($request->has(['shiprocket_api_token', 'shiprocket_email', 'shiprocket_password'])
            || $request->filled('shiprocket_auth_mode')) {
            \App\Services\ShiprocketService::clearToken();
        }

        return back()->with('success', 'Shipping settings updated successfully.');
    }

    public function tax(): View
    {
        $settings = Setting::where('group', 'tax')->pluck('value', 'key');

        return view('admin.settings.tax', compact('settings'));
    }

    public function updateTax(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tax_calculation' => 'in:exclusive,inclusive',
            'tax_based_on' => 'in:billing,shipping,store',
            'tax_display_cart' => 'in:excluding,including',
            'tax_display_checkout' => 'in:excluding,including',
        ]);

        // Boolean toggles - an unchecked checkbox submits nothing, so reading
        // these out of $validated meant taxes could be switched on but never
        // back off. request->boolean() sees the absent key as false.
        foreach (['tax_enabled', 'tax_round_at_subtotal'] as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0', 'type' => 'boolean', 'group' => 'tax']
            );
            Cache::forget("setting.{$key}");
        }

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'tax']
            );
            Cache::forget("setting.{$key}");
        }
        Cache::forget('settings.group.tax');

        return back()->with('success', 'Tax settings updated successfully.');
    }

    public function seo(): View
    {
        $settings = Setting::where('group', 'seo')->pluck('value', 'key');

        // Show what is actually being served, not an empty box: /robots.txt
        // falls back to a route in web.php when no static file exists.
        $robotsPath = public_path('robots.txt');
        $robotsIsCustom = is_file($robotsPath);
        $robotsTxt = $robotsIsCustom
            ? (string) file_get_contents($robotsPath)
            : (string) ($settings['robots_txt'] ?? '');

        return view('admin.settings.seo', compact('settings', 'robotsTxt', 'robotsIsCustom'));
    }

    public function updateSeo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meta_title'                         => 'nullable|string|max:70',
            'meta_description'                   => 'nullable|string|max:160',
            'meta_keywords'                      => 'nullable|string|max:255',
            'og_image'                           => 'nullable|url|max:500',
            'google_analytics_id'                => ['nullable', 'string', 'regex:/^(G-[A-Z0-9]+)?$/i'],
            'google_tag_manager_id'              => ['nullable', 'string', 'regex:/^(GTM-[A-Z0-9]+)?$/i'],
            'facebook_pixel_id'                  => ['nullable', 'string', 'regex:/^[0-9]*$/'],
            'google_search_console_verification' => ['nullable', 'string', 'max:200', 'regex:/^[A-Za-z0-9_=-]*$/'],
            'twitter_site'                       => ['nullable', 'string', 'max:50', 'regex:/^@?[A-Za-z0-9_]*$/'],
            'robots_txt'                         => 'nullable|string|max:5000',
        ]);

        // robots.txt.
        //
        // /robots.txt is normally served by a route in web.php that builds the
        // file from APP_URL. Writing public/robots.txt shadows that route for
        // good, because the web server hands out the static file before the
        // request ever reaches PHP - so this needs to be deliberate and, more
        // importantly, reversible. Clearing the box used to be a silent no-op:
        // empty input arrives as null, isset() skipped the branch, and the
        // stale file kept being served with no way back to the dynamic route.
        $robots = $request->input('robots_txt');
        $robotsPath = public_path('robots.txt');

        if ($robots !== null && trim($robots) !== '') {
            $validated['robots_txt'] = strip_tags($robots);

            if (@file_put_contents($robotsPath, $validated['robots_txt']) === false) {
                return back()
                    ->withInput()
                    ->withErrors(['robots_txt' => 'Could not write public/robots.txt. Check the directory is writable.']);
            }
        } else {
            $validated['robots_txt'] = '';

            // Emptying the box hands /robots.txt back to the dynamic route.
            if (is_file($robotsPath) && ! @unlink($robotsPath)) {
                return back()
                    ->withInput()
                    ->withErrors(['robots_txt' => 'Could not remove public/robots.txt. Check the file is writable.']);
            }
        }

        // Normalize Twitter handle - ensure it starts with @
        if (!empty($validated['twitter_site']) && !str_starts_with($validated['twitter_site'], '@')) {
            $validated['twitter_site'] = '@' . $validated['twitter_site'];
        }

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'seo']
            );
            Cache::forget("setting.{$key}");
        }

        return back()->with('success', 'SEO settings updated successfully.');
    }

    /**
     * The two storefront popups - the homepage newsletter offer and the
     * exit-intent discount code.
     *
     * Their keys have been read out of `settings` since they were written, but
     * nothing ever wrote them: there was no screen, so changing a word, the
     * coupon code or the countdown meant editing a blade and deploying.
     */
    public function popups(): View
    {
        $settings = Setting::whereIn('group', PopupSettings::GROUPS)->pluck('value', 'key');

        // A blank row is not a stored value - Setting::get() falls back to the
        // default for it - so the form has to show the default too, or the box
        // would look empty while the storefront showed text.
        foreach (PopupSettings::defaults() as $key => $default) {
            if (! isset($settings[$key]) || $settings[$key] === '') {
                $settings[$key] = $default;
            }
        }

        // The exit popup hands the customer a code to type at checkout, and a
        // code with no coupon behind it fails there, in front of them. Warn
        // here rather than reject: the coupon may be created afterwards.
        $couponCodes = Coupon::orderBy('code')->pluck('code')->all();
        $codeIsKnown = in_array(
            strtoupper((string) $settings['exit_popup_code']),
            array_map('strtoupper', $couponCodes),
            true,
        );

        return view('admin.settings.popups', compact('settings', 'couponCodes', 'codeIsKnown'));
    }

    public function updatePopups(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'offer_popup_title'    => 'required|string|max:120',
            'offer_popup_subtitle' => 'nullable|string|max:400',
            'exit_popup_title'     => 'required|string|max:120',
            'exit_popup_subtitle'  => 'nullable|string|max:400',
            // What a coupon code can be anyway, and it keeps quotes out of the
            // value the popup renders into its x-data attribute.
            'exit_popup_code'      => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/'],
            'exit_popup_minutes'   => 'required|integer|min:1|max:180',
            // nullable, unlike the countdown: this field was added after the
            // form shipped, and a required rule would 422 any client posting
            // the older payload. Absent means "leave the horizon alone".
            'exit_popup_claim_days' => 'nullable|integer|min:1|max:365',
            'offer_popup_image'    => V::image(required: false, maxKb: 2048),
            'exit_popup_image'     => V::image(required: false, maxKb: 2048),
        ], [
            'exit_popup_code.regex' => 'The discount code can only contain letters, numbers, hyphens and underscores.',
        ]);

        $values = [
            'offer_popup_title'    => $validated['offer_popup_title'],
            'offer_popup_subtitle' => $validated['offer_popup_subtitle'] ?? '',
            'exit_popup_title'     => $validated['exit_popup_title'],
            'exit_popup_subtitle'  => $validated['exit_popup_subtitle'] ?? '',
            'exit_popup_code'      => strtoupper($validated['exit_popup_code']),
            'exit_popup_minutes'   => (string) $validated['exit_popup_minutes'],
        ];

        // Only written when it was actually submitted. This field arrived after
        // the form shipped, so its rule is nullable rather than required - and
        // writing a fallback for an absent field would let any older payload
        // silently reset a horizon the admin had already chosen.
        if (isset($validated['exit_popup_claim_days'])) {
            $values['exit_popup_claim_days'] = (string) $validated['exit_popup_claim_days'];
        }

        // An unchecked box submits nothing at all, so the toggles are read off
        // the request rather than the validated set.
        foreach (['offer_popup_enabled', 'exit_popup_enabled'] as $key) {
            $values[$key] = $request->boolean($key) ? '1' : '0';
        }

        foreach (['offer_popup_image', 'exit_popup_image'] as $key) {
            $image = $this->popupImage($request, $key);

            if ($image !== null) {
                $values[$key] = $image;
            }
        }

        foreach ($values as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => PopupSettings::groupFor($key),
                    'type'  => str_ends_with($key, '_enabled') ? 'boolean' : 'string',
                ]
            );
            // Setting::get() caches every key for an hour; without this the
            // storefront keeps showing the old copy after a save.
            Cache::forget("setting.{$key}");
        }

        foreach (PopupSettings::GROUPS as $group) {
            Cache::forget("settings.group.{$group}");
        }

        return back()->with('success', 'Popup settings updated successfully.');
    }

    /**
     * The new value for one popup image setting, or null to leave it as it is.
     *
     * A file input submits nothing when no file is chosen, and here that has to
     * mean "keep the current image": one Save covers both popups, so treating
     * an absent file as "clear it" would wipe the other popup's image every
     * time a word was edited. Clearing is a checkbox of its own.
     */
    private function popupImage(Request $request, string $key): ?string
    {
        $current = (string) Setting::get($key, '');

        if ($request->boolean($key.'_remove')) {
            $this->deletePopupImage($current);

            return '';
        }

        if (! $request->hasFile($key)) {
            return null;
        }

        $path = $request->file($key)->store('popups', 'public');
        $this->deletePopupImage($current);

        return $path;
    }

    /**
     * Only files this screen uploaded are deleted. A value set by hand to a CDN
     * URL, or to an image shared with something else, is left on disk.
     */
    private function deletePopupImage(string $path): void
    {
        if ($path !== '' && str_starts_with($path, 'popups/')) {
            Storage::disk('public')->delete($path);
        }
    }

    public function productCard(): View
    {
        $settings = Setting::whereIn('group', ['product_card', 'features'])->pluck('value', 'key');

        $defaults = [
            'product_card_quick_view' => '1',
            'product_card_add_to_cart' => '1',
            'product_card_wishlist' => '1',
            'support_tickets_enabled' => '1',
        ];

        foreach ($defaults as $key => $default) {
            if (!isset($settings[$key])) {
                $settings[$key] = $default;
            }
        }

        return view('admin.settings.product-card', compact('settings'));
    }

    public function updateProductCard(Request $request): RedirectResponse
    {
        $productCardFields = ['product_card_quick_view', 'product_card_add_to_cart', 'product_card_wishlist'];
        foreach ($productCardFields as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0', 'type' => 'boolean', 'group' => 'product_card']
            );
            Cache::forget("setting.{$key}");
        }

        $featureFields = ['support_tickets_enabled'];
        foreach ($featureFields as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0', 'type' => 'boolean', 'group' => 'features']
            );
            Cache::forget("setting.{$key}");
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
