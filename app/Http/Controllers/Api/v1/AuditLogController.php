<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Tampilkan riwayat audit log keamanan (Super Admin only).
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403, 'Akses Audit Log hanya diizinkan untuk Super Admin EDP.');
        }

        $perPage = (int) $request->query('per_page', 20);
        $action = $request->query('action');
        $networkType = $request->query('network_type');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = AuditLog::with('user:id,name,email,role')
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when($networkType, fn ($q) => $q->where('network_type', $networkType))
            ->when($startDate, fn ($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('created_at', '<=', $endDate))
            ->latest('created_at');

        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total_records' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }
}
