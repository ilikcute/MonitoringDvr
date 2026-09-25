<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(protected AuditLogger $auditLogger)
    {
    }

    /**
     * Dapatkan daftar pengguna dengan filter peran dan departemen (Super Admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $perPage = (int) $request->query('per_page', 15);
        $search = $request->query('search');
        $role = $request->query('role');
        $departmentId = $request->query('department_id');

        $query = User::with('department')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($role && $role !== 'All', fn ($q) => $q->where('role', $role))
            ->when($departmentId && $departmentId !== 'All', fn ($q) => $q->where('department_id', $departmentId))
            ->orderBy('id', 'desc');

        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total_records' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

    /**
     * Master data departemen untuk pilihan dropdown formulir pengguna.
     */
    public function departments(Request $request): JsonResponse
    {
        $departments = Department::orderBy('code')->get();

        return response()->json([
            'success' => true,
            'data' => $departments,
        ]);
    }

    /**
     * Tambah Pengguna Baru (Super Admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['superadmin', 'technician', 'dept_operator', 'management'])],
            'department_id' => 'nullable|exists:departments,id',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
        ]);

        // Jika dept_operator, pastikan department_id terisi
        if ($validated['role'] === 'dept_operator' && empty($validated['department_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna dengan peran Operator Departemen wajib memilih departemen.',
            ], 422);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'department_id' => $validated['department_id'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $this->auditLogger->log(
            action: 'USER_CREATED',
            targetType: 'User',
            targetId: $user->id,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'department_id' => $user->department_id,
            ],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengguna baru berhasil ditambahkan.',
            'data' => $user->load('department'),
        ], 201);
    }

    /**
     * Detail Pengguna (Super Admin only).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeSuperAdmin($request);
        $user = User::with('department')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Perbarui Data Pengguna (Super Admin only).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeSuperAdmin($request);
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'role' => ['required', Rule::in(['superadmin', 'technician', 'dept_operator', 'management'])],
            'department_id' => 'nullable|exists:departments,id',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validated['role'] === 'dept_operator' && empty($validated['department_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna dengan peran Operator Departemen wajib memilih departemen.',
            ], 422);
        }

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'department_id' => $user->department_id,
            'is_active' => $user->is_active,
        ];

        $user->name = $validated['name'];
        $user->email = strtolower($validated['email']);
        $user->role = $validated['role'];
        $user->department_id = $validated['department_id'] ?? null;
        $user->phone = $validated['phone'] ?? null;
        if (isset($validated['is_active'])) {
            $user->is_active = (bool) $validated['is_active'];
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $this->auditLogger->log(
            action: 'USER_UPDATED',
            targetType: 'User',
            targetId: $user->id,
            oldValues: $oldValues,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'department_id' => $user->department_id,
                'is_active' => $user->is_active,
            ],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Data pengguna berhasil diperbarui.',
            'data' => $user->load('department'),
        ]);
    }

    /**
     * Hapus Pengguna (Super Admin only).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        // Jangan izinkan super admin menghapus akunnya sendiri
        if ($request->user()->id === (int) $id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri saat sedang login.',
            ], 422);
        }

        $user = User::findOrFail($id);
        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        $user->delete();

        $this->auditLogger->log(
            action: 'USER_DELETED',
            targetType: 'User',
            targetId: $id,
            oldValues: $oldValues,
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403, 'Aksi ini hanya diizinkan untuk peran Super Admin EDP.');
        }
    }
}
