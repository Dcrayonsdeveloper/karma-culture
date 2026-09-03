<?php

namespace Tests\Unit\Validation;

use App\Rules\IndianMobile;
use App\Rules\ValidationRules as V;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The contract for App\Rules\ValidationRules.
 *
 * Deliberately no RefreshDatabase: none of these rule sets touch the database,
 * and the suite must stay runnable without one. ValidationRules::foreignId()
 * is asserted on its shape rather than executed, for the same reason.
 */
class ValidationRulesTest extends TestCase
{
    private function passes(array $rules, mixed $value): bool
    {
        return ! Validator::make(['field' => $value], ['field' => $rules])->fails();
    }

    private function assertAccepts(array $rules, mixed $value, string $label = ''): void
    {
        $this->assertTrue(
            $this->passes($rules, $value),
            sprintf('Expected %s to be accepted%s', var_export($value, true), $label ? " ({$label})" : '')
        );
    }

    private function assertRejects(array $rules, mixed $value, string $label = ''): void
    {
        $this->assertFalse(
            $this->passes($rules, $value),
            sprintf('Expected %s to be rejected%s', var_export($value, true), $label ? " ({$label})" : '')
        );
    }

    // ------------------------------------------------------------------
    // name()
    // ------------------------------------------------------------------

    public static function validNames(): array
    {
        return [
            'two words' => ['John Doe'],
            'hyphenated' => ['Mary-Anne'],
            'apostrophe' => ["O'Connor"],
            'curly apostrophe' => ['Anne-Marie O’Brien'],
            'accented' => ['José'],
            'indian' => ['Ravi Kumar'],
            'devanagari' => ['रवि कुमार'],
            'cjk' => ['山田太郎'],
            'tamil' => ['முருகன்'],
            'arabic' => ['محمد الصايغ'],
            'abbreviated' => ['St. John'],
            'two letters' => ['Ng'],
            'particle' => ['Muhammad al-Sayyid'],
            // Exactly 100 characters, alternating so the repeated-letter guard
            // does not fire and this really tests the max boundary.
            'hundred chars' => [str_repeat('ab', 50)],
        ];
    }

    #[DataProvider('validNames')]
    public function test_name_accepts_real_names(string $name): void
    {
        $this->assertAccepts(V::name(), $name);
    }

    public static function invalidNames(): array
    {
        return [
            'digits only' => ['12345'],
            'letters and digits' => ['John123'],
            'leading symbol' => ['@John'],
            'punctuation only' => ['!!!'],
            'whitespace only' => ['   '],
            'empty' => [''],
            'script tag' => ['<script>alert(1)</script>'],
            'bare tag' => ['John<b>'],
            'entity' => ['John&amp;'],
            'url with scheme' => ['http://evil.com'],
            'url with www' => ['www.evil.com'],
            'bare domain' => ['evil.com'],
            'emoji' => ['John 😀'],
            'five repeats' => ['Aaaaaa'],
            'single char' => ['a'],
            // 101 characters, alternating, so length is the only thing wrong.
            'too long' => [str_repeat('ab', 50).'c'],
            'underscore' => ['John_Doe'],
            'newline injection' => ["John\nBcc: evil@example.com"],
        ];
    }

    #[DataProvider('invalidNames')]
    public function test_name_rejects_junk(string $name): void
    {
        $this->assertRejects(V::name(), $name);
    }

    #[Test]
    public function name_can_be_optional(): void
    {
        $this->assertAccepts(V::name(required: false), null);
        $this->assertRejects(V::name(required: false), 'John123', 'optional still validates the shape');
    }

    // ------------------------------------------------------------------
    // email()
    // ------------------------------------------------------------------

    public static function validEmails(): array
    {
        return [
            'dotted local' => ['john.doe@example.com'],
            'plus tag' => ['john.doe+tag@sub.example.co.uk'],
            'apostrophe' => ["o'neil@example.com"],
            'short' => ['a@b.co'],
            'digits' => ['user123@example.in'],
            'hyphen domain' => ['user@my-shop.example.com'],
        ];
    }

    #[DataProvider('validEmails')]
    public function test_email_accepts_real_addresses(string $email): void
    {
        $this->assertAccepts(V::email(), $email);
    }

    public static function invalidEmails(): array
    {
        return [
            'no domain' => ['john'],
            'trailing at' => ['john@'],
            'no local part' => ['@gmail.com'],
            'inner space' => ['john @gmail.com'],
            'no tld' => ['john@gmail'],
            'double at' => ['john@@gmail.com'],
            'double dot' => ['john..doe@gmail.com'],
            'empty' => [''],
            'whitespace only' => ['   '],
            'html' => ['<script>@example.com'],
        ];
    }

    #[DataProvider('invalidEmails')]
    public function test_email_rejects_malformed_addresses(string $email): void
    {
        $this->assertRejects(V::email(), $email);
    }

    #[Test]
    public function email_is_capped_at_255_characters(): void
    {
        $long = str_repeat('a', 250).'@example.com';

        $this->assertRejects(V::email(), $long);
    }

    // ------------------------------------------------------------------
    // email(strictShape: true) - App\Rules\EmailAddress
    // ------------------------------------------------------------------

    /**
     * Every one of these is legal RFC mail that `email:strict` alone accepts,
     * or a shape it happens to catch anyway. None of them is an address a
     * provider issues, and signup is where an address is minted.
     */
    public static function shapesNoProviderIssues(): array
    {
        return [
            'leading underscore' => ['_asha@example.com'],
            'leading dot' => ['.asha@example.com'],
            'leading hyphen' => ['-asha@example.com'],
            'leading plus' => ['+asha@example.com'],
            'all symbols' => ['!!!@example.com'],
            'trailing dot in local' => ['asha.@example.com'],
            'double dot in local' => ['asha..menon@example.com'],
            'double dot in domain' => ['asha@example..com'],
            'hyphen opening a label' => ['asha@-example.com'],
            'hyphen closing a label' => ['asha@example-.com'],
            'one letter tld' => ['asha@example.c'],
            'numeric tld' => ['asha@example.123'],
            'underscore in domain' => ['asha@exa_mple.com'],
        ];
    }

    #[DataProvider('shapesNoProviderIssues')]
    public function test_strict_shape_rejects_addresses_no_provider_issues(string $email): void
    {
        $this->assertRejects(V::email(strictShape: true), $email);
    }

    /**
     * The other half: a stricter rule that costs real addresses is worse than
     * the looseness it fixed, so the whole valid set has to survive it.
     */
    #[DataProvider('validEmails')]
    public function test_strict_shape_keeps_every_real_address(string $email): void
    {
        $this->assertAccepts(V::email(strictShape: true), $email);
    }

    /**
     * And it stays opt-in. Sign-in matches an address rather than creating one,
     * so anyone whose stored address predates this rule has to keep being able
     * to type it.
     */
    #[Test]
    public function the_default_email_rules_are_untouched_by_it(): void
    {
        $this->assertAccepts(V::email(), '_asha@example.com', 'sign-in keeps the RFC shape');
    }

    #[Test]
    public function normalize_email_lowercases_and_trims(): void
    {
        $this->assertSame('john.doe@example.com', V::normalizeEmail('  John.Doe@Example.COM '));
        $this->assertNull(V::normalizeEmail(null));
        $this->assertNull(V::normalizeEmail('   '));
    }

    // ------------------------------------------------------------------
    // mobile()
    // ------------------------------------------------------------------

    public static function validMobiles(): array
    {
        return [
            'bare' => ['9876543210', '9876543210'],
            'starts with 6' => ['6123456789', '6123456789'],
            'starts with 7' => ['7012345678', '7012345678'],
            'starts with 8' => ['8012345678', '8012345678'],
            'plus 91' => ['+919876543210', '9876543210'],
            'plus 91 spaced' => ['+91 98765 43210', '9876543210'],
            'plus 91 hyphen' => ['+91-98765-43210', '9876543210'],
            'bare 91' => ['919876543210', '9876543210'],
            'trunk zero' => ['09876543210', '9876543210'],
            'zero plus 91' => ['0919876543210', '9876543210'],
            'spaced' => ['98765 43210', '9876543210'],
            'hyphenated' => ['98765-43210', '9876543210'],
            'parenthesised' => ['(98765) 43210', '9876543210'],
        ];
    }

    #[DataProvider('validMobiles')]
    public function test_mobile_accepts_and_normalises(string $input, string $canonical): void
    {
        $this->assertAccepts(V::mobile(), $input);
        $this->assertSame($canonical, IndianMobile::normalize($input));
        $this->assertSame('+91'.$canonical, IndianMobile::toE164($input));
    }

    public static function invalidMobiles(): array
    {
        return [
            'starts with 5' => ['5876543210'],
            'starts with 1' => ['1234567890'],
            'starts with 0' => ['0123456789'],
            'nine digits' => ['987654321'],
            'eleven digits' => ['98765432101'],
            'all zeros' => ['0000000000'],
            'all nines' => ['9999999999'],
            'all sixes' => ['6666666666'],
            'all eights' => ['8888888888'],
            'letters' => ['98765abcde'],
            'empty' => [''],
            'whitespace only' => ['   '],
            'wrong country' => ['+14155552671'],
            'html' => ['<script>alert(1)</script>'],
        ];
    }

    #[DataProvider('invalidMobiles')]
    public function test_mobile_rejects_bad_numbers(string $input): void
    {
        $this->assertRejects(V::mobile(), $input);
        $this->assertNull(IndianMobile::normalize($input));
        $this->assertNull(IndianMobile::toE164($input));
    }

    #[Test]
    public function mobile_can_be_optional(): void
    {
        $this->assertAccepts(V::mobile(required: false), null);
        $this->assertRejects(V::mobile(required: false), '12345', 'optional still validates the shape');
    }

    // ------------------------------------------------------------------
    // pincode()
    // ------------------------------------------------------------------

    #[Test]
    public function pincode_accepts_six_digits_starting_one_to_nine(): void
    {
        foreach (['110001', '400001', '560034', '999999', '100000'] as $pin) {
            $this->assertAccepts(V::pincode(), $pin);
        }
    }

    #[Test]
    public function pincode_rejects_anything_else(): void
    {
        foreach (['012345', '000000', '12345', '1234567', '11000a', '', '   ', '11 0001', '<b>110001</b>'] as $pin) {
            $this->assertRejects(V::pincode(), $pin);
        }
    }

    // ------------------------------------------------------------------
    // money()
    // ------------------------------------------------------------------

    #[Test]
    public function money_accepts_non_negative_two_decimal_amounts(): void
    {
        foreach (['0', '0.00', '10', '10.5', '1499.99', '9999999.99'] as $amount) {
            $this->assertAccepts(V::money(), $amount);
        }
    }

    #[Test]
    public function money_rejects_negatives_precision_and_overflow(): void
    {
        foreach (['-1', '-0.01', '10.555', '1e3', 'abc', '', '10000000', '1,499.99', '<b>1</b>'] as $amount) {
            $this->assertRejects(V::money(), $amount);
        }
    }

    #[Test]
    public function money_bounds_are_configurable(): void
    {
        $this->assertAccepts(V::money(max: 50), '49.99');
        $this->assertRejects(V::money(max: 50), '50.01');
    }

    // ------------------------------------------------------------------
    // percentage()
    // ------------------------------------------------------------------

    #[Test]
    public function percentage_accepts_zero_to_one_hundred_inclusive(): void
    {
        foreach (['0', '0.5', '17.25', '99.99', '100'] as $pct) {
            $this->assertAccepts(V::percentage(), $pct);
        }
    }

    #[Test]
    public function percentage_rejects_out_of_range(): void
    {
        foreach (['-1', '100.01', '101', '1000', 'abc', '', '12.345'] as $pct) {
            $this->assertRejects(V::percentage(), $pct);
        }
    }

    // ------------------------------------------------------------------
    // quantity()
    // ------------------------------------------------------------------

    #[Test]
    public function quantity_accepts_positive_whole_numbers(): void
    {
        foreach (['1', '2', '999', 5] as $qty) {
            $this->assertAccepts(V::quantity(), $qty);
        }
    }

    #[Test]
    public function quantity_rejects_zero_negatives_fractions_and_overflow(): void
    {
        foreach (['0', '-1', '1.5', '1000', 'abc', '', '1e2'] as $qty) {
            $this->assertRejects(V::quantity(), $qty);
        }
    }

    #[Test]
    public function quantity_max_is_configurable(): void
    {
        $this->assertAccepts(V::quantity(max: 10), '10');
        $this->assertRejects(V::quantity(max: 10), '11');
    }

    // ------------------------------------------------------------------
    // text() / textarea()
    // ------------------------------------------------------------------

    #[Test]
    public function text_accepts_ordinary_prose(): void
    {
        foreach ([
            'Order enquiry',
            'Size 8 please — the blue one',
            'Cost is < 500 rupees',
            '5 & 6 both work',
            'क्या यह उपलब्ध है?',
            "O'Brien's order #1234",
        ] as $value) {
            $this->assertAccepts(V::text(), $value);
        }
    }

    #[Test]
    public function text_rejects_markup_and_script(): void
    {
        foreach ([
            '<script>alert(1)</script>',
            '<img src=x onerror=alert(1)>',
            '<b>bold</b>',
            '&lt;script&gt;alert(1)&lt;/script&gt;',
            'javascript:alert(1)',
            'java script:alert(1)',
            'vbscript:msgbox(1)',
            'data:text/html;base64,PHNjcmlwdD4=',
            '<!-- comment -->',
        ] as $value) {
            $this->assertRejects(V::text(), $value);
        }
    }

    #[Test]
    public function required_text_rejects_blank_input(): void
    {
        foreach (['', '   ', "\t\n", "\u{00A0}\u{00A0}", "\u{200B}"] as $value) {
            $this->assertRejects(V::text(), $value, 'whitespace-only must not satisfy required');
        }
    }

    #[Test]
    public function text_length_bounds_apply(): void
    {
        $this->assertAccepts(V::text(max: 10), '0123456789');
        $this->assertRejects(V::text(max: 10), '01234567890');
        $this->assertRejects(V::text(min: 5), 'abcd');
    }

    #[Test]
    public function textarea_accepts_multiline_prose_and_rejects_markup(): void
    {
        $this->assertAccepts(V::textarea(), "Line one\nLine two\n\nLine four");
        $this->assertRejects(V::textarea(), "Fine here\n<script>alert(1)</script>");
        $this->assertRejects(V::textarea(), '   ');
        $this->assertAccepts(V::textarea(required: false), null);
        $this->assertRejects(V::textarea(max: 20), str_repeat('a', 21));
    }

    // ------------------------------------------------------------------
    // addressLine()
    // ------------------------------------------------------------------

    #[Test]
    public function address_line_accepts_real_addresses(): void
    {
        foreach ([
            '221B Baker Street',
            'Flat 3B, #12/4 M.G. Road',
            'Plot No. 45 (Opp. Metro Station)',
            'C/O Ramesh & Sons',
            'Sector-18, Noida',
            'द्वारका सेक्टर 21',
            "O'Shea Complex, 2nd Floor",
        ] as $value) {
            $this->assertAccepts(V::addressLine(), $value);
        }
    }

    #[Test]
    public function address_line_rejects_markup_and_empty_shapes(): void
    {
        foreach ([
            '<script>alert(1)</script>',
            '<b>221B</b>',
            'javascript:alert(1)',
            '221B "Baker" Street',
            '   ',
            '',
            ',,,,',
            '---',
            'ab',
        ] as $value) {
            $this->assertRejects(V::addressLine(), $value);
        }
    }

    // ------------------------------------------------------------------
    // url()
    // ------------------------------------------------------------------

    #[Test]
    public function url_accepts_http_and_https_only(): void
    {
        $this->assertAccepts(V::url(), 'https://example.com');
        $this->assertAccepts(V::url(), 'http://example.com/path?q=1#frag');
        $this->assertAccepts(V::url(), 'https://sub.example.co.in/a/b');
    }

    #[Test]
    public function url_rejects_script_and_other_schemes(): void
    {
        foreach ([
            'javascript:alert(1)',
            'JavaScript:alert(1)',
            'data:text/html;base64,PHNjcmlwdD4=',
            'ftp://example.com',
            'file:///etc/passwd',
            '//example.com',
            'example.com',
            'not a url',
            '',
        ] as $value) {
            $this->assertRejects(V::url(), $value);
        }
    }

    #[Test]
    public function url_can_be_optional(): void
    {
        $this->assertAccepts(V::url(required: false), null);
        $this->assertRejects(V::url(required: false), 'javascript:alert(1)');
    }

    // ------------------------------------------------------------------
    // password()
    // ------------------------------------------------------------------

    #[Test]
    public function password_enforces_the_site_wide_policy(): void
    {
        $rules = ['password' => V::password()];

        $ok = Validator::make(
            ['password' => 'Correct-Horse1', 'password_confirmation' => 'Correct-Horse1'],
            $rules
        );
        $this->assertFalse($ok->fails(), 'Ten or more characters with all four classes should pass');

        $short = Validator::make(
            ['password' => 'Ab1!', 'password_confirmation' => 'Ab1!'],
            $rules
        );
        $this->assertTrue($short->fails(), 'Fewer than 10 characters should fail');

        // Nine characters with all four classes: the case that separates the
        // ten-character minimum from the eight it replaced, and the one that
        // would silently pass again if the callback in AppServiceProvider were
        // ever dropped and Password::defaults() fell back to Laravel's own.
        $nine = Validator::make(
            ['password' => 'Ab1!defgh', 'password_confirmation' => 'Ab1!defgh'],
            $rules
        );
        $this->assertTrue($nine->fails(), 'Nine characters should fail the ten-character minimum');

        $mismatch = Validator::make(
            ['password' => 'Correct-Horse1', 'password_confirmation' => 'Battery-Staple2'],
            $rules
        );
        $this->assertTrue($mismatch->fails(), 'A mismatched confirmation should fail');
    }

    /**
     * The policy lives in one place - the Password::defaults() callback in
     * AppServiceProvider - so this pins each requirement rather than the
     * wording of any one form.
     */
    #[Test]
    public function password_requires_mixed_case_a_number_and_a_symbol(): void
    {
        $rules = ['password' => V::password(confirmed: false)];

        foreach ([
            'no uppercase' => 'lowercase1!',
            'no lowercase' => 'UPPERCASE1!',
            'no number' => 'NoDigitsHere!',
            'no symbol' => 'NoSymbol123',
        ] as $why => $password) {
            $this->assertTrue(
                Validator::make(['password' => $password], $rules)->fails(),
                "A password with {$why} should be rejected"
            );
        }
    }

    /**
     * The four classes are required, but the character set is NOT closed to
     * them. A regex like [A-Za-z\d@$!%*?&]{8,} would reject this password for
     * containing '#' and '_' - characters that make it stronger, not weaker,
     * and that password managers emit by default.
     */
    #[Test]
    public function password_accepts_symbols_outside_the_common_shortlist(): void
    {
        $v = Validator::make(
            ['password' => 'Str#ng_Pass1'],
            ['password' => V::password(confirmed: false)]
        );

        $this->assertFalse($v->fails(), 'Symbols beyond @$!%*?& must be accepted');
    }

    #[Test]
    public function password_confirmation_can_be_waived(): void
    {
        $v = Validator::make(['password' => 'Correct-Horse1'], ['password' => V::password(confirmed: false)]);

        $this->assertFalse($v->fails());
    }

    // ------------------------------------------------------------------
    // image() / document()
    // ------------------------------------------------------------------

    #[Test]
    public function image_accepts_real_images(): void
    {
        foreach (['photo.jpg', 'photo.jpeg', 'photo.png', 'photo.webp'] as $name) {
            $this->assertAccepts(V::image(), UploadedFile::fake()->image($name, 800, 600), $name);
        }
    }

    #[Test]
    public function image_rejects_disguised_and_oversized_files(): void
    {
        $this->assertRejects(
            V::image(),
            UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
            'a script is not an image'
        );

        $this->assertRejects(
            V::image(),
            UploadedFile::fake()->create('payload.png', 10, 'application/x-php'),
            'an image extension over a script mime must fail'
        );

        $this->assertRejects(
            V::image(),
            UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
            'a pdf is not an image'
        );

        $this->assertRejects(
            V::image(maxKb: 100),
            UploadedFile::fake()->image('huge.jpg', 200, 200)->size(200),
            'over the size ceiling'
        );

        $this->assertRejects(
            V::image(maxWidth: 500, maxHeight: 500),
            UploadedFile::fake()->image('wide.png', 900, 100),
            'over the dimension ceiling'
        );
    }

    #[Test]
    public function image_gif_is_opt_in(): void
    {
        $gif = UploadedFile::fake()->image('anim.gif', 100, 100);

        $this->assertRejects(V::image(), $gif);
        $this->assertAccepts(V::image(allowGif: true), UploadedFile::fake()->image('anim.gif', 100, 100));
    }

    #[Test]
    public function image_can_be_optional(): void
    {
        $this->assertAccepts(V::image(required: false), null);
    }

    #[Test]
    public function document_accepts_expected_types_and_rejects_others(): void
    {
        $this->assertAccepts(V::document(), UploadedFile::fake()->create('invoice.pdf', 20, 'application/pdf'));
        $this->assertAccepts(V::document(), UploadedFile::fake()->image('scan.jpg', 400, 400));

        $this->assertRejects(V::document(), UploadedFile::fake()->create('payload.php', 10, 'application/x-php'));
        $this->assertRejects(V::document(), UploadedFile::fake()->create('archive.zip', 10, 'application/zip'));
        $this->assertRejects(
            V::document(maxKb: 50),
            UploadedFile::fake()->create('big.pdf', 100, 'application/pdf'),
            'over the size ceiling'
        );
    }

    // ------------------------------------------------------------------
    // option() / boolean() / accepted() / foreignId()
    // ------------------------------------------------------------------

    #[Test]
    public function option_restricts_to_the_allowed_set(): void
    {
        $rules = V::option(['pending', 'shipped', 'delivered']);

        $this->assertAccepts($rules, 'shipped');
        $this->assertRejects($rules, 'cancelled');
        $this->assertRejects($rules, '');
        $this->assertRejects($rules, '<script>alert(1)</script>');
    }

    #[Test]
    public function boolean_and_accepted_behave_as_expected(): void
    {
        $this->assertAccepts(V::boolean(), '1');
        $this->assertAccepts(V::boolean(), '0');
        $this->assertAccepts(V::boolean(), null);
        $this->assertRejects(V::boolean(), 'yes');

        $this->assertAccepts(V::accepted(), 'on');
        $this->assertAccepts(V::accepted(), '1');
        $this->assertRejects(V::accepted(), null);
        $this->assertRejects(V::accepted(), '0');
    }

    // ------------------------------------------------------------------
    // scheduleStart() / scheduleEnd()
    // ------------------------------------------------------------------

    /** The shape the datetime-local inputs on the admin forms actually post. */
    private function moment(string $modifier): string
    {
        return now()->modify($modifier)->format('Y-m-d\TH:i');
    }

    /** Run a start/end pair through the rules the admin forms use. */
    private function schedule(array $data, mixed $currentStart = null, mixed $currentEnd = null): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, [
            'starts_at' => V::scheduleStart(required: false, current: $currentStart),
            'ends_at' => V::scheduleEnd('starts_at', required: false, current: $currentEnd),
        ]);
    }

    #[Test]
    public function schedule_accepts_a_window_that_opens_and_closes_in_the_future(): void
    {
        $validator = $this->schedule([
            'starts_at' => $this->moment('+1 day'),
            'ends_at' => $this->moment('+1 week'),
        ]);

        $this->assertFalse($validator->fails(), (string) $validator->errors());
    }

    #[Test]
    public function schedule_rejects_an_end_at_or_before_the_start(): void
    {
        $moment = $this->moment('+3 days');

        $this->assertTrue($this->schedule([
            'starts_at' => $moment,
            'ends_at' => $this->moment('+1 day'),
        ])->fails(), 'an end before the start must be rejected');

        $this->assertTrue($this->schedule([
            'starts_at' => $moment,
            'ends_at' => $moment,
        ])->fails(), 'a window that opens and closes on the same minute must be rejected');
    }

    #[Test]
    public function schedule_rejects_dates_in_the_past(): void
    {
        $this->assertTrue($this->schedule(['starts_at' => $this->moment('-1 day')])->fails());

        // With no start to sit after, an expiry still has to be in the future.
        $this->assertTrue($this->schedule(['ends_at' => $this->moment('-1 hour')])->fails());
    }

    #[Test]
    public function schedule_accepts_the_current_minute(): void
    {
        // datetime-local has minute granularity: picking the current minute and
        // submitting thirty seconds later is not choosing a past time.
        $validator = $this->schedule([
            'starts_at' => now()->format('Y-m-d\TH:i'),
            'ends_at' => $this->moment('+1 day'),
        ]);

        $this->assertFalse($validator->fails(), (string) $validator->errors());
    }

    #[Test]
    public function schedule_leaves_a_stored_past_date_alone_but_will_not_take_a_new_one(): void
    {
        // The edit-form case: a coupon that started last week must stay saveable
        // without its schedule being dragged forward.
        $storedStart = now()->subWeek()->startOfMinute();
        $storedEnd = now()->addWeek()->startOfMinute();

        $unchanged = $this->schedule([
            'starts_at' => $storedStart->format('Y-m-d\TH:i'),
            'ends_at' => $storedEnd->format('Y-m-d\TH:i'),
        ], $storedStart, $storedEnd);

        $this->assertFalse($unchanged->fails(), (string) $unchanged->errors());

        $movedBack = $this->schedule([
            'starts_at' => $this->moment('-1 month'),
            'ends_at' => $storedEnd->format('Y-m-d\TH:i'),
        ], $storedStart, $storedEnd);

        $this->assertTrue($movedBack->fails(), 'a changed start still has to be in the future');

        // And the stored Carbon must survive being compared against.
        $this->assertSame(
            now()->subWeek()->startOfMinute()->format('Y-m-d H:i:s'),
            $storedStart->format('Y-m-d H:i:s')
        );
    }

    #[Test]
    public function schedule_leaves_emptiness_and_format_to_the_rules_that_own_them(): void
    {
        $this->assertFalse($this->schedule([])->fails(), 'both ends are optional here');

        $this->assertSame('required', V::scheduleStart()[0]);
        $this->assertSame('nullable', V::scheduleStart(required: false)[0]);
        $this->assertContains('after:starts_at', V::scheduleEnd('starts_at'));

        // A value that is not a date is the `date` rule's business, and it must
        // not also collect a "cannot be set in the past" message.
        $errors = $this->schedule(['starts_at' => 'lastweek'])->errors()->get('starts_at');
        $this->assertCount(1, $errors);
    }

    #[Test]
    public function foreign_id_builds_an_exists_rule_without_trusting_the_client(): void
    {
        $rules = V::foreignId('categories');

        $this->assertSame('required', $rules[0]);
        $this->assertSame('integer', $rules[1]);
        $this->assertSame('min:1', $rules[2]);
        $this->assertStringStartsWith('exists:categories,id', (string) $rules[3]);

        $this->assertSame('nullable', V::foreignId('categories', required: false)[0]);
    }
}
