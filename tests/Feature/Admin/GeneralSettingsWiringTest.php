<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Settings -> General was a form that mostly changed nothing.
 *
 * Its contact fields wrote site_email / site_phone / site_address, and nothing
 * on the storefront reads those - the footer, the contact page and the WhatsApp
 * button all read contact_email / contact_phone / contact_address, written by
 * the other settings screen. So filling this form in was silent.
 *
 * It also loaded its values by GROUP while site_name is stored under the
 * "homepage" group, so the Site Name box rendered empty beside a storefront
 * that was already showing the name.
 *
 * And date_format was a required field nothing read.
 */
class GeneralSettingsWiringTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'Karmaa Kulture',
            'site_tagline' => 'Curated fashion',
            'site_email' => 'hello@karmaa.test',
            'site_phone' => '9876543210',
            'site_address' => '12 Rohini, Delhi',
            'timezone' => 'Asia/Kolkata',
            'date_format' => 'd/m/Y',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'currency_position' => 'before',
        ], $overrides);
    }

    private function save(array $overrides = []): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.settings.general.update'), $this->payload($overrides))
            ->assertSessionHasNoErrors();
    }

    public function test_the_contact_details_land_on_the_keys_the_storefront_reads(): void
    {
        $this->save();

        $this->assertSame('hello@karmaa.test', Setting::get('contact_email'));
        $this->assertSame('9876543210', Setting::get('contact_phone'));
        $this->assertSame('12 Rohini, Delhi', Setting::get('contact_address'));
    }

    public function test_the_footer_shows_what_was_typed_here(): void
    {
        $this->save();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('hello@karmaa.test', $html, 'The footer is not reading this screen.');
        $this->assertStringContainsString('12 Rohini, Delhi', $html);
    }

    /**
     * The form has to open on the values that are actually in force, or an
     * admin retypes a name that was never missing - or worse, saves the blank.
     */
    public function test_the_form_shows_a_name_saved_by_the_other_screen(): void
    {
        Setting::updateOrCreate(
            ['key' => 'site_name'],
            ['value' => 'Karmaa Kulture', 'group' => 'homepage', 'type' => 'string']
        );

        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.settings.general'))->assertOk()->getContent();

        $this->assertStringContainsString('value="Karmaa Kulture"', $html);
    }

    public function test_the_two_settings_screens_no_longer_disagree(): void
    {
        $this->save(['site_email' => 'first@karmaa.test']);

        // What the other screen reads back is what this one wrote.
        $this->assertSame('first@karmaa.test', Setting::get('contact_email'));

        $this->save(['site_email' => 'second@karmaa.test']);

        $this->assertSame('second@karmaa.test', Setting::get('contact_email'));
        $this->assertSame(
            1,
            Setting::where('key', 'contact_email')->count(),
            'A second row for the same setting means the screens will disagree again.'
        );
    }

    public function test_the_chosen_date_format_is_what_customers_see(): void
    {
        $this->save(['date_format' => 'd/m/Y']);

        $this->assertSame('25/12/2026', format_date('2026-12-25'));

        // No cache clearing here on purpose: updateGeneral() forgets the key
        // it wrote, and if it stopped doing that the admin would save a new
        // format and go on seeing the old one for an hour.
        $this->save(['date_format' => 'Y-m-d']);

        $this->assertSame('2026-12-25', format_date('2026-12-25'));
    }

    /**
     * An order that has not shipped has no shipped date, and every caller
     * should not have to guard for it.
     */
    public function test_a_missing_date_renders_as_nothing(): void
    {
        $this->assertSame('', format_date(null));
        $this->assertSame('', format_date(''));
    }

    public function test_the_currency_symbol_still_reaches_prices(): void
    {
        $this->save(['currency_symbol' => '$', 'currency_position' => 'after']);

        $this->assertSame('1,500.00$', format_price(1500));
    }
}
