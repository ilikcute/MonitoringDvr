<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Dvr;
use App\Models\DvrCheck;
use App\Services\AuditLogger;
use App\Services\NetworkDetector;
use App\Services\ChecklistSpreadsheetService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DvrCheckController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NetworkDetector $networkDetector,
        protected ChecklistSpreadsheetService $spreadsheetService
    ) {
    }

    /**
     * Ekspor Rekapitulasi Hasil Checklist Lapangan ke format XLSX atau CSV.
     */
    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $hasIssue = $request->query('has_issue');
        $format = strtolower($request->query('format', 'xlsx'));

        if (!in_array($format, ['xlsx', 'csv'])) {
            $format = 'xlsx';
        }

        $query = DvrCheck::with(['dvr.store', 'checker:id,name,role'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('dvr.store', function ($sq) use ($search) {
                        $sq->where('store_code', 'like', "%{$search}%")
                           ->orWhere('store_name', 'like', "%{$search}%")
                           ->orWhere('region', 'like', "%{$search}%");
                    })
                    ->orWhereHas('dvr', function ($dq) use ($search) {
                        $dq->where('label', 'like', "%{$search}%")
                           ->orWhere('ip_address', 'like', "%{$search}%");
                    })
                    ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when($hasIssue === 'yes', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('camera_broken_count', '>', 0)
                        ->orWhere('hdd_status', '!=', 'Normal')
                        ->orWhere('is_time_synced', false)
                        ->orWhere('is_ping_online', false);
                });
            })
            ->when($hasIssue === 'no', function ($q) {
                $q->where('camera_broken_count', 0)
                    ->where('hdd_status', 'Normal')
                    ->where('is_time_synced', true)
                    ->where('is_ping_online', true);
            })
            ->when($status === 'online', fn ($q) => $q->where('is_ping_online', true))
            ->when($status === 'offline', fn ($q) => $q->where('is_ping_online', false))
            ->latest('check_timestamp');

        $checks = $query->get();

        if ($checks->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data hasil checklist lapangan yang dapat diekspor.',
            ], 200);
        }

        $detectedNetwork = $request->attributes->get('network_type') ?? $this->networkDetector->detect($request);

        // Catat Audit Log Ekspor
        $this->auditLogger->log(
            action: 'MASS_DATA_EXPORT',
            targetType: 'DvrCheck',
            newValues: [
                'format' => $format,
                'total_records' => $checks->count(),
                'network_type' => $detectedNetwork,
            ],
            request: $request
        );

        return $this->spreadsheetService->export($checks, $format);
    }

    /**
     * Submit Hasil Checklist Lapangan Teknisi (API.md 3.1 & BR-CHK-001/002).
     */
    public function store(Request $request, int $dvrId): JsonResponse
    {
        $dvr = Dvr::with('store')->findOrFail($dvrId);
        $user = $request->user();

        $validated = $request->validate([
            'is_ping_online' => 'required|boolean',
            'is_time_synced' => 'nullable|boolean',
            'time_difference_seconds' => 'nullable|integer|min:0',
            'hdd_status' => ['required', Rule::in(['Normal', 'Error', 'Unformatted', 'Full'])],
            'record_retention_days' => 'nullable|integer|min:0|max:365',
            'camera_working_count' => 'required|integer|min:0|max:128',
            'camera_broken_count' => 'required|integer|min:0|max:128',
            'network_type' => ['nullable', Rule::in(['LAN', 'WAN'])],
            'notes' => 'nullable|string|max:1000',
        ]);

        $timeDiff = $validated['time_difference_seconds'] ?? 0;
        $syncThreshold = config('cdams.ntp_sync_threshold_seconds', 180);

        // Aturan BR-CHK-002: Selisih > 180 detik wajib ditandai Time Out of Sync
        $isTimeSynced = $validated['is_time_synced'] ?? true;
        if ($timeDiff > $syncThreshold) {
            $isTimeSynced = false;
        }

        $detectedNetwork = $request->attributes->get('network_type') ?? $this->networkDetector->detect($request);
        $networkType = $validated['network_type'] ?? $detectedNetwork;

        $check = DvrCheck::create([
            'dvr_id' => $dvr->id,
            'checked_by_user_id' => $user->id,
            'check_timestamp' => now(),
            'is_ping_online' => (bool) $validated['is_ping_online'],
            'is_time_synced' => $isTimeSynced,
            'time_difference_seconds' => $timeDiff,
            'hdd_status' => $validated['hdd_status'],
            'record_retention_days' => $validated['record_retention_days'] ?? null,
            'camera_working_count' => (int) $validated['camera_working_count'],
            'camera_broken_count' => (int) $validated['camera_broken_count'],
            'network_type' => $networkType,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Catat Audit Log
        $this->auditLogger->log(
            action: 'DVR_CHECK_SUBMITTED',
            targetType: 'DvrCheck',
            targetId: $check->id,
            newValues: [
                'store_code' => $dvr->store->store_code,
                'dvr_index' => $dvr->dvr_index,
                'is_ping_online' => $check->is_ping_online,
                'hdd_status' => $check->hdd_status,
                'time_difference_seconds' => $check->time_difference_seconds,
            ],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Hasil checklist DVR berhasil dicatat.',
            'check_id' => $check->id,
            'data' => [
                'id' => $check->id,
                'dvr_id' => $check->dvr_id,
                'is_ping_online' => $check->is_ping_online,
                'is_time_synced' => $check->is_time_synced,
                'dvr_status' => $dvr->fresh()->status,
                'check_timestamp' => $check->check_timestamp->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Riwayat Checklist DVR Tertentu.
     */
    public function index(Request $request, int $dvrId): JsonResponse
    {
        $dvr = Dvr::findOrFail($dvrId);
        $checks = DvrCheck::with('checker:id,name,role')
            ->where('dvr_id', $dvr->id)
            ->latest('check_timestamp')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $checks->items(),
            'meta' => [
                'current_page' => $checks->currentPage(),
                'total_records' => $checks->total(),
                'last_page' => $checks->lastPage(),
            ],
        ]);
    }

    /**
     * Daftar Toko / DVR dengan status Check Overdue (> 45 hari tanpa cek).
     */
    public function overdue(Request $request): JsonResponse
    {
        $overdueThreshold = Carbon::now()->subDays(config('cdams.check_overdue_days', 45));

        $dvrs = Dvr::with('store')
            ->whereHas('store', fn ($q) => $q->where('status', 'Active'))
            ->where(function ($q) use ($overdueThreshold) {
                $q->whereNull('last_check_at')
                  ->orWhere('last_check_at', '<', $overdueThreshold);
            })
            ->orderBy('last_check_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $dvrs->items(),
            'meta' => [
                'current_page' => $dvrs->currentPage(),
                'total_records' => $dvrs->total(),
                'last_page' => $dvrs->lastPage(),
            ],
        ]);
    }

    /**
     * Riwayat Seluruh Checklist Lapangan Toko & Temuan (untuk menu Checklist Lapangan).
     */
    public function listAll(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $search = $request->query('search');
        $status = $request->query('status');
        $hasIssue = $request->query('has_issue');

        $query = DvrCheck::with(['dvr.store', 'checker:id,name,role'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('dvr.store', function ($sq) use ($search) {
                        $sq->where('store_code', 'like', "%{$search}%")
                           ->orWhere('store_name', 'like', "%{$search}%")
                           ->orWhere('region', 'like', "%{$search}%");
                    })
                    ->orWhereHas('dvr', function ($dq) use ($search) {
                        $dq->where('label', 'like', "%{$search}%")
                           ->orWhere('ip_address', 'like', "%{$search}%");
                    })
                    ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when($hasIssue === 'yes', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('camera_broken_count', '>', 0)
                        ->orWhere('hdd_status', '!=', 'Normal')
                        ->orWhere('is_time_synced', false)
                        ->orWhere('is_ping_online', false);
                });
            })
            ->when($hasIssue === 'no', function ($q) {
                $q->where('camera_broken_count', 0)
                    ->where('hdd_status', 'Normal')
                    ->where('is_time_synced', true)
                    ->where('is_ping_online', true);
            })
            ->when($status === 'online', fn ($q) => $q->where('is_ping_online', true))
            ->when($status === 'offline', fn ($q) => $q->where('is_ping_online', false))
            ->latest('check_timestamp');

        $paginated = $query->paginate($perPage);

        $stats = [
            'total_checks' => DvrCheck::count(),
            'total_with_issues' => DvrCheck::where(function ($q) {
                $q->where('camera_broken_count', '>', 0)
                  ->orWhere('hdd_status', '!=', 'Normal')
                  ->orWhere('is_time_synced', false)
                  ->orWhere('is_ping_online', false);
            })->count(),
            'total_normal' => DvrCheck::where('camera_broken_count', 0)
                ->where('hdd_status', 'Normal')
                ->where('is_time_synced', true)
                ->where('is_ping_online', true)
                ->count(),
        ];

        $items = collect($paginated->items())->map(function (DvrCheck $check) {
            $issues = [];
            if (!$check->is_ping_online) {
                $issues[] = 'Ping RTO / Jaringan Offline';
            }
            if ($check->camera_broken_count > 0) {
                $issues[] = "{$check->camera_broken_count} Kamera Rusak/Mati";
            }
            if ($check->hdd_status !== 'Normal') {
                $issues[] = "HDD {$check->hdd_status}";
            }
            if (!$check->is_time_synced) {
                $issues[] = "Waktu Out of Sync (Selisih {$check->time_difference_seconds}s)";
            }

            return [
                'id' => $check->id,
                'dvr_id' => $check->dvr_id,
                'check_timestamp' => $check->check_timestamp?->toIso8601String(),
                'formatted_date_time' => $check->check_timestamp?->format('d M Y, H:i') . ' WIB',
                'is_ping_online' => $check->is_ping_online,
                'is_time_synced' => $check->is_time_synced,
                'time_difference_seconds' => $check->time_difference_seconds,
                'hdd_status' => $check->hdd_status,
                'record_retention_days' => $check->record_retention_days,
                'camera_working_count' => $check->camera_working_count,
                'camera_broken_count' => $check->camera_broken_count,
                'network_type' => $check->network_type,
                'notes' => $check->notes,
                'has_issues' => count($issues) > 0,
                'issues' => $issues,
                'checker' => $check->checker ? [
                    'id' => $check->checker->id,
                    'name' => $check->checker->name,
                    'role' => $check->checker->role,
                ] : null,
                'dvr' => $check->dvr ? [
                    'id' => $check->dvr->id,
                    'dvr_index' => $check->dvr->dvr_index,
                    'label' => $check->dvr->label,
                    'ip_address' => $check->dvr->ip_address,
                    'status' => $check->dvr->status,
                    'store' => $check->dvr->store ? [
                        'id' => $check->dvr->store->id,
                        'store_code' => $check->dvr->store->store_code,
                        'store_name' => $check->dvr->store->store_name,
                        'region' => $check->dvr->store->region,
                    ] : null,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total_records' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }
}
