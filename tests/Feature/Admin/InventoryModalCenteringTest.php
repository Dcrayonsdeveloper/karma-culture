<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * The "Adjust Stock" dialog opened pinned to the top-left corner instead of
 * centred.
 *
 * Alpine's x-show owns the inline display property: it hides with
 * style.setProperty('display', 'none') and shows with
 * style.removeProperty('display'). The overlay declared
 * "display: flex; align-items: center; justify-content: center" in its own
 * style attribute, so the very first open deleted that declaration along with
 * the none, leaving a full-screen block container whose card fell to the
 * top-left. Centring now lives in the .kk-modal class, where x-show cannot
 * reach it.
 *
 * The same overlay is copy-pasted into the low-stock and out-of-stock screens,
 * so all three are covered. That the pages themselves still render is covered
 * by RouteSmokeTest, which sweeps every parameterless admin GET; this class
 * stays free of the database so it keeps working while the shared test schema
 * is contended.
 */
class InventoryModalCenteringTest extends TestCase
{
    /**
     * Every template that makes up an inventory screen, including the shell it
     * renders inside — the dialog is only centred if nothing in the chain
     * reintroduces the bug.
     */
    public static function inventoryTemplates(): array
    {
        $paths = [
            'admin/inventory/index.blade.php',
            'admin/inventory/low-stock.blade.php',
            'admin/inventory/out-of-stock.blade.php',
            'admin/inventory/locations/show.blade.php',
            'admin/partials/header.blade.php',
            'admin/partials/sidebar.blade.php',
        ];

        return array_combine($paths, array_map(fn ($p) => [$p], $paths));
    }

    public static function stockDialogTemplates(): array
    {
        $paths = [
            'admin/inventory/index.blade.php',
            'admin/inventory/low-stock.blade.php',
            'admin/inventory/out-of-stock.blade.php',
            // A warehouse's own page adjusts one line at a time through the
            // same dialog, so it inherits the same trap.
            'admin/inventory/locations/show.blade.php',
        ];

        return array_combine($paths, array_map(fn ($p) => [$p], $paths));
    }

    private function template(string $relative): string
    {
        $path = resource_path('views/' . $relative);
        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    /**
     * The regression itself: an x-show element may not set display inline,
     * because opening the element throws that declaration away.
     *
     * @dataProvider inventoryTemplates
     */
    public function test_no_toggled_element_declares_display_inline(string $template): void
    {
        preg_match_all('/<[a-z][^>]*\sx-show=[^>]*>/is', $this->template($template), $matches);

        $this->assertNotEmpty(
            $matches[0],
            "Expected {$template} to still contain x-show elements; if it no longer "
                . 'does, drop it from the provider rather than leaving a test that checks nothing.'
        );

        foreach ($matches[0] as $tag) {
            if (! preg_match('/\sstyle="([^"]*)"/i', $tag, $style)) {
                continue;
            }

            $this->assertDoesNotMatchRegularExpression(
                '/(^|;)\s*display\s*:/i',
                $style[1],
                "x-show strips inline display, so this element loses it on open.\n"
                    . "In {$template}: " . trim(preg_replace('/\s+/', ' ', $tag))
            );
        }
    }

    /**
     * @dataProvider stockDialogTemplates
     */
    public function test_stock_dialog_uses_the_centred_modal_classes(string $template): void
    {
        $source = $this->template($template);

        $this->assertStringContainsString('class="kk-modal"', $source);
        $this->assertStringContainsString('kk-modal__backdrop', $source);
        $this->assertStringContainsString('kk-modal__card', $source);
    }

    /**
     * The classes only help if the stylesheet actually centres and constrains
     * them, which is what keeps the dialog usable on a short phone screen.
     */
    public function test_stylesheet_centres_and_constrains_the_modal(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/\.kk-modal\s*\{[^}]*display:\s*flex[^}]*\}/s',
            $css,
            '.kk-modal must provide the flex centring that the inline style used to.'
        );
        $this->assertMatchesRegularExpression(
            '/\.kk-modal\s*\{[^}]*align-items:\s*center[^}]*\}/s',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/\.kk-modal\s*\{[^}]*justify-content:\s*center[^}]*\}/s',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/\.kk-modal__card\s*\{[^}]*max-height:[^}]*\}/s',
            $css,
            'A tall form must scroll inside the viewport rather than overflow it.'
        );
        $this->assertMatchesRegularExpression(
            '/\.kk-modal__card\s*\{[^}]*overflow-y:\s*auto[^}]*\}/s',
            $css
        );
    }
}
