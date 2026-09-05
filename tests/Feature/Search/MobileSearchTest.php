<?php

namespace Tests\Feature\Search;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A phone has to be able to search.
 *
 * Below sm the header's inline search bar is hidden, and its magnifier was a
 * plain link to /search. With no query that page rendered "Start searching.
 * Enter a keyword to find products." and nothing else - no field. So the one
 * search affordance on a phone landed the shopper on a screen that asked them
 * to type and gave them nowhere to type.
 *
 * Two things fix it, and both are worth pinning: the magnifier now opens a
 * full-screen panel built on the same searchBar() component the desktop bar
 * uses, and /search itself carries a real field so the URL is not a dead end
 * when it is reached directly, from a bookmark or a shared link.
 */
class MobileSearchTest extends TestCase
{
    use RefreshDatabase;

    private function seedProduct(): Product
    {
        $category = Category::create([
            'name' => 'Shirts',
            'slug' => 'shirts',
            'is_active' => true,
        ]);

        return Product::create([
            'name' => 'Poplin Shirt',
            'slug' => 'poplin-shirt',
            'sku' => 'POPLIN',
            'price' => 500,
            'mrp' => 900,
            'cost_price' => 200,
            'stock_quantity' => 10,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    /** The single <form> that contains $needle. */
    private function formContaining(string $html, string $needle): string
    {
        $at = strpos($html, $needle);
        $this->assertNotFalse($at, sprintf('Nothing matching %s on the page.', $needle));

        $open = strrpos(substr($html, 0, $at), '<form');
        $this->assertNotFalse($open, sprintf('%s is not inside a form.', $needle));

        return substr($html, $open, strpos($html, '</form>', $open) - $open);
    }

    public function test_the_empty_search_page_offers_a_field_to_type_in(): void
    {
        $html = $this->get('/search')->assertOk()->getContent();

        // It still says "enter a keyword" - so there had better be somewhere to.
        $this->assertStringContainsString('Enter a keyword to find products.', $html);

        // Scoped to the empty state, or this matches the mobile panel's field,
        // which the layout renders further up every page.
        $emptyState = strstr($html, 'Enter a keyword to find products.');
        $form = $this->formContaining($emptyState, 'name="q"');

        $this->assertStringContainsString('name="q"', $form);
        $this->assertStringContainsString(route('search'), $form);
        $this->assertStringContainsString('autofocus', $form);
    }

    public function test_that_field_actually_runs_a_search(): void
    {
        $this->seedProduct();

        // What the empty-state form submits.
        $this->get('/search?q=Poplin')
            ->assertOk()
            ->assertSee('Poplin Shirt');
    }

    public function test_the_phone_magnifier_opens_the_panel_instead_of_leaving_the_page(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // The mobile control is the one marked sm:hidden. It must dispatch, not
        // navigate: a link to /search is what produced the dead-end screen.
        $ok = preg_match('/<(a|button)\b[^>]*sm:hidden[^>]*aria-label="Search"[^>]*>/', $html, $m);
        $this->assertSame(1, $ok, 'No mobile search control in the header.');

        $this->assertStringStartsWith('<button', $m[0], 'The phone magnifier is still a link.');
        $this->assertStringContainsString('open-mobile-search', $m[0]);
    }

    public function test_the_panel_is_on_the_page_and_hidden_until_asked_for(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('@open-mobile-search.window', $html);
        // x-cloak, or it flashes over the page on every load before Alpine boots.
        $ok = preg_match('/<div x-data="\{ open: false, \.\.\.searchBar\(\) \}"(?:(?!<\/div>).)*?x-cloak/s', $html);
        $this->assertSame(1, $ok, 'The panel is not cloaked until Alpine initialises it.');
    }

    public function test_the_panel_reuses_the_desktop_search_component(): void
    {
        // Not a second implementation: same suggestions endpoint, same voice
        // search, same typewriter, so the two cannot drift apart.
        $html = $this->get('/')->assertOk()->getContent();

        // From the panel's own x-data, which is where searchBar() is spread in.
        $panel = strstr($html, 'x-data="{ open: false, ...searchBar() }"');
        $panel = substr($panel, 0, strpos($panel, 'Popular searches'));

        $this->assertStringContainsString('searchBar()', $panel);
        $this->assertStringContainsString('fetchSuggestions()', $panel);
        $this->assertStringContainsString('x-model="query"', $panel);
    }

    public function test_the_panel_never_renders_on_desktop(): void
    {
        // The inline header bar covers sm and up; two search fields on one screen
        // would both post to /search and fight over focus.
        $html = $this->get('/')->assertOk()->getContent();

        // The panel is rendered once, and its ROOT carries sm:hidden - that is
        // what keeps it off desktop. It used to be asserted as the literal
        // string "sm:hidden fixed inset-0", which stopped being true when the
        // panel moved into partials/mobile-search.blade.php and the positioning
        // classes moved to an inner element. The panel was still mobile-only
        // throughout; only the assertion had gone stale.
        $this->assertStringContainsString('x-data="{ open: false, ...searchBar() }"', $html);

        // The opening tag cannot be cut at the first ">": the Alpine handlers on
        // it contain arrow functions. Take a generous window after the marker
        // and require the sm:hidden class inside it instead.
        $root = substr($html, strpos($html, 'x-data="{ open: false, ...searchBar() }"'), 2000);

        $this->assertStringContainsString('class="sm:hidden"', $root, 'The mobile search panel must not render on desktop.');
    }

    public function test_the_suggestions_endpoint_the_panel_calls_still_answers(): void
    {
        $this->seedProduct();

        // The panel reads data.suggestions; if the shape moves, it silently shows
        // "no matches" for everything.
        $this->getJson('/search/suggestions?q=Poplin')
            ->assertOk()
            ->assertJsonPath('suggestions.0.name', 'Poplin Shirt')
            ->assertJsonStructure(['suggestions' => [['id', 'name', 'url', 'image']]]);
    }
}
