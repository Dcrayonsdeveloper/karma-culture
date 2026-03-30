<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosRegister;
use App\Models\Staff;
use App\Models\StaffShift;
use App\Models\Store;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Main POS billing screen.
     */
    public function index(Request $request)
    {
        $staffId    = $request->session()->get('pos_staff_id');
        $storeId    = $request->session()->get('pos_store_id');
        $registerId = $request->session()->get('pos_register_id');
        $shiftId    = $request->session()->get('pos_shift_id');

        $staff    = Staff::with('user')->find($staffId);
        $store    = Store::find($storeId);
        $register = PosRegister::find($registerId);
        $shift    = StaffShift::find($shiftId);

        return view('pos.billing', compact('staff', 'store', 'register', 'shift'));
    }
}
