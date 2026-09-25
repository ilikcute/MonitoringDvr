<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Dvr;
use App\Models\DvrCheck;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dapatkan ringkasan statistik CDAMS untuk kartu metrik dan grafik.
     */
    public function index(Request $request): JsonResponse
    {
        $overdueThreshold = Carbon::now()->subDays(config('cdams.check_overdue_days', 45));

        // Statistik Toko
        $totalStores = Store::count();
        $activeStores = Store::where('status', 'Active')->count();
        $renovationStores = Store::where('status', 'Renovation')->count();
        $closedStores = Store::where('status', 'Closed')->count();

        // Statistik DVR
        $totalDvrs = Dvr::count();
        $onlineDvrs = Dvr::where('status', 'Online')->count();
        $offlineDvrs = Dvr::where('status', 'Offline')->count();
        $degradedDvrs = Dvr::where('status', 'Degraded')->count();
        $maintenanceDvrs = Dvr::where('status', 'Maintenance')->count();

        // Checklist Metrics
        $checksThisMonth = DvrCheck::where('check_timestamp', '>=', Carbon::now()->startOfMonth())->count();

        // DVR Overdue: Belum pernah dicek atau terakhir dicek > 45 hari lalu
        $overdueDvrsCount = Dvr::whereHas('store', function ($q) {
            $q->where('status', 'Active');
        })->where(function ($q) use ($overdueThreshold) {
            $q->whereNull('last_check_at')
              ->orWhere('last_check_at', '<', $overdueThreshold);
        })->count();

        // Distribusi Region
        $regions = Store::select('region', DB::raw('count(*) as count'))
            ->groupBy('region')
            ->orderByDesc('count')
            ->limit(8)
            ->get();

        // Recent Audit Logs
        $recentLogs = AuditLog::with('user:id,name,role')
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user_name' => $log->user?->name ?? 'System',
                    'target' => $log->target_type ? "{$log->target_type} #{$log->target_id}" : '-',
                    'network_type' => $log->network_type,
                    'created_at' => $log->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'stores' => [
                    'total' => $totalStores,
                    'active' => $activeStores,
                    'renovation' => $renovationStores,
                    'closed' => $closedStores,
                ],
                'dvrs' => [
                    'total' => $totalDvrs,
                    'online' => $onlineDvrs,
                    'offline' => $offlineDvrs,
                    'degraded' => $degradedDvrs,
                    'maintenance' => $maintenanceDvrs,
                ],
                'checklists' => [
                    'this_month' => $checksThisMonth,
                    'overdue_count' => $overdueDvrsCount,
                ],
                'regional_distribution' => $regions,
                'recent_logs' => $recentLogs,
            ],
        ]);
    }
}
