<?php

namespace Tests\Feature;

use App\Rules\PersonName;
use App\Rules\ValidationRules;
use Illuminate\Support\Str;
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
     *
     * Every box must echo ValidationRules::namePattern() rather than carry its
     * own copy. Five hand-copied regexes drifted apart once already, and a copy
     * that loses the leading-whitespace clause looks fine and quietly blocks
     * checkout for anyone who pasted their name in.
     */
    private function renderedPattern(string $view, string $id): string
    {
        $this->assertSame(
            1,
            preg_match('/\bpattern="([^"]*)"/', $this->inputTag($view, $id), $m),
            "The {$id} input in {$view} has no pattern attribute."
        );

        $this->assertSame(
            '{{ \App\Rules\ValidationRules::namePattern() }}',
            $m[1],
            "The {$id} input in {$view} carries its own copy of the name pattern instead of echoing the one definition."
        );

        return ValidationRules::namePattern();
    }

    /**
     * The HTML pattern is a JavaScript regular expression; PCRE spells the
     * Unicode escapes differently and anchors nothing by itself. Translating
     * rather than retyping is the point - the test then exercises the string
     * that actually ships, not a copy that can drift away from it.
     */
    private function asPcre(string $jsSource): string
    {
        // \u{XXXX} is JavaScript's spelling under the u/v flag; PCRE wants \x{}.
        $pcre = preg_replace('/\\\\u\{([0-9a-fA-F]+)\}/', '\x{$1}', $jsSource);
        $pcre = preg_replace('/\\\\u([0-9a-fA-F]{4})/', '\x{$1}', $pcre);
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
        // Str::trim, not PHP's trim: it is what the TrimStrings middleware calls,
        // and it strips Str::INVISIBLE_CHARACTERS - TAB, NBSP, the ideographic
        // space, the zero-width joiners - which PHP's trim leaves alone. Using
        // the wrong one here is what let the leading-NBSP defect through: the
        // fixture looked rejected server-side, so the browser refusing it looked
        // correct, when in fact the server accepts the name and the box did not.
        $rejected = false;
        (new PersonName)->validate('full name', Str::trim($name), function () use (&$rejected) {
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
        //
        // Judged on the TRIMMED name: leading and trailing whitespace is what the
        // server discards anyway, so the filter dropping it changes nothing. What
        // must survive is every character of the name itself - which is why this
        // splits by code point, and why the filter had to stop walking UTF-16
        // code units: a supplementary-plane letter is one character here and two
        // there, and testing each half alone matches nothing.
        foreach (preg_split('//u', Str::trim($name), -1, PREG_SPLIT_NO_EMPTY) as $char) {
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
            'all four separators' => ["Jean-Luc O'Brien Jr."],
            // Real Indian city names, since two of these boxes are City.
            'city with a space' => ['Navi Mumbai'],
            'city with a period' => ['St. Thomas Mount'],
            // A supplementary-plane letter. 𠮟 is a genuine CJK name character,
            // and both PersonName and the pattern accept it - only the keystroke
            // filter used to delete it, silently, as the customer typed.
            'astral CJK letter' => ["\u{20B9F}\u{7530}"],
            // Everything below is whitespace Str::trim strips at the ends, so the
            // server never sees it and the box must not block the submit over it.
            // These are what a pasted name actually carries, and their absence is
            // what let the browser start refusing names the server accepts.
            'leading space' => [' Asha'],
            'trailing space' => ['Asha '],
            'inner non-breaking space' => ["Asha\u{00A0}Menon"],
            'leading non-breaking space (Word, WhatsApp Web)' => ["\u{00A0}Asha Menon"],
            'trailing non-breaking space' => ["Asha Menon\u{00A0}"],
            'leading tab (spreadsheet paste)' => ["\tAsha Menon"],
            'trailing tab' => ["Asha Menon\t"],
            'leading ideographic space' => ["\u{3000}Asha"],
            'leading narrow non-breaking space' => ["\u{202F}Asha"],
            'leading zero-width space' => ["\u{200B}Asha"],
            'trailing byte-order mark' => ["Asha\u{FEFF}"],
        ];
    }
}
