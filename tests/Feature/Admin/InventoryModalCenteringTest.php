<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
 * so all three are covered.
 */
class InventoryModalCenteringTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Inventory',
            'last_name' => 'Admin',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    /**
     * @return string[] the opening tag of every x-show element on the page
     */
    private function toggledTags(string $url): array
    {
        $html = $this->actingAs($this->adminUser, 'admin')->get($url)
            ->assertStatus(200)
            ->getContent();

        preg_match_all('/<[a-z][^>]*\sx-show=[^>]*>/i', $html, $matches);

        return $matches[0];
    }

    public static function inventoryPages(): array
    {
        return [
            'inventory' => ['/admin/inventory'],
            'low stock' => ['/admin/inventory/low-stock'],
            'out of stock' => ['/admin/inventory/out-of-stock'],
        ];
    }

    /**
     * The regression itself: an x-show element may not set display inline,
     * because opening the element throws that declaration away.
     *
     * @dataProvider inventoryPages
     */
    public function test_no_toggled_element_declares_display_inline(string $url): void
    {
        foreach ($this->toggledTags($url) as $tag) {
            if (! preg_match('/\sstyle="([^"]*)"/i', $tag, $style)) {
                continue;
            }

            $this->assertDoesNotMatchRegularExpression(
                '/(^|;)\s*display\s*:/i',
                $style[1],
                "x-show strips inline display, so this element loses it on open: {$tag}"
            );
        }
    }

    /**
     * @dataProvider inventoryPages
     */
    public function test_stock_dialog_uses_the_centred_modal_classes(string $url): void
    {
        $html = $this->actingAs($this->adminUser, 'admin')->get($url)
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('class="kk-modal"', $html);
        $this->assertStringContainsString('kk-modal__backdrop', $html);
        $this->assertStringContainsString('kk-modal__card', $html);
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
