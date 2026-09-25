<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\DvrAccount;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DvrAccountController extends Controller
{
    public function __construct(protected AuditLogger $auditLogger)
    {
    }

    /**
     * Intip Password Akun DVR secara aman dengan audit trail (API.md 2.2 & BR-ACC-002).
     */
    public function revealPassword(Request $request, int $dvrId, int $accountId): JsonResponse
    {
        $user = $request->user();
        $account = DvrAccount::with(['dvr.store', 'department'])->where('dvr_id', $dvrId)->findOrFail($accountId);

        // Validasi Aturan BR-ACC-002 (Isolasi Visibilitas Departemen)
        if ($user->isDeptOperator()) {
            if ($user->department_id !== $account->department_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk melihat kredensial milik departemen lain (BR-ACC-002).',
                ], 403);
            }
        }

        // Dekripsi password
        $decrypted = $account->getDecryptedPassword();

        // Catat ke Audit Log sesuai spesifikasi
        $this->auditLogger->log(
            action: 'CREDENTIAL_REVEAL',
            targetType: 'DvrAccount',
            targetId: $account->id,
            departmentCode: $account->department->code,
            newValues: [
                'store_code' => $account->dvr->store->store_code,
                'dvr_index' => $account->dvr->dvr_index,
                'username' => $account->username,
                'department_code' => $account->department->code,
            ],
            request: $request
        );

        return response()->json([
            'success' => true,
            'data' => [
                'account_id' => $account->id,
                'username' => $account->username,
                'department_code' => $account->department->code,
                'decrypted_password' => $decrypted,
                'expires_in_seconds' => 15, // UI re-masks after 15 seconds
            ],
        ]);
    }

    /**
     * Update Akun Kredensial DVR (Username, Password, Profile).
     */
    public function update(Request $request, int $dvrId, int $accountId): JsonResponse
    {
        $user = $request->user();
        $account = DvrAccount::with(['dvr.store', 'department'])->where('dvr_id', $dvrId)->findOrFail($accountId);

        // Hanya Super Admin atau Operator divisinya yang boleh mengubah kredensial
        if (!$user->isSuperAdmin() && !$user->isTechnician()) {
            if ($user->isDeptOperator() && $user->department_id !== $account->department_id) {
                abort(403, 'Anda tidak berwenang mengedit kredensial departemen lain.');
            }
        }

        $validated = $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'nullable|string|min:4|max:100',
            'permission_profile' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:255',
        ]);

        $account->username = $validated['username'];
        if (isset($validated['permission_profile'])) {
            $account->permission_profile = $validated['permission_profile'];
        }
        if (isset($validated['is_active'])) {
            $account->is_active = (bool) $validated['is_active'];
        }
        if (isset($validated['notes'])) {
            $account->notes = $validated['notes'];
        }

        $passwordChanged = false;
        if (!empty($validated['password'])) {
            $account->setPlainPassword($validated['password']);
            $passwordChanged = true;
        }

        $account->save();

        if ($passwordChanged) {
            $this->auditLogger->log(
                action: 'ACCOUNT_PASSWORD_CHANGED',
                targetType: 'DvrAccount',
                targetId: $account->id,
                departmentCode: $account->department->code,
                newValues: [
                    'store_code' => $account->dvr->store->store_code,
                    'dvr_index' => $account->dvr->dvr_index,
                    'username' => $account->username,
                ],
                request: $request
            );
        } else {
            $this->auditLogger->log(
                action: 'ACCOUNT_UPDATED',
                targetType: 'DvrAccount',
                targetId: $account->id,
                departmentCode: $account->department->code,
                request: $request
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Kredensial akun berhasil diperbarui.',
            'data' => [
                'id' => $account->id,
                'username' => $account->username,
                'permission_profile' => $account->permission_profile,
                'is_active' => $account->is_active,
                'has_password' => true,
                'notes' => $account->notes,
            ],
        ]);
    }
}
