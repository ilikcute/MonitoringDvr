<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'code' => 'IC',
                'name' => 'Inventory Control',
                'description' => 'Wewenang Live View & Playback area gudang/stockroom dan kasir',
            ],
            [
                'code' => 'EDP',
                'name' => 'IT Support / EDP Core',
                'description' => 'Wewenang Administrator / Full Access pemeliharaan dan konfigurasi',
            ],
            [
                'code' => 'SPV',
                'name' => 'Supervisor Area',
                'description' => 'Wewenang Live View Only area publik, pintu masuk, dan perimeter',
            ],
            [
                'code' => 'DEV',
                'name' => 'Team Development',
                'description' => 'Wewenang Live View area kasir dan sales area',
            ],
            [
                'code' => 'AUD',
                'name' => 'Internal Audit',
                'description' => 'Wewenang Playback & Export semua channel untuk audit fraud/temuan',
            ],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                [
                    'name' => $dept['name'],
                    'description' => $dept['description'],
                ]
            );
        }
    }
}
