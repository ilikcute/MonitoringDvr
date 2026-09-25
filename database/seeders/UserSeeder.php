<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $icDept = Department::where('code', 'IC')->first();
        $spvDept = Department::where('code', 'SPV')->first();
        $devDept = Department::where('code', 'DEV')->first();
        $audDept = Department::where('code', 'AUD')->first();

        $users = [
            [
                'name' => 'Super Admin EDP',
                'email' => 'admin@cdams.local',
                'password' => Hash::make('password'),
                'role' => 'superadmin',
                'department_id' => null,
                'phone' => '081100000001',
                'is_active' => true,
            ],
            [
                'name' => 'Aan Budianto (Teknisi)',
                'email' => 'teknisi@cdams.local',
                'password' => Hash::make('password'),
                'role' => 'technician',
                'department_id' => null,
                'phone' => '081100000002',
                'is_active' => true,
            ],
            [
                'name' => 'Aris (Operator IC)',
                'email' => 'ic@cdams.local',
                'password' => Hash::make('password'),
                'role' => 'dept_operator',
                'department_id' => $icDept?->id,
                'phone' => '081100000003',
                'is_active' => true,
            ],
            [
                'name' => 'JURIT',
                'email' => 'spv@cdams.local',
                'password' => Hash::make('password'),
                'role' => 'dept_operator',
                'department_id' => $spvDept?->id,
                'phone' => '081100000004',
                'is_active' => true,
            ],
            [
                'name' => 'SYI',
                'email' => 'dev@cdams.local',
                'password' => Hash::make('password'),
                'role' => 'dept_operator',
                'department_id' => $devDept?->id,
                'phone' => '081100000005',
                'is_active' => true,
            ],
            [
                'name' => 'Hendra Kusuma (Auditor Internal)',
                'email' => 'audit@cdams.local',
                'password' => Hash::make('password'),
                'role' => 'dept_operator',
                'department_id' => $audDept?->id,
                'phone' => '081100000006',
                'is_active' => true,
            ],
            [
                'name' => 'Siti Rahma (Manajer Operasional)',
                'email' => 'manager@cdams.local',
                'password' => Hash::make('password'),
                'role' => 'management',
                'department_id' => null,
                'phone' => '081100000007',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
