<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Dvr;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\NetworkDetector;
use App\Services\StoreSpreadsheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoreController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NetworkDetector $networkDetector,
        protected StoreSpreadsheetService $spreadsheetService
    ) {
    }

    /**
     * List Toko dengan filter cepat, pencarian < 1 detik, dan paginasi (API.md 1.1).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $search = $request->query('search');
        $region = $request->query('region');
        $status = $request->query('status');

        $query = Store::query()
            ->search($search)
            ->when($region && $region !== 'All', fn ($q) => $q->where('region', $region))
            ->when($status && $status !== 'All', fn ($q) => $q->where('status', $status))
            ->with(['dvrs' => function ($q) {
                $q->orderBy('dvr_index')->with([
                    'accounts:id,dvr_id,account_slot,is_active',
                    'latestCheck.checker:id,name',
                ]);
            }])
            ->orderBy('store_code');

        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(function (Store $store) {
            $totalAccounts = 0;
            $readyAccounts = 0;

            $dvrsData = $store->dvrs->map(function (Dvr $dvr) use (&$totalAccounts, &$readyAccounts) {
                $totalAccounts += 5;
                $readyAccounts += $dvr->accounts->where('is_active', true)->count();

                $latestCheck = $dvr->latestCheck;
                $hasCheckIssues = false;
                $checkIssues = [];

                if ($latestCheck) {
                    if (!$latestCheck->is_ping_online) {
                        $hasCheckIssues = true;
                        $checkIssues[] = 'Ping RTO';
                    }
                    if ($latestCheck->camera_broken_count > 0) {
                        $hasCheckIssues = true;
                        $checkIssues[] = "{$latestCheck->camera_broken_count} Cam Rusak";
                    }
                    if ($latestCheck->hdd_status !== 'Normal') {
                        $hasCheckIssues = true;
                        $checkIssues[] = "HDD {$latestCheck->hdd_status}";
                    }
                    if (!$latestCheck->is_time_synced) {
                        $hasCheckIssues = true;
                        $checkIssues[] = 'NTP Desync';
                    }
                }

                return [
                    'id' => $dvr->id,
                    'dvr_index' => $dvr->dvr_index,
                    'label' => $dvr->label,
                    'brand' => $dvr->brand,
                    'ip_address' => $dvr->ip_address,
                    'http_port' => $dvr->http_port,
                    'rtsp_port' => $dvr->rtsp_port,
                    'total_channels' => $dvr->total_channels,
                    'status' => $dvr->status,
                    'last_seen_at' => $dvr->last_seen_at?->toIso8601String(),
                    'last_check_at' => $dvr->last_check_at?->toIso8601String(),
                    'accounts_ready_count' => $dvr->accounts->where('is_active', true)->count(),
                    'latest_check' => $latestCheck ? [
                        'id' => $latestCheck->id,
                        'check_timestamp' => $latestCheck->check_timestamp?->toIso8601String(),
                        'formatted_date_time' => $latestCheck->check_timestamp?->format('d M Y, H:i') . ' WIB',
                        'is_ping_online' => (bool) $latestCheck->is_ping_online,
                        'is_time_synced' => (bool) $latestCheck->is_time_synced,
                        'time_difference_seconds' => $latestCheck->time_difference_seconds,
                        'hdd_status' => $latestCheck->hdd_status,
                        'record_retention_days' => $latestCheck->record_retention_days ?? $dvr->retention_days,
                        'camera_working_count' => $latestCheck->camera_working_count,
                        'camera_broken_count' => $latestCheck->camera_broken_count,
                        'checker_name' => $latestCheck->checker?->name ?? 'Teknisi Lapangan',
                        'has_issues' => $hasCheckIssues,
                        'issues' => $checkIssues,
                        'notes' => $latestCheck->notes,
                    ] : null,
                ];
            });

            return [
                'id' => $store->id,
                'store_code' => $store->store_code,
                'store_name' => $store->store_name,
                'region' => $store->region,
                'address' => $store->address,
                'ip_subnet' => $store->ip_subnet,
                'contact_person' => $store->contact_person,
                'phone' => $store->phone,
                'status' => $store->status,
                'dvrs_count' => $store->dvrs->count(),
                'accounts_ratio' => "{$readyAccounts}/" . max(5, $totalAccounts),
                'dvrs' => $dvrsData,
            ];
        });

        // Daftar region unik untuk filter dropdown
        $regions = Cache::remember('store_regions_list', 300, function () {
            return Store::distinct()->pluck('region')->filter()->values();
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total_records' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'filters' => [
                'regions' => $regions,
            ],
        ]);
    }

    /**
     * Detail Toko beserta DVR dan Kredensial sesuai RBAC (BR-ACC-002).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $store = Store::with(['dvrs.accounts.department', 'dvrs.latestCheck.checker'])->findOrFail($id);

        $dvrsData = $store->dvrs->map(function (Dvr $dvr) use ($user) {
            // Isolasi visibilitas departemen
            $accounts = $dvr->accounts->filter(function ($acc) use ($user) {
                if ($user->isSuperAdmin() || $user->isTechnician() || $user->isManagement()) {
                    return true;
                }
                // Jika operator departemen, hanya tampilkan akun sesuai departemennya
                return $user->department_id && $acc->department_id === $user->department_id;
            })->map(function ($acc) {
                return [
                    'id' => $acc->id,
                    'account_slot' => $acc->account_slot,
                    'department_id' => $acc->department_id,
                    'department_code' => $acc->department->code ?? '-',
                    'department_name' => $acc->department->name ?? '-',
                    'username' => $acc->username,
                    'permission_profile' => $acc->permission_profile,
                    'is_active' => $acc->is_active,
                    'has_password' => !empty($acc->encrypted_password),
                    'notes' => $acc->notes,
                ];
            })->values();

            return [
                'id' => $dvr->id,
                'dvr_index' => $dvr->dvr_index,
                'label' => $dvr->label,
                'brand' => $dvr->brand,
                'model_series' => $dvr->model_series,
                'serial_number' => $dvr->serial_number,
                'ip_address' => $dvr->ip_address,
                'http_port' => $dvr->http_port,
                'rtsp_port' => $dvr->rtsp_port,
                'server_port' => $dvr->server_port,
                'total_channels' => $dvr->total_channels,
                'storage_capacity_tb' => $dvr->storage_capacity_tb,
                'retention_days' => $dvr->retention_days,
                'firmware_version' => $dvr->firmware_version,
                'status' => $dvr->status,
                'last_seen_at' => $dvr->last_seen_at?->toIso8601String(),
                'last_check_at' => $dvr->last_check_at?->toIso8601String(),
                'latest_check' => $dvr->latestCheck ? [
                    'id' => $dvr->latestCheck->id,
                    'check_timestamp' => $dvr->latestCheck->check_timestamp?->toIso8601String(),
                    'formatted_date_time' => $dvr->latestCheck->check_timestamp?->format('d M Y, H:i') . ' WIB',
                    'is_ping_online' => $dvr->latestCheck->is_ping_online,
                    'is_time_synced' => $dvr->latestCheck->is_time_synced,
                    'time_difference_seconds' => $dvr->latestCheck->time_difference_seconds,
                    'hdd_status' => $dvr->latestCheck->hdd_status,
                    'record_retention_days' => $dvr->latestCheck->record_retention_days ?? $dvr->retention_days,
                    'camera_working_count' => $dvr->latestCheck->camera_working_count,
                    'camera_broken_count' => $dvr->latestCheck->camera_broken_count,
                    'network_type' => $dvr->latestCheck->network_type,
                    'checker_name' => $dvr->latestCheck->checker?->name ?? 'Teknisi Lapangan',
                    'notes' => $dvr->latestCheck->notes,
                ] : null,
                'accounts' => $accounts,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $store->id,
                'store_code' => $store->store_code,
                'store_name' => $store->store_name,
                'region' => $store->region,
                'address' => $store->address,
                'ip_subnet' => $store->ip_subnet,
                'contact_person' => $store->contact_person,
                'phone' => $store->phone,
                'status' => $store->status,
                'allow_extra_dvr' => $store->allow_extra_dvr,
                'dvrs' => $dvrsData,
            ],
        ]);
    }

    /**
     * Tambah Toko Baru (Super Admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'store_code' => 'required|string|max:20|unique:stores,store_code',
            'store_name' => 'required|string|max:150',
            'region' => 'required|string|max:50',
            'address' => 'nullable|string',
            'ip_subnet' => 'nullable|string|max:45',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'status' => ['required', Rule::in(['Active', 'Renovation', 'Closed'])],
            'allow_extra_dvr' => 'nullable|boolean',
            // Opsi inisialisasi DVR 1 langsung
            'create_dvr1' => 'nullable|boolean',
            'dvr1_ip' => 'nullable|string|max:45',
            'dvr1_label' => 'nullable|string|max:100',
            'dvr1_brand' => 'nullable|string|max:60',
        ]);

        $store = Store::create([
            'store_code' => strtoupper($validated['store_code']),
            'store_name' => $validated['store_name'],
            'region' => $validated['region'],
            'address' => $validated['address'] ?? null,
            'ip_subnet' => $validated['ip_subnet'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
            'allow_extra_dvr' => $validated['allow_extra_dvr'] ?? false,
        ]);

        // Jika diminta buat DVR 1 atau status toko Active (BR-STR-002 wajib min 1 DVR)
        if (!empty($validated['create_dvr1']) || $store->status === 'Active') {
            Dvr::create([
                'store_id' => $store->id,
                'dvr_index' => 1,
                'label' => $validated['dvr1_label'] ?? 'DVR 1 - Area Toko & Kasir',
                'brand' => $validated['dvr1_brand'] ?? 'Hikvision',
                'ip_address' => $validated['dvr1_ip'] ?? config('cdams.default_ping_ip', '192.168.25.200'),
                'status' => 'Offline',
            ]);
        }

        $this->auditLogger->log(
            action: 'STORE_CREATED',
            targetType: 'Store',
            targetId: $store->id,
            newValues: $store->toArray(),
            request: $request
        );

        Cache::forget('store_regions_list');

        return response()->json([
            'success' => true,
            'message' => 'Toko berhasil ditambahkan.',
            'data' => $store->load('dvrs'),
        ], 201);
    }

    /**
     * Perbarui Data Toko (Super Admin only).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeSuperAdmin($request);
        $store = Store::findOrFail($id);

        $validated = $request->validate([
            'store_name' => 'required|string|max:150',
            'region' => 'required|string|max:50',
            'address' => 'nullable|string',
            'ip_subnet' => 'nullable|string|max:45',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'status' => ['required', Rule::in(['Active', 'Renovation', 'Closed'])],
            'allow_extra_dvr' => 'nullable|boolean',
        ]);

        $oldValues = $store->toArray();
        $store->update($validated);

        $this->auditLogger->log(
            action: 'STORE_UPDATED',
            targetType: 'Store',
            targetId: $store->id,
            oldValues: $oldValues,
            newValues: $store->toArray(),
            request: $request
        );

        Cache::forget('store_regions_list');

        return response()->json([
            'success' => true,
            'message' => 'Data toko berhasil diperbarui.',
            'data' => $store,
        ]);
    }

    /**
     * Hapus Toko (Super Admin only).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeSuperAdmin($request);
        $store = Store::findOrFail($id);
        $oldValues = $store->toArray();

        $store->delete();

        $this->auditLogger->log(
            action: 'STORE_DELETED',
            targetType: 'Store',
            targetId: $id,
            oldValues: $oldValues,
            request: $request
        );

        Cache::forget('store_regions_list');

        return response()->json([
            'success' => true,
            'message' => 'Toko berhasil dihapus.',
        ]);
    }

    /**
     * Minta OTP 6-Digit untuk Ekspor WAN (BR-NET-002).
     */
    public function requestExportOtp(Request $request): JsonResponse
    {
        $user = $request->user();
        $otp = (string) rand(100000, 999999);

        // Simpan di cache selama 3 menit
        Cache::put("export_otp_user_{$user->id}", $otp, now()->addMinutes(3));

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP ekspor berhasil dibuat.',
            'data' => [
                'otp_code' => $otp, // Disimulasikan untuk kemudahan pengujian
                'expires_in_seconds' => 180,
            ],
        ]);
    }

    /**
     * Ekspor Data Toko & DVR ke format XLSX / CSV (BR-NET-002 & AUDITLOG).
     */
    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $networkType = $request->attributes->get('network_type') ?? $this->networkDetector->detect($request);
        $user = $request->user();
        $format = strtolower($request->query('format', 'xlsx')) === 'csv' ? 'csv' : 'xlsx';

        // Validasi aturan BR-NET-002: WAN ekspor memerlukan OTP
        if ($networkType === 'WAN') {
            $inputOtp = $request->query('otp');
            $cachedOtp = Cache::get("export_otp_user_{$user->id}");

            if (empty($inputOtp) || $inputOtp !== $cachedOtp) {
                return response()->json([
                    'success' => false,
                    'requires_otp' => true,
                    'message' => 'Akses dari jaringan WAN memerlukan One-Time Password (OTP) verifikasi sebelum mengunduh data.',
                ], 403);
            }

            // Hapus OTP setelah digunakan
            Cache::forget("export_otp_user_{$user->id}");
        }

        $stores = Store::with(['dvrs.accounts'])->orderBy('store_code')->get();

        // Catat audit log ekspor massal
        $this->auditLogger->log(
            action: 'MASS_DATA_EXPORT',
            targetType: 'Store',
            newValues: [
                'format' => $format,
                'total_records' => $stores->count(),
                'network_type' => $networkType,
            ],
            request: $request
        );

        return $this->spreadsheetService->export($stores, $format);
    }

    /**
     * Download format template spreadsheet untuk impor data.
     */
    public function downloadTemplate(Request $request): StreamedResponse
    {
        $format = strtolower($request->query('format', 'xlsx')) === 'csv' ? 'csv' : 'xlsx';
        return $this->spreadsheetService->downloadTemplate($format);
    }

    /**
     * Unggah dan impor spreadsheet data toko (Super Admin only).
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt,xls|max:10240',
        ]);

        $file = $request->file('file');
        $result = $this->spreadsheetService->import($file->getRealPath());

        $this->auditLogger->log(
            action: 'MASS_DATA_IMPORT',
            targetType: 'Store',
            newValues: $result,
            request: $request
        );

        Cache::forget('store_regions_list');

        return response()->json([
            'success' => true,
            'message' => "Proses impor selesai. {$result['imported']} data toko baru ditambahkan, {$result['updated']} diperbarui.",
            'data' => $result,
        ]);
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403, 'Aksi ini hanya diizinkan untuk peran Super Admin EDP.');
        }
    }
}
