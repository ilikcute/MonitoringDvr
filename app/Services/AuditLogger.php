<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function __construct(protected NetworkDetector $networkDetector)
    {
    }

    /**
     * Catat aksi audit ke tabel audit_logs (append-only).
     */
    public function log(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?string $departmentCode = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null,
        ?Request $request = null
    ): AuditLog {
        $req = $request ?? request();
        $currentUser = $user ?? Auth::user();
        $networkType = $req->attributes->get('network_type') ?? $this->networkDetector->detect($req);

        return AuditLog::create([
            'user_id' => $currentUser?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'department_code' => $departmentCode,
            'network_type' => $networkType,
            'ip_address' => $req->ip() ?? '127.0.0.1',
            'user_agent' => substr((string) $req->userAgent(), 0, 250),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now(),
        ]);
    }
}
