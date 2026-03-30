<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $storeId = $request->session()->get('pos_store_id');
        $date = $request->input('date', today()->format('Y-m-d'));

        $logs = DB::table('pos_audit_log')
            ->where('store_id', $storeId)
            ->whereDate('created_at', $date)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'date' => $date,
            'logs' => $logs,
        ]);
    }

    public static function log(Request $request, string $action, string $entityType, ?int $entityId = null, array $data = []): void
    {
        DB::table('pos_audit_log')->insert([
            'store_id'      => $request->session()->get('pos_store_id'),
            'staff_id'      => $request->session()->get('pos_staff_id'),
            'action'        => $action,
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'terminal_id'   => $request->session()->get('pos_device_id'),
            'authorized_by' => $data['authorized_by'] ?? null,
            'description'   => $data['description'] ?? null,
            'old_values'    => isset($data['old']) ? json_encode($data['old']) : null,
            'new_values'    => isset($data['new']) ? json_encode($data['new']) : null,
            'ip_address'    => $request->ip(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
}
