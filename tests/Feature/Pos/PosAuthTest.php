<?php

namespace Tests\Feature\Pos;

use App\Models\PosRegister;
use App\Models\Staff;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosAuthTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private PosRegister $register;
    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Test Store', 'code' => 'TEST-01',
            'is_active' => true,
        ]);

        $this->register = PosRegister::create([
            'store_id' => $this->store->id,
            'name' => 'Counter 1',
            'device_id' => 'POS-TEST',
            'status' => 'active',
        ]);

        $user = User::factory()->create(['first_name' => 'Test', 'role' => 'admin']);

        $this->staff = Staff::create([
            'user_id' => $user->id,
            'employee_id' => 'EMP-TEST',
            'role' => 'manager',
            'store_id' => $this->store->id,
            'pin' => bcrypt('1234'),
            'is_active' => true,
        ]);
    }

    public function test_pos_login_page_loads(): void
    {
        $response = $this->get('/pos/login');
        $response->assertStatus(200);
    }

    public function test_device_registration(): void
    {
        $response = $this->post('/pos/register-device', [
            'device_id' => 'POS-TEST',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'store', 'terminal']);
    }

    public function test_device_registration_fails_for_unknown_device(): void
    {
        $response = $this->post('/pos/register-device', [
            'device_id' => 'UNKNOWN',
        ]);

        $response->assertStatus(404);
    }

    public function test_staff_login_with_valid_pin(): void
    {
        $response = $this->withSession([
            'pos_device_id' => 'POS-TEST',
            'pos_store_id' => $this->store->id,
            'pos_register_id' => $this->register->id,
        ])->post('/pos/login', [
            'pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);
    }

    public function test_staff_login_with_wrong_pin(): void
    {
        $response = $this->withSession([
            'pos_device_id' => 'POS-TEST',
            'pos_store_id' => $this->store->id,
            'pos_register_id' => $this->register->id,
        ])->post('/pos/login', [
            'pin' => '9999',
        ]);

        $response->assertStatus(401);
    }

    public function test_protected_routes_redirect_without_auth(): void
    {
        $response = $this->get('/pos');
        $response->assertRedirect('/pos/login');
    }

    public function test_logout_clears_session(): void
    {
        $response = $this->withSession([
            'pos_staff_id' => $this->staff->id,
            'pos_store_id' => $this->store->id,
            'pos_device_id' => 'POS-TEST',
            'pos_register_id' => $this->register->id,
        ])->post('/pos/logout');

        $response->assertStatus(200);
    }
}
