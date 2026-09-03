<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * The Add/Edit Store form used to validate its contact fields as
 * 'nullable|string|max:20' and 'nullable|email', so a store saved cleanly with
 * "Yucgguctu65_533" in the phone box and "jcuttuctufitd.@gmail" in the email
 * box. Both fields now run the shared rules the rest of the admin uses.
 */
class StoreContactValidationTest extends TestCase
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

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Testing',
            'code' => 'STORE001',
            'address' => 'Testing Road 4',
            'phone' => '9876543210',
            'email' => 'store@example.com',
            'is_active' => 1,
        ], $overrides);
    }

    public function test_a_valid_store_is_created(): void
    {
        $response = $this->post(route('admin.stores.store'), $this->payload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.stores.index'));
        $this->assertDatabaseHas('stores', ['code' => 'STORE001', 'phone' => '9876543210']);
    }

    /**
     * The exact value from the bug report, plus the shapes either side of it.
     *
     * @return array<string, array{0: string}>
     */
    public static function badPhones(): array
    {
        return [
            'letters and symbols' => ['Yucgguctu65_533'],
            'letters only' => ['sasadasdasdsada'],
            'starts with 5' => ['5876543210'],
            'starts with 1' => ['1234567890'],
            'nine digits' => ['987654321'],
            'eleven digits' => ['98765432109'],
            'repdigit' => ['9999999999'],
        ];
    }

    /** @dataProvider badPhones */
    public function test_a_phone_that_is_not_an_indian_mobile_is_rejected(string $phone): void
    {
        $response = $this->post(route('admin.stores.store'), $this->payload(['phone' => $phone]));

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('stores', 0);
    }

    /**
     * A number the admin typed with decoration is the same store line, so it is
     * accepted and stored as the bare ten digits.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function decoratedPhones(): array
    {
        return [
            'plus 91 spaced' => ['+91 98765 43210', '9876543210'],
            'ninety one' => ['919876543210', '9876543210'],
            'leading zero' => ['098765-43210', '9876543210'],
            'hyphenated' => ['98765-43210', '9876543210'],
        ];
    }

    /** @dataProvider decoratedPhones */
    public function test_a_decorated_number_is_accepted_and_stored_bare(string $typed, string $stored): void
    {
        $response = $this->post(route('admin.stores.store'), $this->payload(['phone' => $typed]));

        $response->assertSessionHasNoErrors();
        $this->assertSame($stored, Store::firstOrFail()->phone);
    }

    /** @return array<string, array{0: string}> */
    public static function badEmails(): array
    {
        return [
            'no tld' => ['jcuttuctufitd.@gmail'],
            'trailing dot in local part' => ['jcuttuctufitd.@gmail.com'],
            'no at sign' => ['jcuttuctufitdgmail.com'],
            'double at' => ['a@b@example.com'],
            'folded whitespace' => ['john @gmail.com'],
            'bare word' => ['gmail'],
        ];
    }

    /** @dataProvider badEmails */
    public function test_a_malformed_email_is_rejected(string $email): void
    {
        $response = $this->post(route('admin.stores.store'), $this->payload(['email' => $email]));

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('stores', 0);
    }

    /**
     * Both fields are optional - a store with no published contact line still
     * has to be creatable, which is what `nullable` buys.
     */
    public function test_phone_and_email_may_be_left_empty(): void
    {
        $response = $this->post(
            route('admin.stores.store'),
            $this->payload(['phone' => null, 'email' => null])
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('stores', ['code' => 'STORE001', 'phone' => null, 'email' => null]);
    }

    /**
     * stores.code is varchar(20); the old rule said max:50, so MySQL was left to
     * truncate or reject whatever came past twenty characters.
     */
    public function test_a_code_longer_than_the_column_is_rejected(): void
    {
        $response = $this->post(
            route('admin.stores.store'),
            $this->payload(['code' => str_repeat('A', 21)])
        );

        $response->assertSessionHasErrors('code');
    }

    public function test_a_code_of_punctuation_soup_is_rejected(): void
    {
        $response = $this->post(route('admin.stores.store'), $this->payload(['code' => '!!!///']));

        $response->assertSessionHasErrors('code');
    }

    /**
     * Wider than the inventory-location code charset on purpose: this table
     * already has rows, and a legacy code has to stay editable.
     */
    public function test_a_legacy_style_code_with_a_space_or_slash_is_accepted(): void
    {
        $response = $this->post(route('admin.stores.store'), $this->payload(['code' => 'KK/DEL 01']));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('stores', ['code' => 'KK/DEL 01']);
    }

    public function test_the_name_may_not_carry_markup(): void
    {
        $response = $this->post(
            route('admin.stores.store'),
            $this->payload(['name' => '<script>alert(1)</script>'])
        );

        $response->assertSessionHasErrors('name');
    }

    /**
     * The unique rule has to ignore the row being edited, or a store cannot be
     * saved without also renaming its own code.
     */
    public function test_a_store_keeps_its_own_code_on_edit(): void
    {
        $store = Store::create($this->payload());

        $response = $this->put(
            route('admin.stores.update', $store),
            $this->payload(['name' => 'Testing Renamed'])
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame('Testing Renamed', $store->fresh()->name);
    }

    public function test_a_duplicate_code_is_still_rejected_on_edit(): void
    {
        Store::create($this->payload(['code' => 'STORE001']));
        $second = Store::create($this->payload(['code' => 'STORE002']));

        $response = $this->put(
            route('admin.stores.update', $second),
            $this->payload(['code' => 'STORE001'])
        );

        $response->assertSessionHasErrors('code');
    }

    /** The edit form runs the same rules as create - they share one rule set. */
    public function test_the_edit_form_rejects_a_bad_phone_too(): void
    {
        $store = Store::create($this->payload());

        $response = $this->put(
            route('admin.stores.update', $store),
            $this->payload(['phone' => 'Yucgguctu65_533'])
        );

        $response->assertSessionHasErrors('phone');
        $this->assertSame('9876543210', $store->fresh()->phone);
    }
}
