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
     * [view, the attribute that identifies the input, its value].
     *
     * Identified by `name` where the box has no id - the admin staff form
     * labels its inputs without one.
     */
    public static function nameFields(): array
    {
        return [
            'checkout full name' => ['checkout/index.blade.php', 'id', 'kk-co-name'],
            'address book full name (create)' => ['account/addresses/create.blade.php', 'id', 'name'],
            'address book city (create)' => ['account/addresses/create.blade.php', 'id', 'city'],
            'address book full name (edit)' => ['account/addresses/edit.blade.php', 'id', 'name'],
            'address book city (edit)' => ['account/addresses/edit.blade.php', 'id', 'city'],
            // The reported box: it asked for a length and nothing else, so
            // "686876988" could be typed into it in full.
            'offer popup name' => ['partials/offer-popup.blade.php', 'id', 'offer-name'],
            'admin staff first name (create)' => ['admin/staff/create.blade.php', 'name', 'first_name'],
            'admin staff last name (create)' => ['admin/staff/create.blade.php', 'name', 'last_name'],
            'admin staff first name (edit)' => ['admin/staff/edit.blade.php', 'name', 'first_name'],
            'admin staff last name (edit)' => ['admin/staff/edit.blade.php', 'name', 'last_name'],
            // Published under the review on the product page, so junk here is
            // junk on the storefront.
            'guest review name' => ['products/show.blade.php', 'name', 'guest_name'],
            // Identified by the blade expression itself: the edit form is inside
            // a loop, so its id is only literal in the source, and both
            // testimonial boxes share name="name".
            'testimonial name (new)' => ['admin/homepage/testimonials.blade.php', 'id', 'testimonial-name'],
            'testimonial name (edit)' => ['admin/homepage/testimonials.blade.php', 'id', 't{{ $testimonial->id }}-name'],
            'admin profile first name' => ['admin/profile/edit.blade.php', 'name', 'first_name'],
            'admin profile last name' => ['admin/profile/edit.blade.php', 'name', 'last_name'],
        ];
    }

    /**
     * The autocomplete tokens app.js infers the personName policy from.
     *
     * Read out of the bundle rather than retyped, so a box that earns its
     * filter by inference is tested against the list that actually ships.
     */
    private function inferredNameTokens(): array
    {
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertSame(
            1,
            preg_match('/const NAME_AUTOCOMPLETE = new Set\(\[(.*?)\]\);/s', $js, $m),
            'app.js has no NAME_AUTOCOMPLETE set.'
        );

        preg_match_all("/'([a-z-]+)'/", $m[1], $tokens);

        return $tokens[1];
    }

    /**
     * A box earns the keystroke filter one of two ways, and charPolicy() in
     * app.js reads them in this order: an explicit data-kk-chars, or - failing
     * that - an autocomplete token that says the field holds a person's name.
     * Either is fine; neither is not.
     */
    private function assertRefusesCharactersAsTyped(string $tag, string $where): void
    {
        if (preg_match('/\bdata-kk-chars="([^"]*)"/', $tag, $m)) {
            $this->assertSame(
                'personName',
                $m[1],
                "The {$where} input names the {$m[1]} character policy, not personName."
            );

            return;
        }

        $this->assertSame(
            1,
            preg_match('/\bautocomplete="([^"]*)"/', $tag, $a),
            "The {$where} input has neither data-kk-chars nor an autocomplete token, so nothing refuses a disallowed character as it is typed."
        );

        // autocomplete is a token list and the field type is its last token,
        // which is how charPolicy() reads it.
        $parts = preg_split('/\s+/', trim(strtolower($a[1])), -1, PREG_SPLIT_NO_EMPTY);

        $this->assertContains(
            end($parts),
            $this->inferredNameTokens(),
            "The {$where} input relies on autocomplete=\"{$a[1]}\" for its keystroke filter, but app.js does not infer a name policy from that token."
        );
    }

    /**
     * One whole <input> tag, so an assertion about a field cannot accidentally
     * be satisfied by the next field's attributes.
     *
     * Scanning to the next `>` is not enough: `value="{{ $address->full_name }}"`
     * puts one inside an attribute. Consuming quoted runs whole steps over those.
     */
    private function inputTag(string $view, string $attr, string $value): string
    {
        $blade = file_get_contents(resource_path('views/'.$view));

        preg_match_all('/<input\b(?:[^>"]|"[^"]*")*>/', $blade, $tags);

        foreach ($tags[0] as $tag) {
            if (preg_match('/\b'.preg_quote($attr, '/').'="'.preg_quote($value, '/').'"/', $tag)) {
                return $tag;
            }
        }

        $this->fail("No <input> with {$attr}=\"{$value}\" in {$view}.");
    }

    /**
     * The pattern attribute exactly as the browser will receive it.
     *
     * Every box must echo ValidationRules::namePattern() rather than carry its
     * own copy. Five hand-copied regexes drifted apart once already, and a copy
     * that loses the leading-whitespace clause looks fine and quietly blocks
     * checkout for anyone who pasted their name in.
     */
    private function renderedPattern(string $view, string $attr, string $value): string
    {
        $this->assertSame(
            1,
            preg_match('/\bpattern="([^"]*)"/', $this->inputTag($view, $attr, $value), $m),
            "The {$value} input in {$view} has no pattern attribute."
        );

        $this->assertSame(
            '{{ \App\Rules\ValidationRules::namePattern() }}',
            $m[1],
            "The {$value} input in {$view} carries its own copy of the name pattern instead of echoing the one definition."
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
    public function test_the_box_asks_for_a_charset_and_not_just_a_length(string $view, string $attr, string $value): void
    {
        $tag = $this->inputTag($view, $attr, $value);

        $this->assertRefusesCharactersAsTyped($tag, "{$value} in {$view}");

        $this->assertMatchesRegularExpression(
            '/\btitle="[^"]+"/',
            $tag,
            "The {$value} input in {$view} has a pattern but no title, which leaves the browser to invent the message."
        );
    }

    /**
     * The shape from the bug report: length alone let it through.
     */
    #[DataProvider('nameFields')]
    public function test_the_box_refuses_a_name_of_symbols(string $view, string $attr, string $value): void
    {
        $this->assertDoesNotMatchRegularExpression(
            $this->asPcre($this->renderedPattern($view, $attr, $value)),
            'chirag raw arakn@#@!#q13123123',
            "The {$value} input in {$view} still accepts the reported name."
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

        foreach (self::nameFields() as $label => [$view, $attr, $value]) {
            $this->assertMatchesRegularExpression(
                $this->asPcre($this->renderedPattern($view, $attr, $value)),
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
