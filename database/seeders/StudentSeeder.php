<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $byGroup = [
            'GR-2026-I' => [
                ['student_id' => 'TLV-2026-001', 'full_name' => 'Aziz Karimov',       'phone' => '+998901001001', 'muassasa_nomi' => 'Samarqand DTI'],
                ['student_id' => 'TLV-2026-002', 'full_name' => 'Barno Sotvoldiyeva', 'phone' => null,            'muassasa_nomi' => 'Samarqand DTI'],
                ['student_id' => 'TLV-2026-003', 'full_name' => 'Doniyor Ergashev',   'phone' => '+998901001003', 'muassasa_nomi' => 'Buxoro SMI'],
                ['student_id' => 'TLV-2026-004', 'full_name' => 'Feruza Yuldasheva',  'phone' => null,            'muassasa_nomi' => 'Buxoro SMI'],
                ['student_id' => 'TLV-2026-005', 'full_name' => 'Hamza Normatov',     'phone' => '+998901001005', 'muassasa_nomi' => 'Namangan DTI'],
            ],
            'GR-2026-II' => [
                ['student_id' => 'TLV-2026-006', 'full_name' => 'Iroda Xasanova',     'phone' => null,            'muassasa_nomi' => 'Samarqand DTI'],
                ['student_id' => 'TLV-2026-007', 'full_name' => 'Jasur Tursunov',     'phone' => '+998901001007', 'muassasa_nomi' => 'Toshkent TTA'],
                ['student_id' => 'TLV-2026-008', 'full_name' => 'Kamola Raximova',    'phone' => null,            'muassasa_nomi' => 'Toshkent TTA'],
                ['student_id' => 'TLV-2026-009', 'full_name' => 'Lochinbek Xoliqov',  'phone' => '+998901001009', 'muassasa_nomi' => 'Namangan DTI'],
                ['student_id' => 'TLV-2026-010', 'full_name' => 'Mohira Ismoilova',   'phone' => null,            'muassasa_nomi' => 'Buxoro SMI'],
            ],
            // Muddati o'tgan guruhlar (arxiv)
            'GR-2025-III' => [
                ['student_id' => 'TLV-2025-011', 'full_name' => 'Nodir Usmonov',      'phone' => '+998901001011', 'muassasa_nomi' => 'Samarqand DTI'],
                ['student_id' => 'TLV-2025-012', 'full_name' => 'Ozoda Saidova',      'phone' => null,            'muassasa_nomi' => 'Toshkent TTA'],
                ['student_id' => 'TLV-2025-013', 'full_name' => 'Parviz Aminov',      'phone' => '+998901001013', 'muassasa_nomi' => 'Namangan DTI'],
            ],
            'GR-2025-IV' => [
                ['student_id' => 'TLV-2025-016', 'full_name' => 'Sarvar Nishonov',    'phone' => null,            'muassasa_nomi' => 'Buxoro SMI'],
                ['student_id' => 'TLV-2025-017', 'full_name' => 'Tamara Yusupova',    'phone' => '+998901001017', 'muassasa_nomi' => 'Samarqand DTI'],
            ],
        ];

        foreach ($byGroup as $groupCode => $students) {
            $group = Group::where('code', $groupCode)->first();
            if (! $group) {
                continue;
            }

            foreach ($students as $s) {
                Student::updateOrCreate(
                    ['student_id' => $s['student_id']],
                    [
                        'full_name'     => $s['full_name'],
                        'group_id'      => $group->id,
                        'phone'         => $s['phone'],
                        'muassasa_nomi' => $s['muassasa_nomi'],
                        'is_active'     => true,
                    ]
                );
            }
        }
    }
}
