<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin list screens hide their filter fields behind a "Filter" toggle. Both
 * screens that had one seeded the toggle from the query string
 * (`request()->hasAny([...]) ? 'true' : 'false'`), so the drawer sprang open on
 * its own: on Products the status tab links are themselves ?status=..., so
 * clicking "Active" — or running any search — reloaded the page with the
 * drawer already expanded, pushing the table down the screen.
 *
 * Filter drawers must start closed. The applied-filter badge on the toggle is
 * what tells the user filters are active, not the open drawer.
 */
class AdminFilterPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'role'       => 'admin',
        ]);

        Admin::create([
            'user_id'   => $this->adminUser->id,
            'role'      => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_products_filter_drawer_starts_closed_on_a_plain_visit(): void
    {
        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('showFilters: false', $html);
        $this->assertStringNotContainsString('showFilters: true', $html);
    }

    public function test_clicking_a_status_tab_does_not_spring_the_products_drawer_open(): void
    {
        // The reported bug: ?status=active is set by the Active tab link, and it
        // used to count as "a filter is applied", so the drawer rendered open.
        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.products.index', ['status' => 'active']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('showFilters: true', $html);
    }

    public function test_an_applied_filter_still_leaves_the_products_drawer_closed(): void
    {
        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.products.index', ['stock' => 'out', 'search' => 'shirt']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('showFilters: true', $html);
    }

    public function test_inventory_report_filter_card_starts_closed(): void
    {
        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.reports.inventory', ['stock_status' => 'low']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('x-data="{ open: false }"', $html);
        $this->assertStringNotContainsString('x-data="{ open: true }"', $html);
    }
}
