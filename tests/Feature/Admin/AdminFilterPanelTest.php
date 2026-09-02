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

    /**
     * The root cause, guarded across the admin panel: Alpine state must never
     * be seeded from the query string. That is the only way a collapsed panel
     * can come back expanded, and it is how both regressions happened.
     *
     * Scoped to admin views on purpose — the storefront login screen legitimately
     * reads ?mode=register to deep-link into the register tab.
     */
    public function test_no_admin_view_seeds_alpine_state_from_the_query_string(): void
    {
        $offenders = [];

        foreach ($this->adminBladeViews() as $path) {
            $src = file_get_contents($path);

            if (! preg_match_all('/x-data="([^"]*)"/', $src, $matches)) {
                continue;
            }

            foreach ($matches[1] as $expression) {
                if (str_contains($expression, 'request()')) {
                    $offenders[] = $this->relative($path) . ': ' . trim($expression);
                }
            }
        }

        $this->assertSame([], $offenders, "Alpine state seeded from the request:\n" . implode("\n", $offenders));
    }

    /**
     * A toggle variable declared but never used means the panel it belonged to
     * is gone — the orders screen carried one for a filter drawer that does not
     * exist, waiting to be revived with the same bug.
     */
    public function test_no_admin_view_declares_an_unused_filter_toggle(): void
    {
        $offenders = [];

        foreach ($this->adminBladeViews() as $path) {
            $src = file_get_contents($path);

            foreach (['showFilters', 'filtersOpen', 'showFilter', 'openFilters'] as $toggle) {
                if (substr_count($src, $toggle) === 1) {
                    $offenders[] = $this->relative($path) . ": {$toggle} is declared but nothing uses it";
                }
            }
        }

        $this->assertSame([], $offenders, "Dead filter toggle:\n" . implode("\n", $offenders));
    }

    /** @return string[] */
    private function adminBladeViews(): array
    {
        return array_values(array_filter(
            $this->bladeViews(),
            fn (string $path) => str_contains($path, DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR),
        ));
    }

    /** @return string[] */
    private function bladeViews(): array
    {
        $files = [];
        $dir = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($dir as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function relative(string $path): string
    {
        return str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
    }
}
