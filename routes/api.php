<?php

use App\Http\Controllers\Api\v1\AuditLogController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\DvrAccountController;
use App\Http\Controllers\Api\v1\DvrCheckController;
use App\Http\Controllers\Api\v1\DvrController;
use App\Http\Controllers\Api\v1\StoreController;
use App\Http\Controllers\Api\v1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Autentikasi Publik
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Endpoint Terproteksi Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        // Auth User
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Dashboard Metrics
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Master Departemen
        Route::get('/departments', [UserController::class, 'departments']);

        // Manajemen Pengguna & RBAC (Super Admin only)
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // Toko (Stores)
        Route::get('/stores/template', [StoreController::class, 'downloadTemplate']);
        Route::get('/stores/export-otp', [StoreController::class, 'requestExportOtp']);
        Route::get('/stores/export', [StoreController::class, 'export']);
        Route::post('/stores/import', [StoreController::class, 'import']);
        Route::get('/stores', [StoreController::class, 'index']);
        Route::post('/stores', [StoreController::class, 'store']);
        Route::get('/stores/{id}', [StoreController::class, 'show']);
        Route::put('/stores/{id}', [StoreController::class, 'update']);
        Route::delete('/stores/{id}', [StoreController::class, 'destroy']);

        // Perangkat DVR
        Route::post('/dvrs/ping-test', [DvrController::class, 'pingTest']);
        Route::post('/dvrs/{id}/ping-test', [DvrController::class, 'pingTest']);
        Route::post('/stores/{store_id}/dvrs', [DvrController::class, 'store']);
        Route::get('/dvrs/{id}', [DvrController::class, 'show']);
        Route::put('/dvrs/{id}', [DvrController::class, 'update']);
        Route::delete('/dvrs/{id}', [DvrController::class, 'destroy']);

        // Kredensial 5 Akun Departemen DVR
        Route::post('/dvrs/{dvr_id}/accounts/{account_id}/reveal-password', [DvrAccountController::class, 'revealPassword']);
        Route::put('/dvrs/{dvr_id}/accounts/{account_id}', [DvrAccountController::class, 'update']);

        // Checklist Lapangan Teknisi
        Route::get('/checks', [DvrCheckController::class, 'listAll']);
        Route::get('/checks/overdue', [DvrCheckController::class, 'overdue']);
        Route::get('/dvrs/{dvr_id}/checks', [DvrCheckController::class, 'index']);
        Route::post('/dvrs/{dvr_id}/checks', [DvrCheckController::class, 'store']);

        // Audit Logs (Super Admin only)
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
    });
});
