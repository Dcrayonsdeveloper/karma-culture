<?php

namespace Tests\Feature\Pos;

use App\Models\PosRegister;
use App\Models\Product;
use App\Models\Staff;
use App\Models\StaffShift;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosReportTest extends TestCase
{
    use RefreshDatabase;

    private array $posSession;

    protected function setUp(): void
    {
        parent::setUp();

        $store = Store::create(['name' => 'Test Store', 'code' => 'TS-04', 'is_active' => true]);
        $register = PosRegister::create(['store_id' => $store->id, 'name' => 'C4', 'device_id' => 'POS-T4', 'status' => 'active']);
        $user = User::factory()->create(['first_name' => 'Manager', 'role' => 'admin']);
        $staff = Staff::create(['user_id' => $user->id, 'employee_id' => 'E-04', 'role' => 'manager', 'store_id' => $store->id, 'pin' => bcrypt('1234'), 'is_active' => true]);
        $shift = StaffShift::create(['staff_id' => $staff->id, 'store_id' => $store->id, 'register_id' => $register->id, 'shift_start' => now(), 'opening_cash' => 1000, 'status' => 'open']);

        $this->posSession = [
            'pos_staff_id' => $staff->id,
            'pos_store_id' => $store->id,
            'pos_device_id' => 'POS-T4',
            'pos_register_id' => $register->id,
            'pos_shift_id' => $shift->id,
            'pos_staff_role' => 'manager',
        ];
    }

    public function test_reports_page_loads(): void
    {
        $response = $this->withSession($this->posSession)->get('/pos/reports');
        $response->assertStatus(200);
    }

    public function test_reports_json_endpoint(): void
    {
        $response = $this->withSession($this->posSession)
            ->getJson('/pos/reports');

        $response->assertStatus(200);
        $response->assertJsonStructure(['today' => ['total_sales', 'total_bills']]);
    }

    public function test_daily_report(): void
    {
        $response = $this->withSession($this->posSession)
            ->getJson('/pos/reports/daily');

        $response->assertStatus(200);
        $response->assertJsonStructure(['date', 'sales', 'totals']);
    }

    public function test_staff_performance_report(): void
    {
        $response = $this->withSession($this->posSession)
            ->getJson('/pos/reports/staff');

        $response->assertStatus(200);
        $response->assertJsonStructure(['staff']);
    }

    public function test_top_products_report(): void
    {
        $response = $this->withSession($this->posSession)
            ->getJson('/pos/reports/top-products');

        $response->assertStatus(200);
        $response->assertJsonStructure(['products']);
    }

    public function test_inventory_alerts(): void
    {
        $response = $this->withSession($this->posSession)
            ->getJson('/pos/reports/inventory-alerts');

        $response->assertStatus(200);
        $response->assertJsonStructure(['alerts']);
    }

    public function test_gst_report(): void
    {
        $response = $this->withSession($this->posSession)
            ->getJson('/pos/reports/gst');

        $response->assertStatus(200);
        $response->assertJsonStructure(['from', 'to', 'items', 'total_tax']);
    }
}
