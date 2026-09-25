<?php

namespace Tests\Feature;

use App\Models\Dvr;
use App\Models\DvrCheck;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected Dvr $dvr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->store = Store::create([
            'store_code' => 'TK999',
            'store_name' => 'Toko Uji Coba',
            'region' => 'Jakarta Barat',
            'status' => 'Active',
        ]);

        $this->dvr = $this->store->dvrs()->create([
            'dvr_index' => 1,
            'label' => 'DVR 1 Area Kasir',
            'brand' => 'Hikvision',
            'ip_address' => '192.168.25.200',
            'http_port' => 80,
            'rtsp_port' => 554,
            'total_channels' => 8,
            'retention_days' => 30,
            'status' => 'Online',
        ]);
    }

    public function test_can_list_all_checklist_history_with_store_details_and_findings(): void
    {
        $technician = User::where('role', 'Technician')->first();
        $dvr = $this->dvr;

        // Buat 1 check normal
        DvrCheck::create([
            'dvr_id' => $dvr->id,
            'checked_by_user_id' => $technician->id,
            'check_timestamp' => now()->subDay(),
            'is_ping_online' => true,
            'is_time_synced' => true,
            'time_difference_seconds' => 5,
            'hdd_status' => 'Normal',
            'record_retention_days' => 30,
            'camera_working_count' => 8,
            'camera_broken_count' => 0,
            'network_type' => 'LAN',
            'notes' => 'Kondisi normal aman',
        ]);

        // Buat 1 check dengan temuan masalah (kamera rusak & hdd error)
        DvrCheck::create([
            'dvr_id' => $dvr->id,
            'checked_by_user_id' => $technician->id,
            'check_timestamp' => now(),
            'is_ping_online' => true,
            'is_time_synced' => false,
            'time_difference_seconds' => 350,
            'hdd_status' => 'Error',
            'record_retention_days' => 15,
            'camera_working_count' => 6,
            'camera_broken_count' => 2,
            'network_type' => 'LAN',
            'notes' => 'Kamera kasir mati, HDD bunyi kasar',
        ]);

        $response = $this->actingAs($technician)->getJson('/api/v1/checks');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'stats' => ['total_checks', 'total_with_issues', 'total_normal'],
                'data' => [
                    '*' => [
                        'id',
                        'formatted_date_time',
                        'is_ping_online',
                        'is_time_synced',
                        'hdd_status',
                        'camera_broken_count',
                        'has_issues',
                        'issues',
                        'notes',
                        'checker' => ['id', 'name', 'role'],
                        'dvr' => [
                            'id',
                            'label',
                            'ip_address',
                            'store' => ['id', 'store_code', 'store_name', 'region']
                        ],
                    ]
                ],
                'meta' => ['total_records', 'current_page']
            ]);

        $this->assertEquals(2, $response->json('stats.total_checks'));
        $this->assertEquals(1, $response->json('stats.total_with_issues'));
        $this->assertEquals(1, $response->json('stats.total_normal'));
    }

    public function test_can_filter_checks_by_issues_only(): void
    {
        $technician = User::where('role', 'Technician')->first();
        $dvr = $this->dvr;

        DvrCheck::create([
            'dvr_id' => $dvr->id,
            'checked_by_user_id' => $technician->id,
            'check_timestamp' => now()->subHours(2),
            'is_ping_online' => true,
            'is_time_synced' => true,
            'time_difference_seconds' => 0,
            'hdd_status' => 'Normal',
            'record_retention_days' => 30,
            'camera_working_count' => 8,
            'camera_broken_count' => 0,
            'network_type' => 'LAN',
            'notes' => 'Semua aman',
        ]);

        DvrCheck::create([
            'dvr_id' => $dvr->id,
            'checked_by_user_id' => $technician->id,
            'check_timestamp' => now(),
            'is_ping_online' => false,
            'is_time_synced' => true,
            'time_difference_seconds' => 0,
            'hdd_status' => 'Normal',
            'record_retention_days' => 30,
            'camera_working_count' => 8,
            'camera_broken_count' => 0,
            'network_type' => 'LAN',
            'notes' => 'DVR mati total RTO',
        ]);

        $response = $this->actingAs($technician)->getJson('/api/v1/checks?has_issue=yes');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertTrue($response->json('data.0.has_issues'));
        $this->assertContains('Ping RTO / Jaringan Offline', $response->json('data.0.issues'));
    }

    public function test_store_show_returns_latest_check_with_timestamp_and_checker(): void
    {
        $technician = User::where('role', 'Technician')->first();
        $store = $this->store;
        $dvr = $this->dvr;

        // Submit check
        $this->actingAs($technician)->postJson("/api/v1/dvrs/{$dvr->id}/checks", [
            'is_ping_online' => true,
            'is_time_synced' => true,
            'time_difference_seconds' => 10,
            'hdd_status' => 'Normal',
            'record_retention_days' => 35,
            'camera_working_count' => 8,
            'camera_broken_count' => 0,
            'notes' => 'Pengecekan berkala berhasil.',
        ])->assertStatus(201);

        // Fetch store show
        $response = $this->actingAs($technician)->getJson("/api/v1/stores/{$store->id}");
        $response->assertStatus(200);

        $dvrData = collect($response->json('data.dvrs'))->firstWhere('id', $dvr->id);
        $this->assertNotNull($dvrData['latest_check']);
        $this->assertNotNull($dvrData['last_check_at']);
        $this->assertEquals(35, $dvrData['retention_days']);
        $this->assertEquals($technician->name, $dvrData['latest_check']['checker_name']);
        $this->assertNotNull($dvrData['latest_check']['formatted_date_time']);
    }
}
