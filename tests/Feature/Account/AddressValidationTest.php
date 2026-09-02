<?php

namespace Tests\Feature\Account;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A saved address rendered as "!%@#%$ @FDASDF ASDFSFD" over a 200-character
 * unbroken run that burst out of its card and ran across the page. Two separate
 * defects: the form accepted it, and the card could not contain it.
 */
class AddressValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'customer']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Asha Menon',
            'phone' => '9876543210',
            'address_line1' => '12 Rose Street, Sector 7',
            'address_line2' => '',
            'city' => 'Gurgaon',
            'state' => 'Haryana',
            'postal_code' => '122001',
            'country' => 'IN',
            'label' => 'home',
        ], $overrides);
    }

    public function test_a_normal_address_saves(): void
    {
        $this->actingAs($this->user)
            ->post(route('account.addresses.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'city' => 'Gurgaon',
        ]);
    }

    public function test_a_name_of_punctuation_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('account.addresses.store'), $this->payload(['name' => '!%@#%$ @FDASDF ASDFSFD']))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('user_addresses', 0);
    }

    public function test_a_name_longer_than_the_columns_behind_it_is_rejected(): void
    {
        // first_name and last_name are both varchar(50); the single "name" field
        // is split across them, so 100 valid characters with no space overflows.
        $this->actingAs($this->user)
            ->post(route('account.addresses.store'), $this->payload(['name' => str_repeat('a', 80)]))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('user_addresses', 0);
    }

    public function test_a_street_longer_than_its_column_is_rejected(): void
    {
        // AddressLine deliberately allows Unicode letters — Indian addresses are
        // written in Devanagari, Tamil and Bengali — so a long run of letters is
        // bounded by the varchar(255) column, not by the charset.
        $this->actingAs($this->user)
            ->post(route('account.addresses.store'), $this->payload(['address_line1' => str_repeat('d', 300)]))
            ->assertSessionHasErrors('address_line1');

        $this->assertDatabaseCount('user_addresses', 0);
    }

    public function test_a_street_of_pure_punctuation_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('account.addresses.store'), $this->payload(['address_line1' => '!!! @@@ ### $$$']))
            ->assertSessionHasErrors('address_line1');
    }

    public function test_a_city_of_symbols_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('account.addresses.store'), $this->payload(['city' => '!%!@#$DJFHO;IF H;FJ']))
            ->assertSessionHasErrors('city');
    }

    public function test_a_non_indian_mobile_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('account.addresses.store'), $this->payload(['phone' => '1234567890']))
            ->assertSessionHasErrors('phone');
    }

    /**
     * The display half. Rows like the one in the report already exist, so the
     * card must contain them regardless of what validation now rejects.
     */
    public function test_the_card_can_contain_an_address_that_is_already_junk(): void
    {
        UserAddress::create([
            'user_id' => $this->user->id,
            'label' => 'home',
            'first_name' => 'Junk',
            'last_name' => 'Row',
            'phone' => '9876543210',
            'address_line_1' => str_repeat('d', 250),
            'city' => 'Indore',
            'state' => 'Madhya Pradesh',
            'postal_code' => '456321',
            'country' => 'IN',
            'is_default' => true,
        ]);

        $html = $this->actingAs($this->user)
            ->get(route('account.addresses.index'))
            ->assertOk()
            ->getContent();

        // The grid item must be allowed to shrink, and the text must break
        // mid-token — break-words alone would not wrap a 300-char run.
        $this->assertStringContainsString('min-w-0', $html,
            'The address card must be able to shrink below its content, or it pushes out of the grid column.');
        $this->assertStringContainsString('wrap-anywhere', $html,
            'Long unbroken address text must wrap inside the card.');
    }
}
