<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The storefront's two marketing popups, and the one place their copy lives.
 *
 *   - offer popup: the newsletter capture on the homepage
 *   - exit popup:  the discount-code offer shown on the way out, site-wide
 *
 * Both are stored in `settings`, and their defaults used to be written inline
 * as the second argument of each Setting::get() call in the blade partials.
 * The admin screen that edits them would have had to repeat every one of those
 * defaults, and Setting::get() treats a blank stored value as unset - so a
 * field cleared in the admin falls back to the default rather than going empty.
 * With two copies of the defaults, the form and the storefront would eventually
 * have disagreed about what "blank" means. There is one copy, here.
 */
class PopupSettings
{
    public const OFFER = 'offer_popup';
    public const EXIT = 'exit_popup';

    public const GROUPS = [self::OFFER, self::EXIT];

    /**
     * Setting key => default, grouped. Keys are prefixed with their group, so
     * the short name a template uses ('title') is the key minus that prefix.
     *
     * Values are strings because that is what the settings table stores and
     * what the admin form posts back; the readers below cast.
     */
    public const DEFAULTS = [
        self::OFFER => [
            'offer_popup_enabled'  => '1',
            'offer_popup_title'    => 'Unlock 10% Off Your First Order',
            'offer_popup_subtitle' => 'Join the Karmaa Kulture list for early access to new drops, private sales and styling notes.',
            'offer_popup_image'    => '',
        ],
        self::EXIT => [
            'exit_popup_enabled'  => '1',
            'exit_popup_title'    => "Wait - Don't Miss 10% Off",
            'exit_popup_subtitle' => 'Complete your order now and save. Apply the code below at checkout before it expires.',
            'exit_popup_code'     => 'KARMAA10',
            'exit_popup_minutes'  => '10',
            'exit_popup_image'    => '',
        ],
    ];

    /** Every popup key with its default, flattened across both groups. */
    public static function defaults(): array
    {
        return array_merge(...array_values(self::DEFAULTS));
    }

    /** The group a key belongs to, for Setting::updateOrCreate(). */
    public static function groupFor(string $key): string
    {
        foreach (self::DEFAULTS as $group => $keys) {
            if (array_key_exists($key, $keys)) {
                return $group;
            }
        }

        return self::OFFER;
    }

    /**
     * One popup's settings, resolved and cast, keyed without the group prefix:
     * enabled (bool), title, subtitle, image (a URL), and for the exit popup
     * code and minutes.
     */
    public static function all(string $group): array
    {
        $values = [];

        foreach (self::DEFAULTS[$group] as $key => $default) {
            $short = substr($key, strlen($group) + 1);
            $values[$short] = Setting::get($key, $default);
        }

        // getBool rather than a cast: a row seeded with type 'boolean' comes
        // back as a real bool while one written by the settings screen comes
        // back as the string '0', and (bool) '0' and (bool) false only agree
        // by accident of PHP's string rules.
        $values['enabled'] = Setting::getBool($group.'_enabled', self::DEFAULTS[$group][$group.'_enabled'] === '1');

        $values['image'] = self::imageUrl((string) $values['image']);

        if (isset($values['minutes'])) {
            // A zero or negative countdown would render "0:00" and expire the
            // offer the instant the popup opened.
            $values['minutes'] = max(1, (int) $values['minutes']);
        }

        return $values;
    }

    /**
     * A stored image as something an <img src> can use. Uploads are saved as a
     * path on the public disk ("popups/x.jpg"); a value that is already a URL
     * or an absolute path is passed through untouched, so a CDN address typed
     * straight into the database keeps working.
     */
    public static function imageUrl(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'http') || str_starts_with($value, '/')) {
            return $value;
        }

        return asset_v('storage/'.$value);
    }
}
