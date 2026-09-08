<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Banner;
use App\Models\FestivalSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A festival sale's banner, seen from Marketing > Banners.
 *
 * A festival sale keeps its artwork in its own two columns rather than as a
 * banners row, and the home page draws it as slide 0 of the very hero carousel
 * the banners table fills. So an admin who uploaded one had it live on the shop
 * and absent from the one screen that lists what the shop is showing, with no
 * way back to it but remembering which sale owned it.
 *
 * These pin both halves of the fix: that a sale's banner is listed, with the
 * State column carrying whether a shopper can actually see it, and that it is
 * only ever LISTED. Mirroring one into a banners row would put the
 * same picture in the hero twice and leave two records of one image free to
 * drift apart, so the count in the table must stay where it was.
 */
class FestivalBannerOnBannersPageTest extends TestCase
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

    private function admin(): self
    {
        return $this->actingAs($this->adminUser, 'admin');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function sale(array $overrides = []): FestivalSale
    {
        return FestivalSale::create(array_merge([
            'name' => 'Diwali Sale',
            'slug' => 'diwali-sale',
            'discount_percent' => 25,
            'banner_path' => 'festival-sales/diwali.webp',
            'is_active' => true,
            'show_on_home' => true,
        ], $overrides));
    }

    public function test_a_running_sales_banner_is_listed_on_the_banners_page(): void
    {
        $sale = $this->sale();

        $this->admin()->get(route('admin.banners.index'))
            ->assertOk()
            ->assertSee('Diwali Sale')
            ->assertSee('festival-sales/diwali.webp')
            // The row has to lead somewhere, or it is a picture of a problem
            // rather than a way to it.
            ->assertSee(route('admin.festival-sales.edit', $sale), false);
    }

    /**
     * The whole point of listing them is that the hero is one carousel. A
     * mirrored banners row would draw the same artwork twice - once as the
     * festival slide, once as a hero banner behind it.
     */
    public function test_listing_one_does_not_create_a_banner_row(): void
    {
        $this->sale();

        $this->admin()->get(route('admin.banners.index'))->assertOk();

        $this->assertSame(0, Banner::withTrashed()->count(), 'A festival banner is shown, never copied into the banners table.');
    }

    /**
     * A switched-off sale is listed as hidden, not dropped.
     *
     * Running-only was the first cut of this and it read as a bug: the sale on
     * production had artwork and was switched off, so the fix for "my festival
     * banner is missing from this page" left the page just as empty. This
     * screen already lists a hidden or an expired banner beside a live one and
     * lets the State column carry the difference; these follow the same rule.
     */
    public function test_a_switched_off_sale_is_listed_as_hidden(): void
    {
        $this->sale(['name' => 'Old Holi Sale', 'slug' => 'old-holi-sale', 'is_active' => false]);

        $this->admin()->get(route('admin.banners.index'))
            ->assertOk()
            ->assertSee('Old Holi Sale')
            ->assertSee('The sale is switched off')
            // Off is off: it cannot also be claiming the home hero.
            ->assertDontSee('Leads the home hero');
    }

    public function test_a_sale_with_no_artwork_is_not_listed(): void
    {
        $this->sale([
            'name' => 'Bannerless Sale',
            'slug' => 'bannerless-sale',
            'banner_path' => null,
        ]);

        $this->admin()->get(route('admin.banners.index'))
            ->assertOk()
            ->assertDontSee('Bannerless Sale');
    }

    /** Phone-only artwork is still artwork, and still the banner every phone gets. */
    public function test_a_sale_with_only_phone_artwork_is_listed(): void
    {
        $this->sale([
            'name' => 'Phone Only Sale',
            'slug' => 'phone-only-sale',
            'banner_path' => null,
            'banner_mobile_path' => 'festival-sales/phone.webp',
        ]);

        $this->admin()->get(route('admin.banners.index'))
            ->assertOk()
            ->assertSee('Phone Only Sale')
            // ...but it is not what the home page leads with: HomeController
            // reads bannerUrl(), which is the desktop file.
            ->assertSee('no desktop banner');
    }

    public function test_the_bin_and_the_other_placements_do_not_list_it(): void
    {
        $this->sale();

        // Deleted banners are a different question, and a sale cannot be in
        // that bin at all.
        $this->admin()->get(route('admin.banners.index', ['trashed' => 1]))
            ->assertOk()
            ->assertDontSee('Diwali Sale');

        // Filtering to the footer is asking about somewhere these never appear.
        $this->admin()->get(route('admin.banners.index', ['position' => 'footer']))
            ->assertOk()
            ->assertDontSee('Diwali Sale');

        // Filtering to the hero is asking about exactly where they do.
        $this->admin()->get(route('admin.banners.index', ['position' => 'hero']))
            ->assertOk()
            ->assertSee('Diwali Sale');
    }

    /**
     * Switched on and in the hero are different questions.
     *
     * Only the most recently updated running sale leads the home page, and one
     * with "Show on home" off leads nothing - so a row that said "Live" and
     * stopped would promise a banner the home page is not drawing.
     */
    public function test_the_row_says_whether_it_actually_leads_the_home_hero(): void
    {
        $sale = $this->sale();

        $this->admin()->get(route('admin.banners.index'))
            ->assertOk()
            ->assertSee('Leads the home hero');

        $sale->update(['show_on_home' => false]);

        $this->admin()->get(route('admin.banners.index'))
            ->assertOk()
            ->assertDontSee('Leads the home hero')
            ->assertSee('is off');
    }

    /**
     * Two sales can be switched on at once; only one wins the hero, and the
     * list has to be able to tell them apart.
     */
    public function test_only_one_running_sale_leads_the_hero(): void
    {
        $this->sale(['name' => 'Older Sale', 'slug' => 'older-sale']);
        $this->sale(['name' => 'Newer Sale', 'slug' => 'newer-sale']);

        $response = $this->admin()->get(route('admin.banners.index'))->assertOk();

        $response->assertSee('Older Sale')->assertSee('Newer Sale');

        $html = $response->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'Leads the home hero'),
            'Exactly one running sale is drawn into the home hero, however many are switched on.'
        );
        $this->assertStringContainsString('another sale leads the hero', $html);
    }

    /**
     * The empty-state panel speaks for the paginated table. It must not sit
     * underneath a festival group that is plainly listing a banner.
     */
    public function test_the_empty_state_yields_to_a_festival_banner(): void
    {
        $this->admin()->get(route('admin.banners.index'))
            ->assertOk()
            ->assertSee('No banners found');

        $this->sale();

        $this->admin()->get(route('admin.banners.index'))
            ->assertOk()
            ->assertDontSee('No banners found')
            ->assertSee('Diwali Sale');
    }
}
