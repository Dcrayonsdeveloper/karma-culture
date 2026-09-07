<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * /returns-policy and /size-guides used to be Blade files, so correcting a
 * return window or a chest measurement took a code change. They are `pages`
 * rows now - the same thing the four legal pages are - and this is the promise
 * that makes: what an admin types under Online Store -> Pages is what a
 * shopper reads.
 *
 * The rows come from a data migration, so RefreshDatabase alone puts them
 * there; nothing in this file seeds them, which is the point.
 */
class ReturnsAndSizeGuideAreEditablePagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function pageProvider(): array
    {
        return [
            'returns policy' => ['/returns-policy', 'returns-policy', 'Returns Policy'],
            'size guide' => ['/size-guides', 'size-guides', 'Size Guide'],
        ];
    }

    private function actingAsAdmin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        Admin::create([
            'user_id' => $user->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Auth::guard('admin')->login($user);
    }

    /**
     * @dataProvider pageProvider
     */
    public function test_the_page_is_a_row_the_admin_owns(string $path, string $slug, string $title): void
    {
        $page = Page::where('slug', $slug)->first();

        $this->assertNotNull($page, "No pages row for {$slug}, so {$path} answers 404.");
        $this->assertSame($title, $page->title);
        $this->assertTrue($page->is_published);
        $this->assertNotEmpty($page->content);

        $this->get($path)->assertOk()->assertSee($title, false);
    }

    /**
     * The reason the conversion was worth doing at all.
     *
     * @dataProvider pageProvider
     */
    public function test_editing_the_page_in_the_admin_changes_what_a_shopper_reads(string $path, string $slug): void
    {
        $this->actingAsAdmin();
        $page = Page::where('slug', $slug)->firstOrFail();

        $this->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => '<h2>Rewritten By An Admin</h2><p>The new wording lives in the database.</p>',
            'is_published' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.pages.index'));

        $this->get($path)
            ->assertOk()
            ->assertSee('Rewritten By An Admin')
            ->assertSee('The new wording lives in the database.');
    }

    /**
     * @dataProvider pageProvider
     */
    public function test_the_page_is_listed_and_openable_in_the_pages_admin(string $path, string $slug, string $title): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.pages.index'))->assertOk()->assertSee($title);

        $page = Page::where('slug', $slug)->firstOrFail();

        $this->get(route('admin.pages.edit', $page))
            ->assertOk()
            ->assertSee('name="content"', false)
            ->assertSee($slug, false);
    }

    /**
     * The chart is the whole page. Every one of its seventeen bands has to
     * survive the output allowlist, and the cells have to be told apart -
     * before the [&_table] rules landed, Tailwind's preflight ran the five
     * columns of every row together into one unreadable string.
     */
    public function test_the_size_chart_survives_with_all_of_its_rows(): void
    {
        $html = $this->get('/size-guides')->assertOk()->getContent();

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('Height (cm)', $html);

        // Counted inside the body rather than over the whole document, so the
        // number cannot be thrown off by the header row or by anything the
        // layout adds around the page.
        preg_match('#<tbody>(.*?)</tbody>#s', $html, $body);
        $this->assertSame(
            17,
            substr_count($body[1] ?? '', '<tr>'),
            'The size chart lost or gained a band between the pages row and the page.'
        );

        foreach (['0 - 3 months', '13 - 14 years', '152 - 158'] as $cell) {
            $this->assertStringContainsString($cell, $html, "The chart is missing the cell {$cell}.");
        }

        $this->assertStringContainsString('[&_td]:px-3', $html, 'The chart is rendering with no cell padding.');

        // CKEditor names its table wrapper <figure class="table">, and "table"
        // is also a Tailwind display utility - so without this the wrapper
        // takes display:table, shrinks to its contents, and the chart sits at
        // 438px in an 864px card. Nothing about the page looks broken enough
        // to notice in a diff, which is why it is asserted here.
        $this->assertStringContainsString(
            '[&_figure]:block',
            $html,
            'The table wrapper will collapse onto Tailwind\'s .table utility and shrink-wrap.'
        );
    }

    /**
     * The three measurement columns carry a diagram of what to measure. It is
     * drawn by the template, not stored in the page body - CKEditor has no
     * image plugin and would drop an <img> on the first admin save - so this
     * checks the template still finds the headings to attach them to.
     */
    public function test_each_measurement_column_is_shown_with_its_diagram(): void
    {
        $html = $this->get('/size-guides')->assertOk()->getContent();

        foreach (['Height (cm)', 'Chest (cm)', 'Waist (cm)'] as $heading) {
            $this->assertMatchesRegularExpression(
                '#<th[^>]*><span[^>]*>\s*<svg.*?</svg>\s*'.preg_quote($heading, '#').'#s',
                $html,
                "The {$heading} column lost its measurement diagram."
            );
        }

        // The Size and Age columns are not measurements and get nothing.
        $this->assertMatchesRegularExpression('#<th[^>]*>\s*Size\s*</th>#', $html);
    }

    /**
     * The diagrams are attached to the rendered output, so they must not be a
     * way for stored page copy to smuggle markup past the sanitiser.
     */
    public function test_the_diagrams_do_not_reopen_the_content_sanitiser(): void
    {
        $this->actingAsAdmin();
        $page = Page::where('slug', 'size-guides')->firstOrFail();

        $this->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => '<h2>Chart</h2><table><thead><tr>'
                .'<th>Height <script>alert(1)</script></th></tr></thead>'
                .'<tbody><tr><td>1</td></tr></tbody></table>',
            'is_published' => 1,
        ])->assertSessionHasNoErrors();

        $html = $this->get('/size-guides')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    /**
     * The write allowlist and the render allowlist used to disagree, and
     * CKEditor writes italic as <i>, never <em>. So italicising a word saved
     * cleanly and then vanished from the page, with nothing to show why.
     */
    public function test_emphasis_the_editor_can_produce_survives_to_the_page(): void
    {
        $this->actingAsAdmin();
        $page = Page::where('slug', 'returns-policy')->firstOrFail();

        $this->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => '<h2>Emphasis</h2><p><i>italic</i> <b>bold</b> <u>underlined</u></p>',
            'is_published' => 1,
        ])->assertSessionHasNoErrors();

        $html = $this->get('/returns-policy')->assertOk()->getContent();

        $this->assertStringContainsString('<i>italic</i>', $html);
        $this->assertStringContainsString('<b>bold</b>', $html);
        $this->assertStringContainsString('<u>underlined</u>', $html);
    }

    /**
     * A page that is linked from the footer of every page on the site is not
     * something a rerun migration should be able to overwrite.
     */
    public function test_rerunning_the_import_migration_leaves_an_edited_page_alone(): void
    {
        $page = Page::where('slug', 'returns-policy')->firstOrFail();
        $page->update(['content' => '<h2>Ours</h2><p>Hand written.</p>']);

        $migration = require database_path(
            'migrations/2026_09_07_170000_import_hardcoded_returns_and_size_guide_pages.php'
        );
        $migration->up();

        $this->assertSame('<h2>Ours</h2><p>Hand written.</p>', $page->fresh()->content);
        $this->assertSame(1, Page::where('slug', 'returns-policy')->count());
    }

    /**
     * Neither page may be given a menu placement: the footer already hardcodes
     * a link to both, and a generated one would sit beside it pointing at
     * /page/<slug> - two links, two URLs, one body.
     */
    public function test_neither_page_adds_a_second_footer_link(): void
    {
        $this->assertDatabaseCount('navigation_menus', 0);
    }
}
