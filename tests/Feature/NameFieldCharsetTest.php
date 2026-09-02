<?php

namespace Tests\Feature;

use App\Rules\PersonName;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every box whose server rule is V::name() - the checkout Full Name, and the
 * address book's Full Name and City - asked the browser for a length and
 * nothing else. "chirag raw arakn@#@!#q13123123" therefore satisfied every
 * client-side check and was refused only by PersonName, after the whole form
 * had been submitted.
 *
 * Each of those boxes now carries three layers: the server rule, a `pattern`
 * spelling that rule's charset, and the data-kk-chars keystroke filter that
 * refuses the character before it appears. These lock in that all three agree,
 * and - the failure mode that actually matters - that neither client-side layer
 * is ever the stricter one. A browser rule the server would have accepted is an
 * order nobody can place, failing with no server message to explain itself.
 *
 * CheckoutNameValidationTest covers the server end, which a direct POST reaches
 * without passing through any of this.
 */
class NameFieldCharsetTest extends TestCase
{
    /**
     * Every field expected to carry the PersonName charset, as
     * [view, input id, the label used in failure messages].
     */
    public static function nameFields(): array
    {
        return [
            'checkout full name' => ['checkout/index.blade.php', 'kk-co-name'],
            'address book full name (create)' => ['account/addresses/create.blade.php', 'name'],
            'address book city (create)' => ['account/addresses/create.blade.php', 'city'],
            'address book full name (edit)' => ['account/addresses/edit.blade.php', 'name'],
            'address book city (edit)' => ['account/addresses/edit.blade.php', 'city'],
        ];
    }

    /**
     * One whole <input> tag, so an assertion about a field cannot accidentally
     * be satisfied by the next field's attributes.
     *
     * Scanning to the next `>` is not enough: `value="{{ $address->full_name }}"`
     * puts one inside an attribute. Consuming quoted runs whole steps over those.
     */
    private function inputTag(string $view, string $id): string
    {
        $blade = file_get_contents(resource_path('views/'.$view));

        preg_match_all('/<input\b(?:[^>"]|"[^"]*")*>/', $blade, $tags);

        foreach ($tags[0] as $tag) {
            if (preg_match('/\bid="'.preg_quote($id, '/').'"/', $tag)) {
                return $tag;
            }
        }

        $this->fail("No <input> with id=\"{$id}\" in {$view}.");
    }

    /**
     * The pattern attribute exactly as the browser will receive it.
     */
    private function renderedPattern(string $view, string $id): string
    {
        $this->assertSame(
            1,
            preg_match('/\bpattern="([^"]*)"/', $this->inputTag($view, $id), $m),
            "The {$id} input in {$view} has no pattern attribute."
        );

        return $m[1];
    }

    /**
     * The HTML pattern is a JavaScript regular expression; PCRE spells the
     * Unicode escapes differently and anchors nothing by itself. Translating
     * rather than retyping is the point - the test then exercises the string
     * that actually ships, not a copy that can drift away from it.
     */
    private function asPcre(string $jsSource): string
    {
        $pcre = preg_replace('/\\\\u([0-9a-fA-F]{4})/', '\x{$1}', $jsSource);
        $pcre = preg_replace('/\\\\x([0-9a-fA-F]{2})(?![0-9a-fA-F])/', '\x{$1}', $pcre);

        return '/^(?:'.$pcre.')$/u';
    }

    /**
     * The keystroke filter's charset, read out of app.js.
     */
    private function keystrokePolicy(): string
    {
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertSame(
            1,
            preg_match('/personName:\s*\{\s*allow:\s*\/\[([^\]]*)\]\//', $js, $m),
            'app.js has no personName character policy.'
        );

        return '/['.preg_replace('/\\\\u([0-9a-fA-F]{4})/', '\x{$1}', $m[1]).']/u';
    }

    #[DataProvider('nameFields')]
    public function test_the_box_asks_for_a_charset_and_not_just_a_length(string $view, string $id): void
    {
        $tag = $this->inputTag($view, $id);

        $this->assertMatchesRegularExpression(
            '/\bdata-kk-chars="personName"/',
            $tag,
            "The {$id} input in {$view} should refuse a disallowed character as it is typed."
        );

        $this->assertMatchesRegularExpression(
            '/\btitle="[^"]+"/',
            $tag,
            "The {$id} input in {$view} has a pattern but no title, which leaves the browser to invent the message."
        );
    }

    /**
     * The shape from the bug report: length alone let it through.
     */
    #[DataProvider('nameFields')]
    public function test_the_box_refuses_a_name_of_symbols(string $view, string $id): void
    {
        $this->assertDoesNotMatchRegularExpression(
            $this->asPcre($this->renderedPattern($view, $id)),
            'chirag raw arakn@#@!#q13123123',
            "The {$id} input in {$view} still accepts the reported name."
        );
    }

    /**
     * The failure mode a client-side mirror must never have.
     */
    #[DataProvider('namesTheServerAccepts')]
    public function test_no_box_rejects_a_name_the_server_accepts(string $name): void
    {
        $rejected = false;
        (new PersonName)->validate('full name', trim($name), function () use (&$rejected) {
            $rejected = true;
        });
        $this->assertFalse($rejected, "Fixture is wrong: the server rejects \"{$name}\".");

        $policy = $this->keystrokePolicy();

        foreach (self::nameFields() as $label => [$view, $id]) {
            $this->assertMatchesRegularExpression(
                $this->asPcre($this->renderedPattern($view, $id)),
                $name,
                "The {$label} box would refuse \"{$name}\", which the server accepts."
            );
        }

        // The pattern and the keystroke filter are two spellings of one charset.
        // A character the pattern allows but the filter drops can never be typed
        // into the box at all, so the two have to agree character for character.
        foreach (preg_split('//u', $name, -1, PREG_SPLIT_NO_EMPTY) as $char) {
            $this->assertMatchesRegularExpression(
                $policy,
                $char,
                "The keystroke filter drops \"{$char}\", which the patterns allow."
            );
        }
    }

    public static function namesTheServerAccepts(): array
    {
        return [
            'plain' => ['Asha Menon'],
            'apostrophe' => ["O'Connor"],
            'typographic apostrophe' => ['O’Connor'],
            'hyphen' => ['Mary-Anne'],
            'initial and period' => ['R. Sharma'],
            'devanagari' => ['रवि कुमार'],
            'han' => ['山田太郎'],
            'accented' => ['José'],
            'combining marks' => ['Nguyễn Thị Hà'],
            // TrimStrings drops these server-side, so the box must not block the
            // submit over a character the customer cannot see.
            'leading space' => [' Asha'],
            'trailing space' => ['Asha '],
            'non-breaking space' => ["Asha\u{00A0}Menon"],
            'all four separators' => ["Jean-Luc O'Brien Jr."],
            // Real Indian city names, since two of these boxes are City.
            'city with a space' => ['Navi Mumbai'],
            'city with a period' => ['St. Thomas Mount'],
        ];
    }
}
