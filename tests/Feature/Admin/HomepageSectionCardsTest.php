<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * The tiles at the top of Online Store → Homepage.
 *
 * Navigation was tiled here because its editor was otherwise unreachable, but
 * header and footer menus are site-wide chrome rather than a home page block,
 * and the store owner asked for it off this screen. Hiding the tile must not
 * break the editor behind it - that would turn a presentation change into a
 * lost feature.
 */
class HomepageSectionCardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        Auth::guard('admin')->login($adminUser);
    }

    public function test_the_homepage_screen_does_not_tile_navigation(): void
    {
        $html = $this->get(route('admin.homepage.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString(
            route('admin.homepage.navigation'),
            $html,
            'The Navigation card is back on the homepage screen.'
        );
        $this->assertStringNotContainsString('Header &amp; footer menus', $html);
    }

    public function test_the_navigation_editor_itself_still_works(): void
    {
        $this->get(route('admin.homepage.navigation'))->assertOk();
    }

    /**
     * The tiles that do belong here stayed - the edit removed one anchor, not
     * the row around it.
     */
    public function test_the_remaining_homepage_tiles_are_untouched(): void
    {
        $html = $this->get(route('admin.homepage.index'))->assertOk()->getContent();

        $this->assertStringContainsString(route('admin.homepage.qualities'), $html);
    }
}
