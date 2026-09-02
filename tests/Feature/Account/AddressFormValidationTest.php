<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The address form is validated twice: once in the browser, once in the
 * controller. When the two disagree the browser lets a value through and the
 * server bounces it, so the customer only learns the rule after losing the
 * page. These pin the pairs that were out of step.
 */
class AddressFormValidationTest extends TestCase
{
    use RefreshDatabase;

    private function valid(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Priya Sharma',
            'phone' => '9876543210',
            'address_line1' => '12 Residency Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'postal_code' => '560025',
            'country' => 'IN',
            'label' => 'home',
        ], $overrides);
    }

    private function submitAddress(array $data)
    {
        return $this->actingAs(User::factory()->create())
            ->post(route('account.addresses.store'), $data);
    }

    public function test_a_complete_address_is_accepted(): void
    {
        $this->submitAddress($this->valid())->assertSessionHasNoErrors();
    }

    /** @return array<string, array{string, string}> */
    public static function rejectedValues(): array
    {
        return [
            'name too short' => ['name', 'A'],
            'street too short' => ['address_line1', '12'],
            'city too short' => ['city', 'B'],
            'phone starting below 6' => ['phone', '5876543210'],
            'phone too short' => ['phone', '98765432'],
            'phone with letters' => ['phone', '98765abcde'],
            'pin too short' => ['postal_code', '5600'],
            'pin with letters' => ['postal_code', '56002A'],
        ];
    }

    /**
     * @dataProvider rejectedValues
     */
    public function test_the_server_rejects_what_the_browser_also_blocks(string $field, string $value): void
    {
        $this->submitAddress($this->valid([$field => $value]))->assertSessionHasErrors($field);
    }

    /** @return array<string, array{string, string, string}> */
    public static function clientConstraints(): array
    {
        return [
            'name minlength' => ['name', 'minlength="2"', 'min:2'],
            'street minlength' => ['address_line1', 'minlength="3"', 'min:3'],
            'city minlength' => ['city', 'minlength="2"', 'min:2'],
            'phone pattern' => ['phone', 'pattern="[6-9][0-9]{9}"', 'regex'],
            'pin pattern' => ['postal_code', 'pattern="[1-9][0-9]{5}"', 'regex'],
        ];
    }

    /**
     * @dataProvider clientConstraints
     */
    public function test_both_address_forms_carry_the_matching_browser_rule(string $field, string $attribute): void
    {
        foreach (['create', 'edit'] as $form) {
            $markup = file_get_contents(resource_path("views/account/addresses/{$form}.blade.php"));

            $this->assertStringContainsString(
                $attribute,
                $markup,
                "The {$form} form must carry {$attribute} so {$field} fails in the browser, not only on the server."
            );
        }
    }

    public function test_the_numeric_fields_ask_for_a_numeric_keypad(): void
    {
        foreach (['create', 'edit'] as $form) {
            $markup = file_get_contents(resource_path("views/account/addresses/{$form}.blade.php"));
            $this->assertSame(
                2,
                substr_count($markup, 'inputmode="numeric"'),
                "Phone and PIN should both open a numeric keypad on {$form}."
            );
        }
    }
}
