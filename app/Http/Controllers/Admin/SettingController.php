<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function general(): View
    {
        $settings = Setting::whereIn('group', ['general', 'store'])->pluck('value', 'key');

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
            'timezone' => 'required|string',
            'date_format' => 'required|string',
            'currency' => 'required|string|size:3',
            'currency_symbol' => 'required|string|max:5',
            'currency_position' => 'required|in:before,after',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'general']
            );
            Cache::forget("setting.{$key}");
        }
        Cache::forget('currency_config');

        return back()->with('success', 'General settings updated successfully.');
    }

    public function payment(): View
    {
        $settings = Setting::where('group', 'payment')->pluck('value', 'key');

        return view('admin.settings.payment', compact('settings'));
    }

    public function updatePayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payu_merchant_key'   => 'nullable|string|max:255',
            'payu_merchant_salt'  => 'nullable|string|max:255',
            'payu_mode'           => 'nullable|in:test,live',
            'cod_instructions'    => 'nullable|string|max:1000',
        ]);

        // Boolean toggles - use request->boolean() so unchecked checkboxes save '0'
        foreach (['payu_enabled', 'cod_enabled'] as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0', 'group' => 'payment']
            );
            // Setting::get() caches each key for an hour; without this forget
            // the storefront kept offering stale payment methods after saving.
            Cache::forget("setting.{$key}");
        }

        // Credential / text fields
        foreach ($validated as $key => $value) {
            // The salt field is deliberately rendered empty, so a blank submit
            // means "unchanged". Without this, opening the page and pressing
            // Save wiped the salt - and checkout drops online payment entirely
            // once it is empty.
            if ($key === 'payu_merchant_salt' && ! $request->filled('payu_merchant_salt')) {
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'payment']
            );
            Cache::forget("setting.{$key}");
        }
        Cache::forget('settings.group.payment');

        return back()->with('success', 'Payment settings updated successfully.');
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

    public function email(): View
    {
        $settings = Setting::where('group', 'email')->pluck('value', 'key');

        return view('admin.settings.email', compact('settings'));
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail_driver' => 'required|in:smtp,sendmail,log',
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            // Same as the PayU salt: the password box renders empty, so a blank
            // submit must leave the stored password alone.
            if ($key === 'mail_password' && ! $request->filled('mail_password')) {
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'email']
            );
            Cache::forget("setting.{$key}");
        }
        Cache::forget('settings.group.email');

        return back()->with('success', 'Email settings updated successfully.');
    }

    /**
     * Send a test message using the saved mail settings.
     *
     * The button used to call a JS alert() that said a test email "would" be
     * sent, which told an admin nothing about whether their SMTP details
     * actually work. This sends a real message to the signed-in admin and
     * surfaces the transport error verbatim when it fails - that error text is
     * the whole point of a test button.
     */
    public function testEmail(Request $request): RedirectResponse
    {
        // The admin area authenticates on the 'admin' guard; the default 'web'
        // guard resolves to null in here.
        $recipient = $request->user('admin')?->email;

        if (! $recipient) {
            return back()->with('error', 'Your admin account has no email address to send a test to.');
        }

        try {
            Mail::raw(
                "This is a test message from {$this->siteName()}.\n\n"
                .'If you are reading it, the mail settings saved in Settings > Email are working.',
                fn ($message) => $message->to($recipient)->subject('Test email from '.$this->siteName())
            );
        } catch (\Throwable $e) {
            Log::warning('Admin test email failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Could not send the test email: '.$e->getMessage());
        }

        return back()->with('success', "Test email sent to {$recipient}.");
    }

    private function siteName(): string
    {
        return (string) (Setting::get('site_name') ?: config('app.name'));
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

    public function integrations(): View
    {
        $settings = Setting::where('group', 'integrations')->pluck('value', 'key');

        return view('admin.settings.integrations', compact('settings'));
    }

    public function updateIntegrations(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Razorpay Webhook
            'razorpay_webhook_secret'            => 'nullable|string|max:255',

            // WhatsApp Business API (Meta)
            'whatsapp_phone_number_id'           => ['nullable', 'string', 'max:30', 'regex:/^[0-9]*$/'],
            'whatsapp_verify_token'              => 'nullable|string|max:255',
            'whatsapp_page_access_token'         => 'nullable|string|max:500',
            'whatsapp_app_secret'                => 'nullable|string|max:255',

            // SMS Gateway
            'sms_provider'                       => 'nullable|in:msg91,twilio,none',
            'sms_api_key'                        => 'nullable|string|max:255',
            'sms_sender_id'                      => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]*$/'],
            'sms_dlt_template_id'                => ['nullable', 'string', 'max:50', 'regex:/^[0-9]*$/'],

            // AI Chatbot
            'anthropic_api_key'                  => ['nullable', 'string', 'max:500', 'regex:/^(sk-ant-[A-Za-z0-9\-_]*)?$/'],
            'anthropic_model'                    => 'nullable|in:claude-haiku-4-5,claude-sonnet-5,claude-opus-5',
            'ai_provider'                        => 'nullable|in:anthropic,gemini',
            // Google issues more than one key format (AIza... and AQ....), so accept
            // any plausible token rather than rejecting a valid key on its prefix.
            'gemini_api_key'                     => ['nullable', 'string', 'max:500', 'regex:/^[A-Za-z0-9._\-]*$/'],
            'gemini_model'                       => 'nullable|in:gemini-3.6-flash,gemini-3.5-flash,gemini-3.1-flash-lite',
            'chatbot_brand_voice'                => 'nullable|string|max:2000',
            'chatbot_extra_instructions'         => 'nullable|string|max:4000',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'integrations']
            );
            Cache::forget("setting.{$key}");
        }
        Cache::forget('settings.group.integrations');

        return back()->with('success', 'Integration settings updated successfully.');
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
