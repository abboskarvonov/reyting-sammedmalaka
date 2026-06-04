<?php

namespace Database\Seeders;

use App\Models\Direction;
use App\Models\Group;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupSubjectTeacherSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. O'qituvchi → Yo'nalish biriktirish (teacher_directions) ──────────
        // email => direction unique_key
        $teacherDirections = [
            'dilnoza@smk.uz'  => 'KRD',
            'jasur@smk.uz'    => 'JRR',
            'malika@smk.uz'   => 'PDT',
            'bobur@smk.uz'    => 'LBR',
            'sarvinoz@smk.uz' => 'STM',
            'ulugbek@smk.uz'  => 'NVR',
            'nilufar@smk.uz'  => 'GNK',
        ];

        foreach ($teacherDirections as $email => $directionKey) {
            $teacher   = Teacher::whereHas('user', fn ($q) => $q->where('email', $email))->first();
            $direction = Direction::where('unique_key', $directionKey)->first();
            if (! $teacher || ! $direction) continue;

            DB::table('teacher_directions')->updateOrInsert(
                ['teacher_id' => $teacher->id, 'direction_id' => $direction->id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // ── 2. Guruh → Yo'nalish biriktirish (group_direction_teacher) ──────────
        // Barcha guruhlar barcha yo'nalishlarga biriktiriladi
        // (O'qituvchi bu yerda ko'rsatilmaydi — u teacher_directions orqali bog'liq)
        $allGroupCodes  = ['GR-2026-I', 'GR-2026-II', 'GR-2025-III', 'GR-2025-IV'];
        $allDirectionKeys = ['KRD', 'JRR', 'PDT', 'LBR', 'STM', 'NVR', 'GNK'];

        foreach ($allGroupCodes as $groupCode) {
            $group = Group::where('code', $groupCode)->first();
            if (! $group) continue;

            foreach ($allDirectionKeys as $dirKey) {
                $direction = Direction::where('unique_key', $dirKey)->first();
                if (! $direction) continue;

                DB::table('group_direction_teacher')->updateOrInsert(
                    ['group_id' => $group->id, 'direction_id' => $direction->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
