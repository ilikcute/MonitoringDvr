<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Dvr;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CdamsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_login_and_audit_log_is_recorded(): void
    {
        $admin = User::where('role', 'superadmin')->first();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'token',
                    'network_type',
                ],
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'AUTH_LOGIN_SUCCESS',
            'user_id' => $admin->id,
        ]);
    }

    public function test_failed_login_records_audit_log(): void
    {
        $admin = User::where('role', 'superadmin')->first();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'AUTH_LOGIN_FAILED',
        ]);
    }

    public function test_store_crud_and_dvr_auto_provisioning(): void
    {
        $admin = User::where('role', 'superadmin')->first();

        // 1. Create Store with DVR 1
        $response = $this->actingAs($admin)->postJson('/api/v1/stores', [
            'store_code' => 'T101',
            'store_name' => 'Toko Dago Bandung',
            'region' => 'Jawa Barat',
            'status' => 'Active',
            'create_dvr1' => true,
            'dvr1_ip' => '192.168.25.200',
            'dvr1_label' => 'DVR 1 - Toko & Kasir',
        ]);

        $response->assertStatus(201);
        $storeId = $response->json('data.id');

        $store = Store::with('dvrs.accounts')->find($storeId);
        $this->assertNotNull($store);
        $this->assertCount(1, $store->dvrs);

        $dvr1 = $store->dvrs->first();
        $this->assertCount(5, $dvr1->accounts);

        // 2. Add DVR 2 (Allowed)
        $dvr2Res = $this->actingAs($admin)->postJson("/api/v1/stores/{$storeId}/dvrs", [
            'label' => 'DVR 2 - Gudang',
            'ip_address' => '192.168.25.201',
            'status' => 'Offline',
        ]);
        $dvr2Res->assertStatus(201);

        // 3. Try to add DVR 3 without override (BR-STR-002: should fail with 422)
        $dvr3Res = $this->actingAs($admin)->postJson("/api/v1/stores/{$storeId}/dvrs", [
            'label' => 'DVR 3 - Extra',
            'ip_address' => '192.168.25.202',
        ]);
        $dvr3Res->assertStatus(422);
    }

    public function test_department_isolation_on_credential_reveal(): void
    {
        $admin = User::where('role', 'superadmin')->first();
        $deptOperator = User::where('role', 'dept_operator')->whereNotNull('department_id')->first();
        $operatorDept = $deptOperator->department;
        $otherDept = Department::where('id', '!=', $operatorDept->id)->first();

        $store = Store::create([
            'store_code' => 'T202',
            'store_name' => 'Toko Senayan',
            'region' => 'Jabodetabek',
            'status' => 'Active',
        ]);

        $dvr = Dvr::create([
            'store_id' => $store->id,
            'dvr_index' => 1,
            'label' => 'DVR 1 - Kasir',
            'ip_address' => '192.168.25.200',
        ]);

        $ownAccount = $dvr->accounts()->where('department_id', $operatorDept->id)->first();
        $otherAccount = $dvr->accounts()->where('department_id', $otherDept->id)->first();

        // Operator reveals their own department account -> SUCCESS 200
        $resOwn = $this->actingAs($deptOperator)->postJson("/api/v1/dvrs/{$dvr->id}/accounts/{$ownAccount->id}/reveal-password");
        $resOwn->assertStatus(200)
              ->assertJsonPath('data.department_code', $operatorDept->code)
              ->assertJsonPath('data.expires_in_seconds', 15);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CREDENTIAL_REVEAL',
            'department_code' => $operatorDept->code,
            'user_id' => $deptOperator->id,
        ]);

        // Operator attempts to reveal other department account -> FORBIDDEN 403 (BR-ACC-002)
        $resForbidden = $this->actingAs($deptOperator)->postJson("/api/v1/dvrs/{$dvr->id}/accounts/{$otherAccount->id}/reveal-password");
        $resForbidden->assertStatus(403);

        // Super Admin can reveal ANY account -> SUCCESS 200
        $resAdmin = $this->actingAs($admin)->postJson("/api/v1/dvrs/{$dvr->id}/accounts/{$otherAccount->id}/reveal-password");
        $resAdmin->assertStatus(200);
    }

    public function test_dvr_check_submission_and_ntp_sync_rule(): void
    {
        $technician = User::where('role', 'technician')->first();

        $store = Store::create([
            'store_code' => 'T303',
            'store_name' => 'Toko Kelapa Gading',
            'region' => 'Jabodetabek',
            'status' => 'Active',
        ]);

        $dvr = Dvr::create([
            'store_id' => $store->id,
            'dvr_index' => 1,
            'label' => 'DVR 1',
            'ip_address' => '192.168.25.200',
        ]);

        // Submit check with time difference = 300 seconds (> 180s BR-CHK-002)
        $response = $this->actingAs($technician)->postJson("/api/v1/dvrs/{$dvr->id}/checks", [
            'is_ping_online' => true,
            'is_time_synced' => true, // Sent as true, but should be overridden by > 180s rule
            'time_difference_seconds' => 300,
            'hdd_status' => 'Normal',
            'record_retention_days' => 30,
            'camera_working_count' => 8,
            'camera_broken_count' => 0,
            'notes' => 'Pengecekan rutin teknisi',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_time_synced', false)
            ->assertJsonPath('data.is_ping_online', true);

        $dvr->refresh();
        $this->assertEquals('Online', $dvr->status);
        $this->assertNotNull($dvr->last_check_at);
    }

    public function test_dedicated_ping_test_to_default_ip(): void
    {
        $technician = User::where('role', 'technician')->first();

        $response = $this->actingAs($technician)->postJson('/api/v1/dvrs/ping-test', [
            'ip_address' => '192.168.25.200',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'ip',
                    'status',
                    'is_online',
                    'message',
                ],
            ]);
    }

    public function test_wan_export_requires_otp(): void
    {
        $admin = User::where('role', 'superadmin')->first();

        // 1. Request export simulated on WAN without OTP -> 403 Forbidden
        $response = $this->actingAs($admin)
            ->withHeader('X-Network-Simulate', 'WAN')
            ->getJson('/api/v1/stores/export');

        $response->assertStatus(403)
            ->assertJsonPath('requires_otp', true);

        // 2. Request OTP
        $otpRes = $this->actingAs($admin)->getJson('/api/v1/stores/export-otp');
        $otpRes->assertStatus(200);
        $otpCode = $otpRes->json('data.otp_code');

        // 3. Request export on WAN with valid OTP -> 200 Streamed Download
        $downloadRes = $this->actingAs($admin)
            ->withHeader('X-Network-Simulate', 'WAN')
            ->get("/api/v1/stores/export?format=csv&otp={$otpCode}");

        $downloadRes->assertStatus(200);
    }
}
