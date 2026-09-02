<?php

namespace Tests\Feature\Admin;

use App\Models\Coupon;
use App\Models\Setting;
use App\Models\User;
use App\Support\PopupSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admin > Settings > Popups, and the two storefront partials it drives.
 *
 * Both popups read their copy, image, coupon code and countdown out of
 * `settings`, but nothing ever wrote those keys: there was no screen for them,
 * so every wording change was a code change and a deploy.
 */
class PopupSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** The values the form posts when nothing in particular is being tested. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'offer_popup_enabled'  => '1',
            'offer_popup_title'    => 'Unlock 10% Off Your First Order',
            'offer_popup_subtitle' => 'Join the list.',
            'exit_popup_enabled'   => '1',
            'exit_popup_title'     => "Wait - Don't Miss 10% Off",
            'exit_popup_subtitle'  => 'Complete your order now.',
            'exit_popup_code'      => 'KARMAA10',
            'exit_popup_minutes'   => '10',
        ], $overrides);
    }

    public function test_the_page_renders_with_the_defaults_the_storefront_uses(): void
    {
        $response = $this->actingAs($this->admin(), 'admin')->get(route('admin.settings.popups'));

        $response->assertOk();
        // An unsaved setting must show its fallback, not an empty box - the
        // storefront is showing that fallback right now.
        $response->assertSee(PopupSettings::DEFAULTS[PopupSettings::OFFER]['offer_popup_title'], false);
        $response->assertSee(PopupSettings::DEFAULTS[PopupSettings::EXIT]['exit_popup_code'], false);
    }

    public function test_saving_changes_what_the_storefront_renders(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.popups.update'), $this->payload([
                'offer_popup_title'  => 'Ten Percent Off, Just For You',
                'exit_popup_title'   => 'Hold On A Second',
                'exit_popup_code'    => 'stay15',
                'exit_popup_minutes' => '25',
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['key' => 'offer_popup_title', 'value' => 'Ten Percent Off, Just For You']);
        // Codes are typed at checkout in caps and matched there in caps.
        $this->assertDatabaseHas('settings', ['key' => 'exit_popup_code', 'value' => 'STAY15']);

        $this->assertStringContainsString('Ten Percent Off, Just For You', view('partials.offer-popup')->render());

        $exit = view('partials.exit-popup')->render();
        $this->assertStringContainsString('Hold On A Second', $exit);
        $this->assertStringContainsString('STAY15', $exit);
        $this->assertStringContainsString('25:00', $exit);
    }

    public function test_turning_a_popup_off_takes_it_off_the_page(): void
    {
        $admin = $this->admin();

        // An unchecked box submits nothing at all, so "off" is the absence of
        // the key - not a '0' the form helpfully sends.
        $payload = $this->payload();
        unset($payload['offer_popup_enabled'], $payload['exit_popup_enabled']);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.popups.update'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'offer_popup_enabled', 'value' => '0']);
        $this->assertSame('', trim(view('partials.offer-popup')->render()));
        $this->assertSame('', trim(view('partials.exit-popup')->render()));

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.popups.update'), $this->payload())
            ->assertRedirect();

        $this->assertStringContainsString('offer-popup-title', view('partials.offer-popup')->render());
    }

    public function test_a_blank_sub_heading_falls_back_to_the_default_wording(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.popups.update'), $this->payload(['offer_popup_subtitle' => '']))
            ->assertRedirect();

        $this->assertStringContainsString(
            PopupSettings::DEFAULTS[PopupSettings::OFFER]['offer_popup_subtitle'],
            view('partials.offer-popup')->render(),
        );
    }

    public function test_a_code_that_could_break_out_of_the_markup_is_rejected(): void
    {
        // The code is rendered into the popup's x-data attribute.
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.popups.update'), $this->payload(['exit_popup_code' => "X'), alert(1), ("]))
            ->assertSessionHasErrors('exit_popup_code');

        $this->assertDatabaseMissing('settings', ['key' => 'exit_popup_code', 'value' => "X'), alert(1), ("]);
    }

    public function test_the_countdown_must_be_a_sane_number_of_minutes(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.popups.update'), $this->payload(['exit_popup_minutes' => '0']))
            ->assertSessionHasErrors('exit_popup_minutes');
    }

    public function test_an_image_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.popups.update'), $this->payload([
                'offer_popup_image' => UploadedFile::fake()->image('offer.jpg', 600, 660),
            ]))
            ->assertRedirect();

        $stored = Setting::where('key', 'offer_popup_image')->value('value');
        $this->assertStringStartsWith('popups/', $stored);
        Storage::disk('public')->assertExists($stored);
        $this->assertStringContainsString($stored, view('partials.offer-popup')->render());

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.popups.update'), $this->payload([
                'offer_popup_image_remove' => '1',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'offer_popup_image', 'value' => '']);
        Storage::disk('public')->assertMissing($stored);
    }

    public function test_editing_the_wording_leaves_the_images_alone(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.popups.update'), $this->payload([
                'offer_popup_image' => UploadedFile::fake()->image('offer.jpg', 600, 660),
                'exit_popup_image'  => UploadedFile::fake()->image('exit.jpg', 600, 660),
            ]))
            ->assertRedirect();

        $offer = Setting::where('key', 'offer_popup_image')->value('value');
        $exit = Setting::where('key', 'exit_popup_image')->value('value');

        // One Save covers both popups and a file input posts nothing when no
        // file is chosen. Treating that as "clear it" would wipe both images
        // every time a word was edited.
        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.popups.update'), $this->payload(['offer_popup_title' => 'New Words Entirely']))
            ->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'offer_popup_image', 'value' => $offer]);
        $this->assertDatabaseHas('settings', ['key' => 'exit_popup_image', 'value' => $exit]);
        Storage::disk('public')->assertExists($offer);
        Storage::disk('public')->assertExists($exit);
    }

    /**
     * The warning's own tag, so a test can tell "shown" from "in the markup".
     *
     * It is rendered either way now and hidden with x-show, which is what lets
     * picking a real code out of the list clear it without a round trip.
     */
    private function codeWarningTag(string $html): string
    {
        preg_match('/<div role="status" x-show="! known"[^>]*>/', $html, $found);

        return $found[0] ?? '';
    }

    public function test_a_code_with_no_coupon_behind_it_is_flagged(): void
    {
        $admin = $this->admin();

        $unknown = $this->actingAs($admin, 'admin')->get(route('admin.settings.popups'));
        $unknown->assertSee('No coupon matches this code', false);
        $tag = $this->codeWarningTag($unknown->getContent());
        $this->assertNotSame('', $tag);
        $this->assertStringNotContainsString('display: none', $tag);

        Coupon::create([
            'code'  => 'KARMAA10',
            'name'  => 'Exit intent 10% off',
            'type'  => 'percentage',
            'value' => 10,
        ]);

        $known = $this->actingAs($admin, 'admin')->get(route('admin.settings.popups'));
        $this->assertStringContainsString('display: none', $this->codeWarningTag($known->getContent()));
    }

    /**
     * The code box used to be an <input list> over a <datalist>, which the
     * browser decorates with a dropdown arrow that opens its own suggestion
     * list - and that list filters itself down to nothing as soon as anything
     * is typed, so the field advertised a picker and behaved like a text box.
     */
    public function test_the_code_box_offers_every_coupon_that_exists(): void
    {
        foreach (['KARMAA10', 'WELCOME15'] as $code) {
            Coupon::create(['code' => $code, 'name' => $code, 'type' => 'percentage', 'value' => 10]);
        }

        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.settings.popups'))
            ->getContent();

        $this->assertStringNotContainsString('<datalist', $html);
        $this->assertStringContainsString('id="exit-popup-code-list"', $html);
        $this->assertStringContainsString('Show the discount codes that exist', $html);

        // Both codes reach the component, whatever is in the box.
        preg_match('/x-data="kkCodePicker\((.+?)\)"/', $html, $found);
        $this->assertStringContainsString('KARMAA10', $found[1] ?? '');
        $this->assertStringContainsString('WELCOME15', $found[1] ?? '');
    }

    /**
     * No coupons, no arrow: a control that opens an empty list is the bug this
     * replaced, so the box has to look like the plain text field it then is.
     */
    public function test_the_code_box_has_no_arrow_when_there_are_no_coupons(): void
    {
        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.settings.popups'))
            ->getContent();

        $this->assertStringNotContainsString('id="exit-popup-code-list"', $html);
        $this->assertStringNotContainsString('Show the discount codes that exist', $html);
        // Still a required text box that takes a code no coupon exists for yet.
        $this->assertStringContainsString('name="exit_popup_code"', $html);
    }

    public function test_a_staff_member_without_settings_access_cannot_edit_the_popups(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff, 'admin')
            ->get(route('admin.settings.popups'))
            ->assertForbidden();
    }
}
