<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NetworkDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NetworkDetector $networkDetector
    ) {
    }

    /**
     * Login pengguna dan terbitkan token Sanctum.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with('department')->where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            $this->auditLogger->log(
                action: 'AUTH_LOGIN_FAILED',
                oldValues: ['email_attempt' => $validated['email']],
                request: $request
            );

            throw ValidationException::withMessages([
                'email' => ['Kredensial email atau password yang dimasukkan salah.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Silakan hubungi Super Admin EDP.',
            ], 403);
        }

        // Catat keberhasilan login
        $this->auditLogger->log(
            action: 'AUTH_LOGIN_SUCCESS',
            user: $user,
            request: $request
        );

        // Buat Sanctum token
        $token = $user->createToken('cdams_auth_token')->plainTextToken;
        $networkType = $request->attributes->get('network_type') ?? $this->networkDetector->detect($request);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'phone' => $user->phone,
                    'department' => $user->department ? [
                        'id' => $user->department->id,
                        'code' => $user->department->code,
                        'name' => $user->department->name,
                    ] : null,
                ],
                'token' => $token,
                'network_type' => $networkType,
            ],
        ]);
    }

    /**
     * Dapatkan profil user yang sedang login beserta status mode jaringan.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('department');
        $networkType = $request->attributes->get('network_type') ?? $this->networkDetector->detect($request);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'phone' => $user->phone,
                    'department' => $user->department ? [
                        'id' => $user->department->id,
                        'code' => $user->department->code,
                        'name' => $user->department->name,
                    ] : null,
                ],
                'network_type' => $networkType,
            ],
        ]);
    }

    /**
     * Logout dan cabut token saat ini.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
