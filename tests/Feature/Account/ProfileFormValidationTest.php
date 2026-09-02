<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Profile Settings writes straight into the users table, so its rules have to
 * agree with the ones registration applies when those same columns are first
 * populated. Where the two disagreed, this form was the looser way in.
 */
class ProfileFormValidationTest extends TestCase
{
    use RefreshDatabase;

    private function valid(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Priya',
            'last_name' => 'Sharma',
            'email' => 'priya@example.com',
            'phone' => '9876543210',
        ], $overrides);
    }

    private function submit(array $data, ?User $as = null)
    {
        return $this->actingAs($as ?? User::factory()->create())
            ->put(route('account.profile.update'), $data);
    }

    public function test_a_complete_profile_is_accepted(): void
    {
        $this->submit($this->valid())->assertSessionHasNoErrors();
    }

    /** @return array<string, array{string, string}> */
    public static function rejectedValues(): array
    {
        return [
            'first name too short' => ['first_name', 'A'],
            'first name with digits' => ['first_name', 'dev123'],
            'last name with digits' => ['last_name', 'Sharma99'],
            'email with no TLD' => ['email', 'dev@gmail'],
            'email with folded whitespace' => ['email', 'dev @gmail.com'],
            'phone starting below 6' => ['phone', '5876543210'],
            'phone too short' => ['phone', '98765432'],
            'phone with letters' => ['phone', '98765abcde'],
            'phone repdigits' => ['phone', '9999999999'],
        ];
    }

    /**
     * @dataProvider rejectedValues
     */
    public function test_the_server_rejects_bad_values(string $field, string $value): void
    {
        $this->submit($this->valid([$field => $value]))->assertSessionHasErrors($field);
    }

    public function test_a_name_longer_than_the_column_is_rejected_not_truncated(): void
    {
        // first_name/last_name are varchar(50). The old max:255 let a longer
        // value reach the column.
        $this->submit($this->valid(['first_name' => str_repeat('a', 51)]))
            ->assertSessionHasErrors('first_name');
    }

    public function test_last_name_may_be_left_empty(): void
    {
        // Sign-up splits one name field on the first space, so "dev" creates an
        // account with no last name. That account must still be able to save.
        $user = User::factory()->create(['last_name' => '']);

        $this->submit($this->valid(['last_name' => null]), $user)
            ->assertSessionHasNoErrors();

        $this->assertSame('', $user->fresh()->last_name);
    }

    public function test_phone_is_stored_canonicalised(): void
    {
        $user = User::factory()->create();

        $this->submit($this->valid(['phone' => '+91 98765 43210']), $user)
            ->assertSessionHasNoErrors();

        $this->assertSame('9876543210', $user->fresh()->phone);
    }

    public function test_phone_may_be_cleared(): void
    {
        $user = User::factory()->create(['phone' => '9876543210']);

        $this->submit($this->valid(['phone' => null]), $user)
            ->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->phone);
    }

    public function test_a_phone_already_on_another_account_is_rejected(): void
    {
        User::factory()->create(['phone' => '9876543210']);

        // Registration checks this; nothing checked it here, so the profile
        // form was the way around it.
        $this->submit($this->valid(['phone' => '+91 98765 43210']))
            ->assertSessionHasErrors('phone');
    }

    public function test_the_owner_may_resubmit_their_own_phone(): void
    {
        $user = User::factory()->create(['phone' => '9876543210']);

        $this->submit($this->valid(['phone' => '9876543210']), $user)
            ->assertSessionHasNoErrors();
    }

    public function test_an_email_already_on_another_account_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->submit($this->valid(['email' => 'taken@example.com']))
            ->assertSessionHasErrors('email');
    }

    public function test_the_owner_may_resubmit_their_own_email(): void
    {
        $user = User::factory()->create(['email' => 'mine@example.com']);

        $this->submit($this->valid(['email' => 'mine@example.com']), $user)
            ->assertSessionHasNoErrors();
    }

    public function test_the_form_carries_the_matching_browser_rules(): void
    {
        // A browser rule looser than the server's means the customer only
        // learns the rule after losing the page.
        $response = $this->actingAs(User::factory()->create())
            ->get(route('account.profile'));

        $response->assertStatus(200);
        $response->assertSee('maxlength="50"', false);   // varchar(50) name columns
        $response->assertSee('maxlength="255"', false);  // email
        $response->assertSee('pattern="(\+?91[\s\-]?)?0?[6-9][0-9\s\-]{9,}"', false);
    }
}
