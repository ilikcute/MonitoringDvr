<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_superadmin_can_list_and_create_user(): void
    {
        $admin = User::where('role', 'superadmin')->first();
        $icDept = Department::where('code', 'IC')->first();

        // 1. List users
        $response = $this->actingAs($admin)->getJson('/api/v1/users');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);

        // 2. Create new department operator user
        $createRes = $this->actingAs($admin)->postJson('/api/v1/users', [
            'name' => 'Operator Baru IC',
            'email' => 'operator.ic2@cdams.local',
            'password' => 'secret123',
            'role' => 'dept_operator',
            'department_id' => $icDept->id,
            'phone' => '081299887766',
            'is_active' => true,
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('data.email', 'operator.ic2@cdams.local')
            ->assertJsonPath('data.department.code', 'IC');

        $this->assertDatabaseHas('users', [
            'email' => 'operator.ic2@cdams.local',
            'role' => 'dept_operator',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'USER_CREATED',
        ]);
    }

    public function test_superadmin_can_update_and_delete_user(): void
    {
        $admin = User::where('role', 'superadmin')->first();
        $targetUser = User::where('role', 'technician')->first();

        // Update
        $updateRes = $this->actingAs($admin)->putJson("/api/v1/users/{$targetUser->id}", [
            'name' => 'Budi Santoso (Senior)',
            'email' => 'teknisi.senior@cdams.local',
            'role' => 'technician',
            'phone' => '081211112222',
            'is_active' => true,
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Budi Santoso (Senior)');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'USER_UPDATED',
        ]);

        // Delete
        $deleteRes = $this->actingAs($admin)->deleteJson("/api/v1/users/{$targetUser->id}");
        $deleteRes->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'USER_DELETED',
        ]);
    }

    public function test_superadmin_cannot_delete_self(): void
    {
        $admin = User::where('role', 'superadmin')->first();

        $response = $this->actingAs($admin)->deleteJson("/api/v1/users/{$admin->id}");
        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_non_superadmin_is_forbidden_from_user_management(): void
    {
        $technician = User::where('role', 'technician')->first();
        $icOperator = User::whereHas('department', fn ($q) => $q->where('code', 'IC'))->first();

        $res1 = $this->actingAs($technician)->getJson('/api/v1/users');
        $res1->assertStatus(403);

        $res2 = $this->actingAs($icOperator)->postJson('/api/v1/users', [
            'name' => 'Hacker',
            'email' => 'hacker@cdams.local',
            'password' => 'secret123',
            'role' => 'superadmin',
        ]);
        $res2->assertStatus(403);
    }
}
