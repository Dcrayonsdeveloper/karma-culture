<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\NameCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\VerifiesSignupEmails;
use Tests\TestCase;

/**
 * One spelling in the column, the right one on the screen.
 *
 * users.first_name and users.last_name hold lower case whatever the customer's
 * shift key was doing, and every read puts the capitals back - so the store has
 * a single canonical spelling per name and the customer still sees "Priyanshu
 * Verma".
 *
 * Both halves are asserted separately on purpose. assertDatabaseHas alone
 * cannot prove the fold happened: the connection collation is
 * utf8mb4_unicode_ci, so a row still stored as "Priyanshu" would match a
 * lower-case expectation and the test would pass while the rule was broken.
 * getRawOriginal() reads the column past the accessor, which is the only way to
 * see what is actually there.
 */
class NameCasingTest extends TestCase
{
    use RefreshDatabase, VerifiesSignupEmails;

    public function test_signup_stores_the_name_in_lower_case(): void
    {
        $this->verifiedSignupEmail('priyanshu@example.test');

        $this->post('/register', [
            'full_name' => 'PRIYANSHU verma',
            'email' => 'priyanshu@example.test',
            'phone' => '9876543210',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertRedirect(route('login'));

        $user = User::where('email', 'priyanshu@example.test')->firstOrFail();

        $this->assertSame('priyanshu', $user->getRawOriginal('first_name'));
        $this->assertSame('verma', $user->getRawOriginal('last_name'));
    }

    /** However it was typed, it comes back the one way. */
    public function test_a_stored_name_is_shown_with_its_capitals(): void
    {
        $user = User::factory()->create([
            'first_name' => 'PRIYANSHU',
            'last_name' => 'verma',
        ]);

        $this->assertSame('Priyanshu', $user->first_name);
        $this->assertSame('Verma', $user->last_name);
        $this->assertSame('Priyanshu Verma', $user->full_name);

        // And after a round trip through the database, not just in memory.
        $this->assertSame('Priyanshu Verma', $user->fresh()->full_name);
    }

    /**
     * The profile form reads the display casing and writes it straight back.
     * If the mutator only ran on create, this is where mixed case would leak in.
     */
    public function test_editing_a_profile_folds_the_name_again(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $user->update(['first_name' => 'Priyanshu', 'last_name' => 'Verma']);

        $this->assertSame('priyanshu', $user->fresh()->getRawOriginal('first_name'));
        $this->assertSame('verma', $user->fresh()->getRawOriginal('last_name'));
    }

    /**
     * An account created from a single-word name has last_name = '', and a
     * NULL is allowed there too. Neither may turn into the other, and neither
     * may become the string "0" or a stray space.
     */
    public function test_an_empty_or_missing_last_name_survives_both_halves(): void
    {
        $user = User::factory()->create(['first_name' => 'dev', 'last_name' => '']);

        $this->assertSame('', $user->fresh()->last_name);
        $this->assertNull(NameCase::store(null));
        $this->assertNull(NameCase::display(null));
    }

    /**
     * A capital goes after every separator a name is allowed to contain, not
     * only after a space - or "Mary-Anne" prints as "Mary-anne".
     *
     * @dataProvider names
     */
    public function test_display_capitalises_after_every_name_separator(string $stored, string $shown): void
    {
        $this->assertSame($shown, NameCase::display($stored));
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function names(): array
    {
        return [
            'plain space' => ['priyanshu verma', 'Priyanshu Verma'],
            'hyphen' => ['mary-anne d’souza', 'Mary-Anne D’Souza'],
            'apostrophe' => ["patrick o'connor", "Patrick O'Connor"],
            'initials' => ['j.p. mehta', 'J.P. Mehta'],
            'non-breaking space' => ["asha\u{00A0}menon", "Asha\u{00A0}Menon"],
            'already capitalised' => ['Asha Menon', 'Asha Menon'],
            'shouted' => ['ASHA MENON', 'Asha Menon'],
            // Devanagari has no letter case, so both halves leave it alone.
            'no cased letters' => ['रवि कुमार', 'रवि कुमार'],
        ];
    }
}
