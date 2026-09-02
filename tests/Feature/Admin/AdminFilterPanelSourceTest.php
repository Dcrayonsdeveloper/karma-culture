<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * The source-level half of the filter-drawer guard. These sweep the blade files
 * themselves, so they need no database and keep working when MySQL does not —
 * see AdminFilterPanelTest for the rendered-page half and the full story.
 */
class AdminFilterPanelSourceTest extends TestCase
{
    /**
     * The root cause, guarded across the admin panel: Alpine state must never
     * be seeded from the query string. That is the only way a collapsed panel
     * can come back expanded, and it is how both regressions happened — the
     * products drawer and the inventory report's "Filters & Search" card.
     *
     * Scoped to admin views on purpose: the storefront login screen legitimately
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
        $files = [];
        $dir = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($dir as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR)) {
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
