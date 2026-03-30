<?php

namespace Tests\Feature\Pos;

use App\Models\Category;
use App\Models\CreditNote;
use App\Models\PosRegister;
use App\Models\Product;
use App\Models\Staff;
use App\Models\StaffShift;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCreditNoteTest extends TestCase
{
    use RefreshDatabase;

    private array $posSession;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $store = Store::create(['name' => 'Test Store', 'code' => 'TS-05', 'is_active' => true]);
        $register = PosRegister::create(['store_id' => $store->id, 'name' => 'C5', 'device_id' => 'POS-T5', 'status' => 'active']);
        $user = User::factory()->create(['first_name' => 'Cashier5', 'role' => 'admin']);
        $staff = Staff::create(['user_id' => $user->id, 'employee_id' => 'E-05', 'role' => 'cashier', 'store_id' => $store->id, 'pin' => bcrypt('1234'), 'is_active' => true]);
        $shift = StaffShift::create(['staff_id' => $staff->id, 'store_id' => $store->id, 'register_id' => $register->id, 'shift_start' => now(), 'opening_cash' => 1000, 'status' => 'open']);

        $this->customer = User::factory()->create(['role' => 'customer']);

        $this->posSession = [
            'pos_staff_id' => $staff->id,
            'pos_store_id' => $store->id,
            'pos_device_id' => 'POS-T5',
            'pos_register_id' => $register->id,
            'pos_shift_id' => $shift->id,
        ];
    }

    public function test_validate_active_credit_note(): void
    {
        $creditNote = CreditNote::create([
            'user_id' => $this->customer->id,
            'amount' => 500,
            'remaining_amount' => 500,
            'used_amount' => 0,
            'status' => 'active',
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->withSession($this->posSession)
            ->get('/pos/credit-note/' . $creditNote->credit_note_number . '/validate');

        $response->assertStatus(200);
        $response->assertJsonFragment(['valid' => true]);
    }

    public function test_validate_expired_credit_note(): void
    {
        $creditNote = CreditNote::create([
            'user_id' => $this->customer->id,
            'amount' => 500,
            'remaining_amount' => 500,
            'used_amount' => 0,
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->withSession($this->posSession)
            ->get('/pos/credit-note/' . $creditNote->credit_note_number . '/validate');

        $response->assertStatus(422);
        $response->assertJsonFragment(['valid' => false]);
    }

    public function test_validate_fully_used_credit_note(): void
    {
        $creditNote = CreditNote::create([
            'user_id' => $this->customer->id,
            'amount' => 500,
            'remaining_amount' => 0,
            'used_amount' => 500,
            'status' => 'fully_used',
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->withSession($this->posSession)
            ->get('/pos/credit-note/' . $creditNote->credit_note_number . '/validate');

        $response->assertStatus(422);
        $response->assertJsonFragment(['valid' => false]);
    }

    public function test_validate_nonexistent_credit_note(): void
    {
        $response = $this->withSession($this->posSession)
            ->get('/pos/credit-note/INVALID-CODE/validate');

        $response->assertStatus(404);
    }
}
