<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Dvr;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\PingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class DvrController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected PingService $pingService
    ) {
    }

    /**
     * Detail DVR beserta Kredensial sesuai RBAC (API.md 2.1).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $dvr = Dvr::with(['store', 'accounts.department', 'latestCheck'])->findOrFail($id);

        $accounts = $dvr->accounts->filter(function ($acc) use ($user) {
            if ($user->isSuperAdmin() || $user->isTechnician() || $user->isManagement()) {
                return true;
            }
            return $user->department_id && $acc->department_id === $user->department_id;
        })->map(function ($acc) {
            return [
                'id' => $acc->id,
                'account_slot' => $acc->account_slot,
                'department_code' => $acc->department->code ?? '-',
                'department_name' => $acc->department->name ?? '-',
                'username' => $acc->username,
                'has_password' => !empty($acc->encrypted_password),
                'permission_profile' => $acc->permission_profile,
                'is_active' => $acc->is_active,
                'notes' => $acc->notes,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $dvr->id,
                'store_id' => $dvr->store_id,
                'store_code' => $dvr->store->store_code,
                'store_name' => $dvr->store->store_name,
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
                'accounts' => $accounts,
            ],
        ]);
    }

    /**
     * Tambah DVR pada Toko dengan validasi kapasitas (BR-STR-002).
     */
    public function store(Request $request, int $storeId): JsonResponse
    {
        $this->authorizeEdp($request);
        $store = Store::withCount('dvrs')->findOrFail($storeId);

        // Aturan BR-STR-002: Max 2 DVR kecuali allow_extra_dvr = true
        $maxAllowed = $store->allow_extra_dvr ? 10 : config('cdams.max_dvrs_per_store', 2);
        if ($store->dvrs_count >= $maxAllowed) {
            return response()->json([
                'success' => false,
                'message' => "Toko ini sudah mencapai batas maksimal {$maxAllowed} unit DVR (BR-STR-002). Pengaktifan unit tambahan memerlukan persetujuan Manager EDP.",
            ], 422);
        }

        $nextIndex = $store->dvrs()->max('dvr_index') + 1;

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'brand' => 'nullable|string|max:60',
            'model_series' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'ip_address' => 'required|string|max:45',
            'http_port' => 'nullable|integer|min:1|max:65535',
            'rtsp_port' => 'nullable|integer|min:1|max:65535',
            'server_port' => 'nullable|integer|min:1|max:65535',
            'total_channels' => 'nullable|integer|min:1|max:128',
            'storage_capacity_tb' => 'nullable|numeric|min:0.5|max:100',
            'retention_days' => 'nullable|integer|min:1|max:365',
            'firmware_version' => 'nullable|string|max:50',
            'status' => ['nullable', Rule::in(['Online', 'Offline', 'Degraded', 'Maintenance', 'Decommissioned'])],
        ]);

        $dvr = Dvr::create([
            'store_id' => $store->id,
            'dvr_index' => $nextIndex,
            'label' => $validated['label'],
            'brand' => $validated['brand'] ?? 'Hikvision',
            'model_series' => $validated['model_series'] ?? null,
            'serial_number' => $validated['serial_number'] ?? null,
            'ip_address' => $validated['ip_address'],
            'http_port' => $validated['http_port'] ?? 80,
            'rtsp_port' => $validated['rtsp_port'] ?? 554,
            'server_port' => $validated['server_port'] ?? 8000,
            'total_channels' => $validated['total_channels'] ?? 8,
            'storage_capacity_tb' => $validated['storage_capacity_tb'] ?? null,
            'retention_days' => $validated['retention_days'] ?? null,
            'firmware_version' => $validated['firmware_version'] ?? null,
            'status' => $validated['status'] ?? 'Offline',
        ]);

        $this->auditLogger->log(
            action: 'DVR_CREATED',
            targetType: 'Dvr',
            targetId: $dvr->id,
            newValues: $dvr->toArray(),
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Perangkat DVR berhasil didaftarkan dan 5 slot akun departemen otomatis dibuat.',
            'data' => $dvr->load('accounts.department'),
        ], 201);
    }

    /**
     * Perbarui konfigurasi DVR (IP, Port, Nama, dsb).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeEdp($request);
        $dvr = Dvr::findOrFail($id);

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'brand' => 'nullable|string|max:60',
            'model_series' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'ip_address' => 'required|string|max:45',
            'http_port' => 'nullable|integer|min:1|max:65535',
            'rtsp_port' => 'nullable|integer|min:1|max:65535',
            'server_port' => 'nullable|integer|min:1|max:65535',
            'total_channels' => 'nullable|integer|min:1|max:128',
            'storage_capacity_tb' => 'nullable|numeric|min:0.5|max:100',
            'retention_days' => 'nullable|integer|min:1|max:365',
            'firmware_version' => 'nullable|string|max:50',
            'status' => ['nullable', Rule::in(['Online', 'Offline', 'Degraded', 'Maintenance', 'Decommissioned'])],
        ]);

        $oldValues = $dvr->toArray();
        $ipOrPortChanged = ($dvr->ip_address !== $validated['ip_address']) ||
                           ($dvr->http_port !== ($validated['http_port'] ?? $dvr->http_port)) ||
                           ($dvr->rtsp_port !== ($validated['rtsp_port'] ?? $dvr->rtsp_port));

        $dvr->update($validated);

        if ($ipOrPortChanged) {
            $this->auditLogger->log(
                action: 'DVR_IP_CHANGED',
                targetType: 'Dvr',
                targetId: $dvr->id,
                oldValues: ['ip' => $oldValues['ip_address'], 'http_port' => $oldValues['http_port'], 'rtsp_port' => $oldValues['rtsp_port']],
                newValues: ['ip' => $dvr->ip_address, 'http_port' => $dvr->http_port, 'rtsp_port' => $dvr->rtsp_port],
                request: $request
            );
        } else {
            $this->auditLogger->log(
                action: 'DVR_UPDATED',
                targetType: 'Dvr',
                targetId: $dvr->id,
                oldValues: $oldValues,
                newValues: $dvr->toArray(),
                request: $request
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi DVR berhasil diperbarui.',
            'data' => $dvr,
        ]);
    }

    /**
     * Hapus DVR (Super Admin only).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403, 'Aksi ini hanya diizinkan untuk Super Admin.');
        }

        $dvr = Dvr::findOrFail($id);
        $oldValues = $dvr->toArray();
        $dvr->delete();

        $this->auditLogger->log(
            action: 'DVR_DELETED',
            targetType: 'Dvr',
            targetId: $id,
            oldValues: $oldValues,
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Perangkat DVR berhasil dihapus.',
        ]);
    }

    /**
     * Uji Konektivitas Ping ke IP Default 192.168.25.200 atau IP DVR tertentu.
     * Reply -> Online, RTO -> Offline.
     */
    public function pingTest(Request $request, ?int $id = null): JsonResponse
    {
        $dvr = $id ? Dvr::find($id) : null;
        $ipToPing = $request->input('ip_address') ?? ($dvr ? $dvr->ip_address : config('cdams.default_ping_ip', '192.168.25.200'));
        $portToVerify = $request->input('port') ?? ($dvr ? $dvr->http_port : 80);

        $result = $this->pingService->ping($ipToPing, (int) $portToVerify);

        // Jika terasosiasi dengan record DVR, perbarui statusnya
        if ($dvr) {
            if ($result['is_online']) {
                $dvr->last_seen_at = Carbon::now();
                $latestCheck = $dvr->latestCheck;
                $hasIssues = $latestCheck && ($latestCheck->camera_broken_count > 0 || $latestCheck->hdd_status !== 'Normal');
                $dvr->status = $hasIssues ? 'Degraded' : 'Online';
            } else {
                $dvr->status = 'Offline';
            }
            $dvr->saveQuietly();
            $result['status'] = $dvr->status;
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    private function authorizeEdp(Request $request): void
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !$user->isTechnician()) {
            abort(403, 'Aksi ini hanya diizinkan untuk Tim EDP / Teknisi.');
        }
    }
}
