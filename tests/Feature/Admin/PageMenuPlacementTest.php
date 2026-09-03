<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Creating a page and listing it in a menu were two unrelated jobs on two
 * screens. The page form had no placement field at all, so a new policy page
 * existed at its URL and appeared nowhere - the only way to surface it was to
 * go to Online Store → Homepage → Navigation and hand-type a link to a slug
 * you had to remember. Nothing kept the two in step afterwards either: rename
 * the slug and the hand-made link 404s.
 *
 * The form now offers the four locations the storefront renders, and the link
 * is generated, moved and removed with the page.
 */
class PageMenuPlacementTest extends TestCase
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

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Shipping Policy',
            'slug' => 'shipping-policy',
            'content' => '<p>How we ship.</p>',
            'is_published' => 1,
        ], $overrides);
    }

    public function test_a_page_can_be_placed_in_a_footer_column_as_it_is_created(): void
    {
        $this->post(route('admin.pages.store'), $this->payload(['nav_location' => 'footer_col3']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.pages.index'));

        $link = NavigationMenu::firstOrFail();

        $this->assertSame('footer_col3', $link->location);
        $this->assertSame('Shipping Policy', $link->label);
        $this->assertSame('/page/shipping-policy', $link->url);
        $this->assertTrue($link->is_active);
        $this->assertSame(Page::firstOrFail()->id, $link->page_id);
    }

    /**
     * The whole point of the field: the link has to come out of the reader the
     * footer actually calls, not merely exist in the table.
     */
    public function test_the_placed_page_is_returned_by_the_reader_the_footer_uses(): void
    {
        $this->post(route('admin.pages.store'), $this->payload(['nav_location' => 'footer_col3']));

        $items = NavigationMenu::getByLocation('footer_col3');

        $this->assertCount(1, $items);
        $this->assertSame('Shipping Policy', $items->first()->label);
    }

    public function test_a_page_created_without_a_placement_is_listed_nowhere(): void
    {
        $this->post(route('admin.pages.store'), $this->payload())->assertSessionHasNoErrors();

        $this->assertDatabaseCount('navigation_menus', 0);
    }

    public function test_changing_the_placement_moves_the_one_link_rather_than_adding_another(): void
    {
        $this->post(route('admin.pages.store'), $this->payload(['nav_location' => 'footer_col3']));
        $page = Page::firstOrFail();

        $this->put(route('admin.pages.update', $page), $this->payload(['nav_location' => 'header']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('navigation_menus', 1);
        $this->assertSame('header', NavigationMenu::firstOrFail()->location);
    }

    public function test_clearing_the_placement_takes_the_link_out_of_the_menus(): void
    {
        $this->post(route('admin.pages.store'), $this->payload(['nav_location' => 'header']));
        $page = Page::firstOrFail();

        $this->put(route('admin.pages.update', $page), $this->payload(['nav_location' => '']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('navigation_menus', 0);
    }

    /**
     * A renamed slug used to leave a hand-made link pointing at a 404, which is
     * the failure that made this worth wiring up rather than documenting.
     */
    public function test_renaming_the_slug_carries_the_link_with_it(): void
    {
        $this->post(route('admin.pages.store'), $this->payload(['nav_location' => 'footer_col3']));
        $page = Page::firstOrFail();

        $this->put(route('admin.pages.update', $page), $this->payload([
            'slug' => 'delivery-policy',
            'nav_location' => 'footer_col3',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('/page/delivery-policy', NavigationMenu::firstOrFail()->url);
    }

    /**
     * A draft page answers 404, so its link is parked rather than published.
     */
    public function test_a_draft_pages_link_is_inactive_until_it_is_published(): void
    {
        $this->post(route('admin.pages.store'), $this->payload([
            'is_published' => 0,
            'nav_location' => 'header',
        ]));

        $this->assertFalse(NavigationMenu::firstOrFail()->is_active);
        $this->assertCount(0, NavigationMenu::getByLocation('header'));

        $page = Page::firstOrFail();
        $this->put(route('admin.pages.update', $page), $this->payload(['nav_location' => 'header']));

        $this->assertTrue(NavigationMenu::firstOrFail()->fresh()->is_active);
        $this->assertCount(1, NavigationMenu::getByLocation('header'));
    }

    /**
     * The Navigation editor stays the place that owns wording. A label retyped
     * there must survive the next content edit, or the two screens fight.
     */
    public function test_a_label_retyped_in_the_navigation_editor_is_not_overwritten(): void
    {
        $this->post(route('admin.pages.store'), $this->payload(['nav_location' => 'footer_col3']));
        $page = Page::firstOrFail();

        NavigationMenu::firstOrFail()->update(['label' => 'Shipping']);

        $this->put(route('admin.pages.update', $page), $this->payload([
            'title' => 'Shipping And Delivery Policy',
            'nav_location' => 'footer_col3',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Shipping', NavigationMenu::firstOrFail()->label);
    }

    /**
     * ...but a label nobody has touched still follows the page title, so a
     * renamed page is not left advertising its old name.
     */
    public function test_an_untouched_label_follows_the_page_title(): void
    {
        $this->post(route('admin.pages.store'), $this->payload(['nav_location' => 'footer_col3']));
        $page = Page::firstOrFail();

        $this->put(route('admin.pages.update', $page), $this->payload([
            'title' => 'Delivery Policy',
            'nav_location' => 'footer_col3',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Delivery Policy', NavigationMenu::firstOrFail()->label);
    }

    public function test_deleting_the_page_removes_its_link(): void
    {
        $this->post(route('admin.pages.store'), $this->payload(['nav_location' => 'header']));

        $this->delete(route('admin.pages.destroy', Page::firstOrFail()));

        $this->assertDatabaseCount('navigation_menus', 0);
    }

    /**
     * A hand-made link carries no page_id, so page edits must leave it alone -
     * that is the reason the row is found by page_id and not by URL.
     */
    public function test_a_hand_made_link_to_the_same_page_is_left_alone(): void
    {
        $this->post(route('admin.pages.store'), $this->payload(['nav_location' => 'footer_col3']));
        $page = Page::firstOrFail();

        $handMade = NavigationMenu::create([
            'location' => 'header',
            'label' => 'Shipping info',
            'url' => '/page/shipping-policy',
            'position' => 0,
            'is_active' => true,
        ]);

        $this->put(route('admin.pages.update', $page), $this->payload(['nav_location' => '']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('navigation_menus', ['id' => $handMade->id, 'label' => 'Shipping info']);
        $this->assertDatabaseCount('navigation_menus', 1);
    }

    public function test_an_unknown_location_is_rejected(): void
    {
        $this->post(route('admin.pages.store'), $this->payload(['nav_location' => 'sidebar']))
            ->assertSessionHasErrors('nav_location');

        $this->assertDatabaseCount('navigation_menus', 0);
    }

    public function test_both_page_forms_offer_the_placement_field(): void
    {
        $this->post(route('admin.pages.store'), $this->payload(['nav_location' => 'footer_col3']));
        $page = Page::firstOrFail();

        foreach (['create' => route('admin.pages.create'), 'edit' => route('admin.pages.edit', $page)] as $which => $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('name="nav_location"', $html, "The {$which} form has no placement field.");
            $this->assertStringContainsString('Footer - Policies', $html);
        }
    }

    /**
     * The edit screen has to open on the page's current placement, or saving an
     * unrelated content change silently drops the page out of its menu.
     */
    public function test_the_edit_form_preselects_the_current_placement(): void
    {
        $this->post(route('admin.pages.store'), $this->payload(['nav_location' => 'footer_col3']));
        $page = Page::firstOrFail();

        $html = $this->get(route('admin.pages.edit', $page))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<option value="footer_col3"[^>]*\bselected\b/',
            $html,
            'The current placement is not preselected, so the next save would clear it.'
        );
    }
}
