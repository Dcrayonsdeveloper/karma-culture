<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_dashboard_requires_authentication(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_login_page_loads(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    public function test_admin_can_login(): void
    {
        $response = $this->post('/admin/login', [
            'email' => $this->adminUser->email,
            'password' => 'password',
        ]);

        $this->assertTrue(Auth::guard('admin')->check());
    }

    public function test_admin_dashboard_loads_for_authenticated_admin(): void
    {
        Auth::guard('admin')->login($this->adminUser);

        $response = $this->actingAs($this->adminUser, 'admin')
            ->get('/admin');

        $response->assertStatus(200);
    }

    public function test_non_admin_user_cannot_access_dashboard(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer, 'admin')
            ->get('/admin');

        $response->assertStatus(403);
    }
}
