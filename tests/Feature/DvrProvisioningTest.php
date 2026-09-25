<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Dvr;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DvrProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_dvr_auto_provisions_five_department_accounts(): void
    {
        $this->seed();

        $store = Store::create([
            'store_code' => 'T001',
            'store_name' => 'Toko Grand Central',
            'region' => 'Jabodetabek',
            'status' => 'Active',
        ]);

        $dvr = Dvr::create([
            'store_id' => $store->id,
            'dvr_index' => 1,
            'label' => 'DVR 1 - Toko & Kasir',
            'ip_address' => '192.168.25.200',
        ]);

        $this->assertCount(5, $dvr->accounts);

        $slots = $dvr->accounts->pluck('account_slot')->sort()->values()->all();
        $this->assertEquals([1, 2, 3, 4, 5], $slots);

        $codes = $dvr->accounts->map(fn ($acc) => $acc->department->code)->values()->all();
        $this->assertEquals(['IC', 'EDP', 'SPV', 'DEV', 'AUD'], $codes);

        foreach ($dvr->accounts as $acc) {
            $this->assertNotEmpty($acc->getDecryptedPassword());
            $this->assertStringStartsWith('Pass#', $acc->getDecryptedPassword());
        }
    }
}
