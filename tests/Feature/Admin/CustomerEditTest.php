<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * The admin customer form has to accept the accounts sign-up actually creates.
 *
 * Registration takes one "full name" field and splits it on the first space, so
 * a customer who typed "dev" is stored with last_name = ''. The edit form used
 * to mark Last Name required, which meant staff could not save that account at
 * all - not even to correct its phone number.
 */
class CustomerEditTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

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

        // Exactly what registering as "dev" leaves behind.
        $this->customer = User::factory()->create([
            'first_name' => 'dev',
            'last_name' => '',
            'email' => 'dev01@example.com',
            'phone' => '7865786785',
            'role' => 'customer',
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'dev',
            'last_name' => '',
            'email' => 'dev01@example.com',
            'phone' => '7865786785',
            'is_active' => 1,
        ], $overrides);
    }

    public function test_a_customer_with_no_last_name_can_be_saved(): void
    {
        $response = $this->put(
            route('admin.customers.update', $this->customer),
            $this->payload(['phone' => '9876543210'])
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.customers.show', $this->customer));

        $this->customer->refresh();
        $this->assertSame('', $this->customer->last_name);
        $this->assertSame('9876543210', $this->customer->phone);
    }

    public function test_a_supplied_last_name_is_saved(): void
    {
        $this->put(
            route('admin.customers.update', $this->customer),
            $this->payload(['last_name' => 'Sharma'])
        )->assertSessionHasNoErrors();

        $this->assertSame('Sharma', $this->customer->refresh()->last_name);
    }

    public function test_a_name_wider_than_the_column_is_rejected(): void
    {
        // users.first_name and users.last_name are varchar(50); the old
        // max:255 handed a 51-character name straight to the database.
        $this->put(
            route('admin.customers.update', $this->customer),
            $this->payload(['last_name' => str_repeat('a', 51)])
        )->assertSessionHasErrors('last_name');

        $this->assertSame('', $this->customer->refresh()->last_name);
    }

    public function test_a_name_with_digits_is_rejected(): void
    {
        $this->put(
            route('admin.customers.update', $this->customer),
            $this->payload(['first_name' => 'dev123'])
        )->assertSessionHasErrors('first_name');
    }

    public function test_phone_is_stored_as_bare_digits(): void
    {
        $this->put(
            route('admin.customers.update', $this->customer),
            $this->payload(['phone' => '+91 78657 86785'])
        )->assertSessionHasNoErrors();

        $this->assertSame('7865786785', $this->customer->refresh()->phone);
    }

    public function test_a_phone_already_on_another_account_is_rejected(): void
    {
        User::factory()->create(['phone' => '9876543210', 'role' => 'customer']);

        $this->put(
            route('admin.customers.update', $this->customer),
            $this->payload(['phone' => '98765 43210'])
        )->assertSessionHasErrors('phone');

        $this->assertSame('7865786785', $this->customer->refresh()->phone);
    }

    public function test_a_junk_phone_is_rejected(): void
    {
        $this->put(
            route('admin.customers.update', $this->customer),
            $this->payload(['phone' => '12345'])
        )->assertSessionHasErrors('phone');
    }

    public function test_the_phone_can_be_cleared(): void
    {
        $this->put(
            route('admin.customers.update', $this->customer),
            $this->payload(['phone' => ''])
        )->assertSessionHasNoErrors();

        $this->assertNull($this->customer->refresh()->phone);
    }

    public function test_an_email_without_a_tld_is_rejected(): void
    {
        $this->put(
            route('admin.customers.update', $this->customer),
            $this->payload(['email' => 'dev@gmail'])
        )->assertSessionHasErrors('email');

        $this->assertSame('dev01@example.com', $this->customer->refresh()->email);
    }

    public function test_the_edit_form_does_not_demand_a_last_name(): void
    {
        $response = $this->get(route('admin.customers.edit', $this->customer));

        $response->assertOk();
        $response->assertSee('Last Name <span style="color: #616161; font-weight: 400;">(optional)</span>', false);
    }
}
