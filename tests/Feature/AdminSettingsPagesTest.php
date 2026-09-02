<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every screen under /admin/settings should render. Several of these used to
 * 500 or silently misbehave: resource routes registered a show() the
 * controllers never defined, and the shipping-rate screens had no route into
 * them at all.
 */
class AdminSettingsPagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public static function settingsTabs(): array
    {
        return [
            'general' => ['admin.settings.general'],
            'payment' => ['admin.settings.payment'],
            'shipping' => ['admin.settings.shipping'],
            'tax' => ['admin.settings.tax'],
            'email' => ['admin.settings.email'],
            'seo' => ['admin.settings.seo'],
            'features' => ['admin.settings.product-card'],
            'popups' => ['admin.settings.popups'],
            'integrations' => ['admin.settings.integrations'],
            'currencies' => ['admin.settings.currencies.index'],
            'currency create' => ['admin.settings.currencies.create'],
            'roles' => ['admin.settings.roles.index'],
            'role create' => ['admin.settings.roles.create'],
            'tax rates' => ['admin.settings.tax-rates.index'],
            'tax rate create' => ['admin.settings.tax-rates.create'],
            'shipping zones' => ['admin.settings.shipping-zones.index'],
            'zone create' => ['admin.settings.shipping-zones.create'],
        ];
    }

    /** @dataProvider settingsTabs */
    public function test_settings_page_renders(string $routeName): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route($routeName))
            ->assertOk();
    }

    public function test_every_tab_marks_exactly_one_tab_active(): void
    {
        $response = $this->actingAs($this->admin(), 'admin')->get(route('admin.settings.tax'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'aria-current="page"'));
        $this->assertStringContainsString('settings-tabs__tab is-active', $response->getContent());
    }

    public function test_tax_toggles_can_be_switched_off(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->put(route('admin.settings.tax.update'), [
            'tax_enabled' => '1',
            'tax_calculation' => 'exclusive',
            'tax_based_on' => 'shipping',
            'tax_display_cart' => 'excluding',
            'tax_display_checkout' => 'excluding',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'tax_enabled', 'value' => '1']);

        // The unchecked box submits nothing at all - this used to leave the
        // stored value at '1' forever.
        $this->actingAs($admin, 'admin')->put(route('admin.settings.tax.update'), [
            'tax_calculation' => 'exclusive',
            'tax_based_on' => 'shipping',
            'tax_display_cart' => 'excluding',
            'tax_display_checkout' => 'excluding',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'tax_enabled', 'value' => '0']);
    }

    public function test_integrations_tab_saves(): void
    {
        // Every option in the model picker was outside the controller's in:
        // rule, so this request used to bounce with a validation error.
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.integrations.update'), [
                'ai_provider' => 'anthropic',
                'anthropic_model' => 'claude-opus-5',
                'sms_provider' => 'none',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'key' => 'anthropic_model',
            'value' => 'claude-opus-5',
        ]);
    }

    public function test_shipping_zone_can_be_deactivated_and_takes_regions(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('admin.settings.shipping-zones.store'), [
            'name' => 'South',
            'regions' => "Karnataka\nKerala",
            'is_active' => '1',
        ])->assertRedirect();

        $zone = \App\Models\ShippingZone::firstWhere('name', 'South');
        $this->assertNotNull($zone);
        $this->assertSame(['Karnataka', 'Kerala'], $zone->regions);
        $this->assertTrue($zone->is_active);

        $this->actingAs($admin, 'admin')->put(route('admin.settings.shipping-zones.update', $zone), [
            'name' => 'South',
            'regions' => "Karnataka\nKerala",
        ])->assertRedirect();

        $this->assertFalse($zone->fresh()->is_active);
    }

    public function test_blank_optional_fields_on_a_rate_do_not_error(): void
    {
        $admin = $this->admin();
        $zone = \App\Models\ShippingZone::create(['name' => 'Zone', 'is_active' => true]);

        // min_order and the estimate columns are NOT NULL with defaults; an
        // empty input arrives as null and used to blow up the insert.
        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.shipping-zones.rates.store', $zone), [
                'name' => 'Standard',
                'type' => 'flat',
                'rate' => '99',
                'min_order' => '',
                'estimated_days_min' => '',
                'estimated_days_max' => '',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('shipping_rates', ['name' => 'Standard', 'zone_id' => $zone->id]);
    }

    public function test_rate_screens_are_reachable_from_the_zone_editor(): void
    {
        $zone = \App\Models\ShippingZone::create(['name' => 'Zone', 'is_active' => true]);
        $rate = $zone->rates()->create(['name' => 'Standard', 'type' => 'flat', 'rate' => 99]);

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.settings.shipping-zones.edit', $zone));

        $response->assertOk();
        $response->assertSee(route('admin.settings.shipping-zones.rates.create', $zone), false);
        $response->assertSee(route('admin.settings.rates.edit', $rate), false);
    }

    public function test_orphaned_settings_pages_are_linked(): void
    {
        $admin = $this->admin();

        $general = $this->actingAs($admin, 'admin')->get(route('admin.settings.general'));
        $general->assertSee(route('admin.settings.currencies.index'), false);
        $general->assertSee(route('admin.settings.roles.index'), false);

        $this->actingAs($admin, 'admin')->get(route('admin.settings.shipping'))
            ->assertSee(route('admin.settings.shipping-zones.index'), false);
    }
}
