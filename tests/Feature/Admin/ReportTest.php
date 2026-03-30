<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Report',
            'last_name' => 'Admin',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_sales_report_loads(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/reports/sales');

        $response->assertStatus(200);
    }

    public function test_analytics_report_loads(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/reports/analytics');

        $response->assertStatus(200);
    }

    public function test_products_report_loads(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/reports/products');

        $response->assertStatus(200);
    }

    public function test_customers_report_loads(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/reports/customers');

        $response->assertStatus(200);
    }

    public function test_reports_require_admin_authentication(): void
    {
        $response = $this->get('/admin/reports/sales');

        $response->assertRedirect('/admin/login');
    }
}
