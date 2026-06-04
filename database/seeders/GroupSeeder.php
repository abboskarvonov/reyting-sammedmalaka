<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            // Joriy faol guruhlar (2026)
            [
                'name'      => 'I kurs',
                'code'      => 'GR-2026-I',
                'starts_at' => '2026-04-07',
                'ends_at'   => '2026-06-27',
                'is_active' => true,
            ],
            [
                'name'      => 'II kurs',
                'code'      => 'GR-2026-II',
                'starts_at' => '2026-05-05',
                'ends_at'   => '2026-07-25',
                'is_active' => true,
            ],
            // Muddati o'tgan (arxiv) guruhlar
            [
                'name'      => 'III kurs (2025)',
                'code'      => 'GR-2025-III',
                'starts_at' => '2025-03-03',
                'ends_at'   => '2025-05-23',
                'is_active' => true,
            ],
            [
                'name'      => 'IV kurs (2025)',
                'code'      => 'GR-2025-IV',
                'starts_at' => '2025-04-07',
                'ends_at'   => '2025-06-27',
                'is_active' => true,
            ],
        ];

        foreach ($groups as $g) {
            Group::updateOrCreate(['code' => $g['code']], $g);
        }
    }
}
